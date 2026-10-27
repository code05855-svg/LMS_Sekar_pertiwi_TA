<?php

namespace App\Observers;

use App\Jobs\HapusIndeksBerkas;
use App\Jobs\KirimBerkasKeN8n;
use App\Models\BerkasMateri;

/**
 * Mendeteksi berkas materi dibuat, diganti, atau dihapus, lalu menjalankan
 * job yang sesuai. Perubahan status indeks memakai updateQuietly(), jadi
 * tidak memicu observer ini lagi.
 */
class BerkasMateriObserver
{
    public function created(BerkasMateri $berkas): void
    {
        KirimBerkasKeN8n::dispatch($berkas->id)->afterCommit();
    }

    public function updated(BerkasMateri $berkas): void
    {
        if (! $berkas->wasChanged('path')) {
            return; // hanya penggantian file PDF yang perlu indeks ulang
        }

        $pathLama = $berkas->getRawOriginal('path');

        // Buang potongan lama dan sembunyikan berkas dari pencarian
        // sampai pengindeksan ulang selesai.
        $berkas->bagianMateri()->delete();
        $berkas->tandaiMenunggu();

        if ($pathLama) {
            HapusIndeksBerkas::dispatch($pathLama)->afterCommit();
        }

        KirimBerkasKeN8n::dispatch($berkas->id)->afterCommit();
    }

    public function deleted(BerkasMateri $berkas): void
    {
        HapusIndeksBerkas::dispatch($berkas->path)->afterCommit();
    }
}
