<?php

namespace App\Services;

use App\Enums\StatusChat;
use App\Models\RiwayatChat;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

/**
 * Menghubungkan pertanyaan pengguna ke workflow n8n "Tanya Jawab" dan
 * mencatat hasilnya di riwayat_chat (DFD proses 6).
 *
 * Hanya pertanyaan dan user_id yang dikirim ke n8n. Kelas dan hak akses
 * dicari Laravel sendiri dari user_id (lihat PencarianMateriController),
 * sehingga n8n tidak bisa meminta materi kelas lain.
 */
class ChatbotService
{
    public const PESAN_GAGAL = 'Maaf, chatbot sedang tidak dapat dihubungi. Coba lagi nanti.';

    public function __construct(private N8nClient $n8n)
    {
    }

    public function tanya(User $user, string $pertanyaan, string $sesiId): RiwayatChat
    {
        $mulai = microtime(true);

        try {
            $url = (string) config('services.n8n.webhook_tanya');

            if ($url === '') {
                throw new \RuntimeException('N8N_WEBHOOK_TANYA belum diisi di .env');
            }

            $res = $this->n8n->kirim($url, [
                'pertanyaan' => $pertanyaan,
                'user_id' => $user->id,
            ])->throw()->json();

            if (! is_array($res)) {
                throw new \UnexpectedValueException('Respons n8n bukan JSON objek');
            }
        } catch (Throwable $e) {
            // Chatbot tidak boleh menjatuhkan halaman: catat galatnya dan
            // tampilkan pesan cadangan. Riwayat tetap tersimpan berstatus gagal.
            report($e);
            $res = ['jawaban' => self::PESAN_GAGAL, 'status' => 'gagal'];
        }

        $berhasil = ($res['status'] ?? 'gagal') === 'berhasil';

        return RiwayatChat::create([
            'user_id' => $user->id,
            'sesi_id' => $sesiId,
            'pertanyaan' => $pertanyaan,
            'jawaban' => $this->bersihkanJawaban($res['jawaban'] ?? '') ?: self::PESAN_GAGAL,
            'sumber' => $berhasil ? $this->bersihkanSumber($res['sumber'] ?? []) : [],
            'token_masuk' => $this->angka($res['token_masuk'] ?? null),
            'token_keluar' => $this->angka($res['token_keluar'] ?? null),
            'waktu_respons_ms' => (int) round((microtime(true) - $mulai) * 1000),
            'status' => $berhasil ? StatusChat::Berhasil : StatusChat::Gagal,
        ]);
    }

    private function bersihkanJawaban(mixed $jawaban): string
    {
        return Str::limit(trim((string) (is_scalar($jawaban) ? $jawaban : '')), 8000, '');
    }

    /**
     * Simpan hanya kolom yang dikenal, supaya JSON di riwayat_chat selalu
     * berbentuk sama walaupun n8n menambah field lain.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bersihkanSumber(mixed $sumber): array
    {
        if (! is_array($sumber)) {
            return [];
        }

        return collect($sumber)
            ->filter(fn ($s) => is_array($s))
            ->map(fn (array $s) => [
                'no' => $this->angka($s['no'] ?? null),
                'materi_id' => $this->angka($s['materi_id'] ?? null),
                'materi' => isset($s['materi']) ? Str::limit((string) $s['materi'], 200, '') : null,
                'mapel' => isset($s['mapel']) ? Str::limit((string) $s['mapel'], 100, '') : null,
                'berkas_id' => $this->angka($s['berkas_id'] ?? null),
                'berkas' => isset($s['berkas']) ? Str::limit((string) $s['berkas'], 255, '') : null,
                'halaman' => $this->angka($s['halaman'] ?? null),
            ])
            ->values()
            ->all();
    }

    private function angka(mixed $nilai): ?int
    {
        return is_numeric($nilai) ? max(0, (int) $nilai) : null;
    }
}
