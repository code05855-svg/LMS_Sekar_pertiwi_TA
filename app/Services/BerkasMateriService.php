<?php

namespace App\Services;

use App\Models\BerkasMateri;
use App\Models\Materi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan, mengganti, dan menghapus berkas PDF materi di disk privat "materi".
 *
 * Pengindeksan chatbot tidak dikerjakan di sini: BerkasMateriObserver
 * mendeteksi berkas dibuat, diganti, atau dihapus, lalu menjalankan job.
 */
class BerkasMateriService
{
    public function simpan(Materi $materi, UploadedFile $file): BerkasMateri
    {
        return $materi->berkasMateri()->create($this->atribut($materi, $file));
    }

    /** Ganti file PDF; Observer mengembalikan status ke "menunggu" dan mengindeks ulang. */
    public function ganti(BerkasMateri $berkas, UploadedFile $file): BerkasMateri
    {
        $berkas->update($this->atribut($berkas->materi, $file));

        return $berkas;
    }

    /** Hapus catatan; file fisiknya dihapus job HapusIndeksBerkas lewat Observer. */
    public function hapus(BerkasMateri $berkas): void
    {
        $berkas->delete();
    }

    /** @return array<string, mixed> */
    private function atribut(Materi $materi, UploadedFile $file): array
    {
        $path = Storage::disk('materi')->putFileAs(
            (string) $materi->id,
            $file,
            Str::uuid()->toString().'.pdf',
        );

        return [
            'nama_asli' => Str::limit($file->getClientOriginalName(), 250, ''),
            'path' => $path,
            'mime' => 'application/pdf',
            'ukuran' => $file->getSize(),
        ];
    }
}
