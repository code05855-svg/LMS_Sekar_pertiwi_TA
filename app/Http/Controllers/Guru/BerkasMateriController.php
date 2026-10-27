<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBerkasMateriRequest;
use App\Jobs\KirimBerkasKeN8n;
use App\Models\BerkasMateri;
use App\Models\Materi;
use App\Services\BerkasMateriService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Berkas PDF pembelajaran (sumber chatbot). Pengindeksan dijalankan BerkasMateriObserver. */
class BerkasMateriController extends Controller
{
    public function store(StoreBerkasMateriRequest $request, Materi $materi, BerkasMateriService $layanan): RedirectResponse
    {
        $this->authorize('update', $materi);

        foreach ($request->file('berkas') as $file) {
            $layanan->simpan($materi, $file);
        }

        return redirect()->route('guru.materi.show', $materi)
            ->with('sukses', count($request->file('berkas')).' berkas diunggah. Pengindeksan chatbot berjalan di latar belakang.');
    }

    /** Buka PDF di tab baru, atau unduh dengan ?unduh=1. */
    public function show(Request $request, BerkasMateri $berkas): StreamedResponse
    {
        $this->authorize('view', $berkas->materi);

        $disk = Storage::disk('materi');
        abort_unless($disk->exists($berkas->path), 404, 'File PDF tidak ditemukan di penyimpanan.');

        return $request->boolean('unduh')
            ? $disk->download($berkas->path, $berkas->nama_asli)
            : $disk->response($berkas->path, $berkas->nama_asli, ['Content-Type' => 'application/pdf'], 'inline');
    }

    /** Ganti file PDF: indeks lama dibuang, berkas diindeks ulang. */
    public function update(Request $request, BerkasMateri $berkas, BerkasMateriService $layanan): RedirectResponse
    {
        $this->authorize('update', $berkas->materi);

        $request->validate(
            ['berkas' => ['required', ...StoreBerkasMateriRequest::aturanPdf()]],
            ['berkas.required' => 'Pilih berkas PDF pengganti.', 'berkas.mimes' => 'Berkas harus berformat PDF.', 'berkas.mimetypes' => 'Berkas harus berformat PDF.'],
        );

        $layanan->ganti($berkas, $request->file('berkas'));

        return redirect()->route('guru.materi.show', $berkas->materi_id)
            ->with('sukses', 'PDF diganti. Indeks lama dibuang dan berkas diindeks ulang.');
    }

    public function destroy(BerkasMateri $berkas, BerkasMateriService $layanan): RedirectResponse
    {
        $this->authorize('update', $berkas->materi);

        $layanan->hapus($berkas);

        return redirect()->route('guru.materi.show', $berkas->materi_id)
            ->with('sukses', 'Berkas dihapus beserta indeks chatbot-nya.');
    }

    /** Kirim ulang ke n8n (status tersangkut, gagal, atau ingin menyegarkan indeks). */
    public function reindeks(BerkasMateri $berkas): RedirectResponse
    {
        $this->authorize('update', $berkas->materi);

        $berkas->tandaiMenunggu();
        KirimBerkasKeN8n::dispatch($berkas->id)->afterCommit();

        return back()->with('sukses', 'Permintaan indeks ulang dikirim.');
    }
}
