<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PenugasanGuru;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kelas dan mata pelajaran yang diampu guru (satu kartu = satu penugasan). */
class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $daftar = PenugasanGuru::query()
            ->diaksesOleh($request->user())
            ->with(['kelas' => fn ($q) => $q->withCount('siswa'), 'mataPelajaran'])
            ->withCount(['materi', 'tugas', 'forumTopik'])
            ->get()
            ->sortBy(fn ($p) => $p->kelas->nama.$p->mataPelajaran->nama)
            ->values();

        return view('guru.kelas.index', ['daftar' => $daftar]);
    }

    public function show(PenugasanGuru $penugasan): View
    {
        $this->pastikanAkses($penugasan);

        $penugasan->load(['kelas', 'mataPelajaran']);

        return view('guru.kelas.show', [
            'penugasan' => $penugasan,
            'daftarMateri' => $penugasan->materi()
                ->withCount('berkasMateri')
                ->withCount(['berkasMateri as berkas_terindeks_count' => fn ($q) => $q->terindeks()])
                ->latest()
                ->get(),
            'daftarTugas' => $penugasan->tugas()
                ->withCount('pengumpulanTugas')
                ->withCount(['pengumpulanTugas as belum_dinilai_count' => fn ($q) => $q->belumDinilai()])
                ->orderByDesc('tenggat')
                ->get(),
            'topikTerbaru' => $penugasan->forumTopik()
                ->with('pembuat')
                ->withCount('balasan')
                ->urutTampil()
                ->limit(5)
                ->get(),
            'daftarSiswa' => $penugasan->kelas->siswa()->orderBy('nama')->get(),
        ]);
    }
}
