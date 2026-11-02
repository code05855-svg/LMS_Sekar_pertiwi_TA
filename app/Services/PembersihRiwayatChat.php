<?php

namespace App\Services;

use App\Models\RiwayatChat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PembersihRiwayatChat
{
    public function bersihkan(?int $hari = null, bool $uji = false): array
    {
        $hari = max(1, $hari ?? (int) config('lms.chatbot.retensi_hari'));
        $batas = now()->subDays($hari);

        $sesi = RiwayatChat::query()
            ->select('sesi_id')
            ->groupBy('sesi_id')
            ->havingRaw('MAX(created_at) < ?', [$batas->toDateTimeString()])
            ->pluck('sesi_id');

        $baris = 0;

        foreach ($sesi->chunk(200) as $kelompok) {
            if ($uji) {
                $baris += RiwayatChat::whereIn('sesi_id', $kelompok)->count();

                continue;
            }

            DB::transaction(function () use ($kelompok, &$baris) {
                $this->ringkas($kelompok);
                $baris += RiwayatChat::whereIn('sesi_id', $kelompok)->delete();
            });
        }

        return ['sesi' => $sesi->count(), 'baris' => $baris];
    }

    /** Tambahkan angka sesi-sesi ini ke statistik_chat_harian (menambah, bukan menimpa). */
    private function ringkas(Collection $sesi): void
    {
        $perHari = RiwayatChat::query()
            ->whereIn('sesi_id', $sesi)
            ->selectRaw("DATE(created_at) AS tanggal, COUNT(*) AS jumlah,
                SUM(status = 'berhasil') AS berhasil, SUM(status = 'gagal') AS gagal,
                COALESCE(SUM(token_masuk), 0) AS token_masuk, COALESCE(SUM(token_keluar), 0) AS token_keluar,
                COALESCE(SUM(CASE WHEN status = 'berhasil' THEN waktu_respons_ms END), 0) AS total_waktu_ms")
            ->groupByRaw('DATE(created_at)')
            ->get();

        foreach ($perHari as $h) {
            DB::statement(
                'INSERT INTO statistik_chat_harian
                    (tanggal, jumlah, berhasil, gagal, token_masuk, token_keluar, total_waktu_ms, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    jumlah = jumlah + VALUES(jumlah), berhasil = berhasil + VALUES(berhasil),
                    gagal = gagal + VALUES(gagal), token_masuk = token_masuk + VALUES(token_masuk),
                    token_keluar = token_keluar + VALUES(token_keluar),
                    total_waktu_ms = total_waktu_ms + VALUES(total_waktu_ms), updated_at = VALUES(updated_at)',
                [$h->tanggal, $h->jumlah, $h->berhasil, $h->gagal, $h->token_masuk, $h->token_keluar, $h->total_waktu_ms, now()->toDateTimeString()]
            );
        }
    }
}
