<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengumpulanTugasRequest;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Siswa mengumpulkan jawaban (proses 4.3). Status tepat waktu atau terlambat
 * dihitung server dari jam saat berkas diterima, bukan dari input siswa.
 */
class PengumpulanTugasController extends Controller
{
    public function store(StorePengumpulanTugasRequest $request, Tugas $tugas): RedirectResponse
    {
        $this->authorize('kumpul', $tugas);

        $siswa = $request->user();

        if ($tugas->pengumpulanTugas()->where('siswa_id', $siswa->id)->exists()) {
            return back()->with('galat', 'Kamu sudah mengumpulkan tugas ini. Gunakan "Ganti berkas" bila ingin memperbaiki.');
        }

        $waktu = now();

        $pengumpulan = PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
            'waktu_kumpul' => $waktu,
            'status' => $tugas->statusUntuk($waktu),
        ] + $this->simpanBerkas($tugas, $siswa->id, $request->file('berkas')));

        return redirect()->route('siswa.tugas.show', $tugas)->with(
            'sukses',
            $pengumpulan->status->value === 'terlambat'
                ? 'Jawaban diterima, tetapi tercatat terlambat.'
                : 'Jawaban berhasil dikumpulkan tepat waktu.'
        );
    }

    /** Ganti berkas selama belum dinilai; waktu dan status dihitung ulang. */
    public function update(StorePengumpulanTugasRequest $request, PengumpulanTugas $pengumpulan): RedirectResponse
    {
        $this->authorize('update', $pengumpulan);

        $tugas = $pengumpulan->tugas;
        $waktu = now();
        $pathLama = $pengumpulan->path;

        $pengumpulan->update([
            'waktu_kumpul' => $waktu,
            'status' => $tugas->statusUntuk($waktu),
        ] + $this->simpanBerkas($tugas, $request->user()->id, $request->file('berkas')));

        Storage::disk('pengumpulan')->delete($pathLama);

        return redirect()->route('siswa.tugas.show', $tugas)->with('sukses', 'Berkas jawaban diganti.');
    }

    /** Tarik kembali jawaban selama belum dinilai. */
    public function destroy(PengumpulanTugas $pengumpulan): RedirectResponse
    {
        $this->authorize('delete', $pengumpulan);

        $tugasId = $pengumpulan->tugas_id;
        Storage::disk('pengumpulan')->delete($pengumpulan->path);
        $pengumpulan->delete();

        return redirect()->route('siswa.tugas.show', $tugasId)->with('sukses', 'Jawaban ditarik. Kamu bisa mengumpulkannya lagi.');
    }

    public function unduh(Request $request, PengumpulanTugas $pengumpulan): StreamedResponse
    {
        $this->authorize('view', $pengumpulan);

        $disk = Storage::disk('pengumpulan');
        abort_unless($disk->exists($pengumpulan->path), 404, 'Berkas jawaban tidak ditemukan.');

        return $disk->download($pengumpulan->path, $pengumpulan->nama_asli);
    }

    /** @return array<string, mixed> */
    private function simpanBerkas(Tugas $tugas, int $siswaId, UploadedFile $file): array
    {
        $ekstensi = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

        $path = Storage::disk('pengumpulan')->putFileAs(
            (string) $tugas->id,
            $file,
            $siswaId.'-'.Str::uuid().'.'.$ekstensi,
        );

        return [
            'nama_asli' => Str::limit($file->getClientOriginalName(), 250, ''),
            'path' => $path,
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'ukuran' => $file->getSize(),
        ];
    }
}
