<?php

namespace App\Jobs;

use App\Models\BerkasMateri;
use App\Services\N8nClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Memicu workflow n8n "Indeks Dokumen" untuk satu berkas materi.
 * Webhook n8n membalas langsung (Respond Immediately), jadi job ini selesai
 * dalam hitungan detik; n8n kemudian mengambil PDF dan mengirim hasilnya
 * ke /api/n8n/berkas-materi/{id}/bagian.
 */
class KirimBerkasKeN8n implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> detik */
    public array $backoff = [5, 20];

    public function __construct(public int $berkasId)
    {
    }

    public function handle(N8nClient $n8n): void
    {
        $berkas = BerkasMateri::find($this->berkasId);

        if (! $berkas) {
            return; // berkas sudah dihapus sebelum job berjalan
        }

        $url = (string) config('services.n8n.webhook_indeks');

        if ($url === '') {
            $berkas->tandaiGagal('Alamat webhook n8n belum diisi di .env (N8N_WEBHOOK_INDEKS)');

            return;
        }

        $berkas->tandaiDiproses();

        try {
            $n8n->kirim($url, ['berkas_id' => $berkas->id], 15)->throw();
        } catch (Throwable $e) {
            // Pada antrean "sync" galat akan langsung muncul sebagai halaman 500
            // saat guru mengunggah. Tandai gagal saja; guru dapat menekan "Indeks ulang".
            if ($this->job === null || $this->job->getConnectionName() === 'sync') {
                report($e);
                $berkas->tandaiGagal('n8n tidak dapat dihubungi');

                return;
            }

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        BerkasMateri::whereKey($this->berkasId)->get()->each(
            fn (BerkasMateri $b) => $b->tandaiGagal('n8n tidak dapat dihubungi')
        );
    }
}
