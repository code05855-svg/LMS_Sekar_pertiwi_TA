<?php
 
namespace App\Http\Controllers\Siswa;
 
use App\Enums\JenisJawaban;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengumpulanTugasRequest;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Support\BerkasAman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
 
/**
 * Siswa mengumpulkan jawaban (proses 4.3): dokumen, foto, tautan, atau teks.
 * Status tepat waktu atau terlambat dihitung server dari jam saat jawaban
 * diterima, bukan dari input siswa.
 */
class PengumpulanTugasController extends Controller
{
    public function store(StorePengumpulanTugasRequest $request, Tugas $tugas): RedirectResponse
    {
        $this->authorize('kumpul', $tugas);
 
        $siswa = $request->user();
 
        if ($tugas->pengumpulanTugas()->where('siswa_id', $siswa->id)->exists()) {
            return back()->with('galat', 'Kamu sudah mengumpulkan tugas ini. Gunakan "Ganti jawaban" bila ingin memperbaiki.');
        }
 
        $waktu = now();
 
        $pengumpulan = PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
            'waktu_kumpul' => $waktu,
            'status' => $tugas->statusUntuk($waktu),
        ] + $this->isiJawaban($request, $tugas, $siswa->id));
 
        return redirect()->route('siswa.tugas.show', $tugas)->with(
            'sukses',
            $pengumpulan->status->value === 'terlambat'
                ? 'Jawaban diterima, tetapi tercatat terlambat.'
                : 'Jawaban berhasil dikumpulkan tepat waktu.'
        );
    }
 
    /** Ganti jawaban (boleh berganti jenis) selama belum dinilai; waktu dan status dihitung ulang. */
    public function update(StorePengumpulanTugasRequest $request, PengumpulanTugas $pengumpulan): RedirectResponse
    {
        $this->authorize('update', $pengumpulan);
 
        $tugas = $pengumpulan->tugas;
        $waktu = now();
        $pathLama = $pengumpulan->path;
 
        $pengumpulan->update([
            'waktu_kumpul' => $waktu,
            'status' => $tugas->statusUntuk($waktu),
        ] + $this->isiJawaban($request, $tugas, $request->user()->id));
 
        if ($pathLama) {
            Storage::disk('pengumpulan')->delete($pathLama);
        }
 
        return redirect()->route('siswa.tugas.show', $tugas)->with('sukses', 'Jawaban diganti.');
    }
 
    /** Tarik kembali jawaban selama belum dinilai. */
    public function destroy(PengumpulanTugas $pengumpulan): RedirectResponse
    {
        $this->authorize('delete', $pengumpulan);
 
        $tugasId = $pengumpulan->tugas_id;
 
        if ($pengumpulan->path) {
            Storage::disk('pengumpulan')->delete($pengumpulan->path);
        }
 
        $pengumpulan->delete();
 
        return redirect()->route('siswa.tugas.show', $tugasId)->with('sukses', 'Jawaban ditarik. Kamu bisa mengumpulkannya lagi.');
    }
 
    /** Unduh berkas jawaban sendiri, atau buka PDF/foto langsung dengan ?lihat=1. */
    public function unduh(Request $request, PengumpulanTugas $pengumpulan): StreamedResponse
    {
        $this->authorize('view', $pengumpulan);
 
        abort_unless($pengumpulan->berupaBerkas(), 404, 'Jawaban ini tidak berupa berkas.');
 
        return BerkasAman::kirim('pengumpulan', $pengumpulan->path, (string) $pengumpulan->nama_asli, $pengumpulan->mime, $request->boolean('lihat'));
    }
 
    /**
     * Kolom isi jawaban sesuai jenis yang dipilih. Kolom jenis lain diisi null,
     * sehingga saat siswa berganti jenis, isi jenis sebelumnya tidak tertinggal.
     *
     * @return array<string, mixed>
     */
    private function isiJawaban(StorePengumpulanTugasRequest $request, Tugas $tugas, int $siswaId): array
    {
        $jenis = JenisJawaban::from($request->validated('jenis'));
 
        $kosong = ['nama_asli' => null, 'path' => null, 'mime' => null, 'ukuran' => null, 'tautan' => null, 'teks' => null];
 
        return match ($jenis) {
            JenisJawaban::Dokumen, JenisJawaban::Foto => ['jenis' => $jenis] + $this->simpanBerkas($tugas, $siswaId, $request->file('berkas')) + $kosong,
            JenisJawaban::Tautan => ['jenis' => $jenis, 'tautan' => $request->validated('tautan')] + $kosong,
            JenisJawaban::Teks => ['jenis' => $jenis, 'teks' => $request->validated('teks')] + $kosong,
        };
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
