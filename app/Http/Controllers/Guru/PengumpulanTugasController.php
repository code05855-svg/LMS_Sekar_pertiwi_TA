<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Guru melihat pengumpulan tugas kelasnya (proses 3.5). */
class PengumpulanTugasController extends Controller
{
    /** Semua siswa di kelas, termasuk yang belum mengumpulkan. */
    public function index(Tugas $tugas): View
    {
        $this->authorize('view', $tugas);

        $tugas->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran']);

        $siswa = User::siswa()
            ->where('kelas_id', $tugas->penugasanGuru->kelas_id)
            ->orderBy('nama')
            ->get();

        return view('guru.pengumpulan-tugas.index', [
            'tugas' => $tugas,
            'siswa' => $siswa,
            'pengumpulan' => $tugas->pengumpulanTugas()->get()->keyBy('siswa_id'),
        ]);
    }

    public function show(PengumpulanTugas $pengumpulan): View
    {
        $this->authorize('view', $pengumpulan);

        $pengumpulan->load(['siswa', 'tugas.penugasanGuru.kelas', 'tugas.penugasanGuru.mataPelajaran']);

        $belumDinilai = PengumpulanTugas::query()
            ->where('tugas_id', $pengumpulan->tugas_id)
            ->belumDinilai()
            ->where('id', '!=', $pengumpulan->id)
            ->count();

        return view('guru.pengumpulan-tugas.show', [
            'pengumpulan' => $pengumpulan,
            'sisaBelumDinilai' => $belumDinilai,
        ]);
    }

    /** Unduh jawaban, atau buka di tab baru dengan ?lihat=1 (PDF dan gambar). */
    public function unduh(Request $request, PengumpulanTugas $pengumpulan): StreamedResponse
    {
        $this->authorize('view', $pengumpulan);

        $disk = Storage::disk('pengumpulan');
        abort_unless($disk->exists($pengumpulan->path), 404, 'Berkas jawaban tidak ditemukan di penyimpanan.');

        $bolehInline = $request->boolean('lihat')
            && (str_starts_with($pengumpulan->mime, 'image/') || $pengumpulan->mime === 'application/pdf');

        return $bolehInline
            ? $disk->response($pengumpulan->path, $pengumpulan->nama_asli, ['Content-Type' => $pengumpulan->mime], 'inline')
            : $disk->download($pengumpulan->path, $pengumpulan->nama_asli);
    }
}
