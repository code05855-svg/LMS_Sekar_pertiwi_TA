<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function show(Request $request, Tugas $tugas): View
    {
        $this->authorize('view', $tugas);

        $tugas->load(['penugasanGuru.guru', 'penugasanGuru.kelas', 'penugasanGuru.mataPelajaran']);

        return view('siswa.tugas.show', [
            'tugas' => $tugas,
            'pengumpulan' => $tugas->pengumpulanTugas()->where('siswa_id', $request->user()->id)->first(),
        ]);
    }
}
