<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Menghapus file PDF fisik setelah berkas materi dihapus atau diganti.
 * Potongan teks (bagian_materi) ikut hilang lewat cascade FK atau
 * BerkasMateriObserver.
 */
class HapusIndeksBerkas implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $path)
    {
    }

    public function handle(): void
    {
        Storage::disk('materi')->delete($this->path);
    }
}
