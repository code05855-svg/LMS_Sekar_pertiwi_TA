<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Status pengumpulan dan nilai semua tugas siswa (proses 4.4). */
class NilaiController extends Controller
{
    public function index(Request $request): View
    {
        $siswa = $request->user();

        $tugas = Tugas::query()
            ->diaksesOleh($siswa)
            ->with([
                'penugasanGuru.mataPelajaran',
                'pengumpulanTugas' => fn ($q) => $q->where('siswa_id', $siswa->id),
            ])
            ->orderByDesc('tenggat')
            ->get();

        $dinilai = $tugas->filter(fn ($t) => $t->pengumpulanTugas->first()?->telahDinilai());

        return view('siswa.nilai.index', [
            'daftar' => $tugas,
            'ringkasan' => [
                'tugas' => $tugas->count(),
                'dikumpulkan' => $tugas->filter(fn ($t) => $t->pengumpulanTugas->isNotEmpty())->count(),
                'dinilai' => $dinilai->count(),
                // Rata-rata dalam skala 0-100 agar adil bila nilai maksimum tiap tugas berbeda.
                'rata_rata' => $dinilai->isEmpty() ? null : round($dinilai->avg(
                    fn ($t) => (float) $t->pengumpulanTugas->first()->nilai / $t->nilai_maksimum * 100
                ), 1),
            ],
        ]);
    }
}
