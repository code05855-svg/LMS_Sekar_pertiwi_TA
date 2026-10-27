<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PenugasanGuru;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Daftar mata pelajaran di kelas siswa (proses 4.1). */
class MataPelajaranController extends Controller
{
    public function index(Request $request): View
    {
        $siswa = $request->user();

        $daftar = PenugasanGuru::query()
            ->diaksesOleh($siswa)
            ->with(['guru', 'mataPelajaran', 'kelas'])
            ->withCount([
                'materi' => fn ($q) => $q->terbit(),
                'tugas',
                'tugas as tugas_belum_dikumpulkan_count' => fn ($q) => $q->whereDoesntHave(
                    'pengumpulanTugas',
                    fn ($p) => $p->where('siswa_id', $siswa->id)
                ),
            ])
            ->get()
            ->sortBy(fn ($p) => $p->mataPelajaran->nama)
            ->values();

        return view('siswa.mata-pelajaran.index', [
            'daftar' => $daftar,
            'kelas' => $siswa->kelas,
        ]);
    }

    public function show(Request $request, PenugasanGuru $penugasan): View
    {
        $this->pastikanAkses($penugasan);

        $siswa = $request->user();
        $penugasan->load(['guru', 'mataPelajaran', 'kelas']);

        return view('siswa.mata-pelajaran.show', [
            'penugasan' => $penugasan,
            'daftarMateri' => $penugasan->materi()
                ->terbit()
                ->withCount('berkasMateri')
                ->latest()
                ->get(),
            'daftarTugas' => $penugasan->tugas()
                ->with(['pengumpulanTugas' => fn ($q) => $q->where('siswa_id', $siswa->id)])
                ->urutTenggat()
                ->get(),
            'jumlahTopik' => $penugasan->forumTopik()->count(),
        ]);
    }
}
