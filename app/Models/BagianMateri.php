<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Potongan teks hasil ekstraksi PDF | DFD D5.1.
 * Sumber pencarian chatbot (FULLTEXT pada kolom teks) dan rujukan jawaban.
 *
 * @property int $id
 * @property int $berkas_materi_id
 * @property int $nomor_bagian
 * @property int|null $halaman
 * @property string $teks
 * @property-read float|null $skor   Hanya ada setelah scope cocokDengan()
 * @property-read BerkasMateri $berkasMateri
 */
class BagianMateri extends Model
{
    protected $table = 'bagian_materi';

    protected $fillable = [
        'berkas_materi_id',
        'nomor_bagian',
        'halaman',
        'teks',
    ];

    protected function casts(): array
    {
        return [
            'nomor_bagian' => 'integer',
            'halaman' => 'integer',
        ];
    }

    public function berkasMateri(): BelongsTo
    {
        return $this->belongsTo(BerkasMateri::class, 'berkas_materi_id');
    }

    // ------------------------------------------------------------------
    // Scope pencarian chatbot (proses 6.3)
    // ------------------------------------------------------------------

    /**
     * Pencarian FULLTEXT: menambah kolom `skor`, menyaring baris yang cocok,
     * dan mengurutkan dari skor tertinggi.
     *
     * $kata harus sudah disaring (kata tanya umum dan kata < 3 huruf dibuang,
     * karena FULLTEXT InnoDB mengabaikannya). Nilai dikirim sebagai binding.
     */
    public function scopeCocokDengan(Builder $query, string $kata): void
    {
        $query->select('bagian_materi.*')
            ->selectRaw('MATCH(bagian_materi.teks) AGAINST (? IN NATURAL LANGUAGE MODE) AS skor', [$kata])
            ->whereRaw('MATCH(bagian_materi.teks) AGAINST (? IN NATURAL LANGUAGE MODE)', [$kata])
            ->orderByDesc('skor');
    }

    /**
     * Batasi hanya pada bagian yang BOLEH dibaca pengguna ini:
     * berkas berstatus terindeks, materi berstatus terbit, dan materi milik
     * kelas siswa atau mapel yang diampu guru. Admin (tanpa kelas) mendapat
     * hasil kosong. Ini titik kontrol akses chatbot: n8n hanya menerima
     * hasil scope ini.
     */
    public function scopeUntukPengguna(Builder $query, User $user): void
    {
        $query->whereHas('berkasMateri', fn (Builder $b) => $b
            ->terindeks()
            ->whereHas('materi', fn (Builder $m) => $m
                ->terbit()
                ->when(
                    $user->adalahGuru(),
                    fn (Builder $q) => $q->untukGuru($user),
                    fn (Builder $q) => $q->untukSiswa($user),
                )));
    }

    /** Urutan baca dokumen. */
    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('nomor_bagian');
    }
}
