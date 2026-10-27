<?php

namespace App\Models;

use App\Enums\StatusChat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat chatbot: satu baris = satu tanya-jawab | DFD D6.
 *
 * sumber (array) berisi rujukan materi, mis.
 *   [['materi_id' => 5, 'materi' => 'Fotosintesis', 'mapel' => 'IPA',
 *     'berkas_id' => 12, 'berkas' => 'fotosintesis.pdf', 'halaman' => 3]]
 * status 'gagal' = jawaban cadangan; baris tidak ikut rata-rata waktu respons.
 *
 * @property int $id
 * @property int $user_id
 * @property string $sesi_id
 * @property string $pertanyaan
 * @property string|null $jawaban
 * @property array<int, array<string, mixed>>|null $sumber
 * @property int|null $token_masuk
 * @property int|null $token_keluar
 * @property int|null $waktu_respons_ms
 * @property StatusChat $status
 * @property-read User $user
 */
class RiwayatChat extends Model
{
    protected $table = 'riwayat_chat';

    protected $fillable = [
        'user_id',
        'sesi_id',
        'pertanyaan',
        'jawaban',
        'sumber',
        'token_masuk',
        'token_keluar',
        'waktu_respons_ms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sumber' => 'array',
            'token_masuk' => 'integer',
            'token_keluar' => 'integer',
            'waktu_respons_ms' => 'integer',
            'status' => StatusChat::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    public function scopeMilik(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function scopeSesi(Builder $query, string $sesiId): void
    {
        $query->where('sesi_id', $sesiId);
    }

    public function scopeBerhasil(Builder $query): void
    {
        $query->where('status', StatusChat::Berhasil);
    }
}
