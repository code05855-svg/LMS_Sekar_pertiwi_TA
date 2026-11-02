<?php
 
namespace App\Http\Controllers\Guru;
 
use App\Enums\JenisJawaban;
use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Models\User;
use App\Support\BerkasAman;
use App\Support\PratinjauTeks;
use Illuminate\Http\Request;
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
 
        $pengumpulan->load(['siswa', 'tugas.lampiran', 'tugas.penugasanGuru.kelas', 'tugas.penugasanGuru.mataPelajaran']);
 
        $belumDinilai = PengumpulanTugas::query()
            ->where('tugas_id', $pengumpulan->tugas_id)
            ->belumDinilai()
            ->where('id', '!=', $pengumpulan->id)
            ->count();
 
        // Dokumen .docx dan .txt tidak bisa dibuka langsung di peramban; tampilkan teksnya.
        $teksPratinjau = $pengumpulan->jenis === JenisJawaban::Dokumen
            ? PratinjauTeks::dari('pengumpulan', $pengumpulan->path, $pengumpulan->nama_asli)
            : null;
 
        return view('guru.pengumpulan-tugas.show', [
            'pengumpulan' => $pengumpulan,
            'sisaBelumDinilai' => $belumDinilai,
            'teksPratinjau' => $teksPratinjau,
        ]);
    }
 
    /** Unduh berkas jawaban, atau buka di tab baru dengan ?lihat=1 (hanya PDF dan gambar). */
    public function unduh(Request $request, PengumpulanTugas $pengumpulan): StreamedResponse
    {
        $this->authorize('view', $pengumpulan);
 
        abort_unless($pengumpulan->berupaBerkas(), 404, 'Jawaban ini tidak berupa berkas.');
 
        return BerkasAman::kirim('pengumpulan', $pengumpulan->path, (string) $pengumpulan->nama_asli, $pengumpulan->mime, $request->boolean('lihat'));
    }
}
