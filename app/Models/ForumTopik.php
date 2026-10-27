<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Topik forum diskusi per kelas+mapel (lewat penugasan_guru_id) | DFD D4.1.
 * Teks saja. Tidak diindeks chatbot.
 *
 * disematkan dan dikunci SENGAJA tidak ada di $fillable: hanya guru yang
 * mengubahnya (Guru/ForumModerasiController), lewat sematkan() dan kunci().
 *
 * @property int $id
 * @property int $penugasan_guru_id
 * @property int $user_id
 * @property string $judul
 * @property string $isi
 * @property bool $disematkan
 * @property bool $dikunci
 * @property-read PenugasanGuru $penugasanGuru
 * @property-read User $pembuat
 */
class ForumTopik extends Model
{
    protected $table = 'forum_topik';

    protected $fillable = [
        'penugasan_guru_id',
        'user_id',
        'judul',
        'isi',
    ];

    protected function casts(): array
    {
        return [
            'disematkan' => 'boolean',
            'dikunci' => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    public function penugasanGuru(): BelongsTo
    {
        return $this->belongsTo(PenugasanGuru::class, 'penugasan_guru_id');
    }

    /** Pembuat topik (guru atau siswa). */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function balasan(): HasMany
    {
        return $this->hasMany(ForumBalasan::class, 'forum_topik_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    /** Forum pada kelas siswa, atau pada mapel+kelas yang diampu guru. */
    public function scopeDiaksesOleh(Builder $query, User $user): void
    {
        $query->whereHas('penugasanGuru', fn (Builder $p) => $p->diaksesOleh($user));
    }

    /** Topik yang disematkan di atas, lalu yang terbaru (query [5.1]). */
    public function scopeUrutTampil(Builder $query): void
    {
        $query->orderByDesc('disematkan')->orderByDesc('created_at');
    }

    // ------------------------------------------------------------------
    // Moderasi (proses 5.4)
    // ------------------------------------------------------------------

    public function sematkan(bool $nilai = true): bool
    {
        return $this->forceFill(['disematkan' => $nilai])->save();
    }

    public function kunci(bool $nilai = true): bool
    {
        return $this->forceFill(['dikunci' => $nilai])->save();
    }
}
