<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\BerkasMateri;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Siswa membaca dan mengunduh materi TERBIT pada kelasnya (proses 4.2). */
class MateriController extends Controller
{
    public function show(Materi $materi): View
    {
        $this->authorize('view', $materi);

        $materi->load(['penugasanGuru.guru', 'penugasanGuru.kelas', 'penugasanGuru.mataPelajaran', 'berkasMateri' => fn ($q) => $q->orderBy('id')]);

        return view('siswa.materi.show', ['materi' => $materi]);
    }

    /** Unduh PDF, atau buka di tab baru dengan ?lihat=1. */
    public function unduh(Request $request, BerkasMateri $berkas): StreamedResponse
    {
        $this->authorize('view', $berkas->materi);

        $disk = Storage::disk('materi');
        abort_unless($disk->exists($berkas->path), 404, 'File materi tidak ditemukan. Hubungi gurumu.');

        return $request->boolean('lihat')
            ? $disk->response($berkas->path, $berkas->nama_asli, ['Content-Type' => 'application/pdf'], 'inline')
            : $disk->download($berkas->path, $berkas->nama_asli);
    }
}
