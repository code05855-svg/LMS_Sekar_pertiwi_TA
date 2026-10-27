<?php

namespace App\Models;

use App\Enums\StatusMateri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Materi pembelajaran | DFD D2.1.
 * Hanya materi berstatus 'terbit' yang tampil ke siswa dan dicari chatbot.
 *
 * @property int $id
 * @property int $penugasan_guru_id
 * @property string $judul
 * @property string|null $deskripsi
 * @property StatusMateri $status
 * @property-read PenugasanGuru $penugasanGuru
 */
class Materi extends Model
{
    protected $table = 'materi';

    protected $fillable = [
        'penugasan_guru_id',
        'judul',
        'deskripsi',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusMateri::class,
        ];
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    public function penugasanGuru(): BelongsTo
    {
        return $this->belongsTo(PenugasanGuru::class, 'penugasan_guru_id');
    }

    public function berkasMateri(): HasMany
    {
        return $this->hasMany(BerkasMateri::class, 'materi_id');
    }

    // ------------------------------------------------------------------
    // Scope (sama dengan panduan Chatbot Akademik, bagian 6.5)
    // ------------------------------------------------------------------

    public function scopeTerbit(Builder $query): void
    {
        $query->where('status', StatusMateri::Terbit);
    }

    public function scopeDraf(Builder $query): void
    {
        $query->where('status', StatusMateri::Draf);
    }

    /** Siswa: materi pada kelas tempat ia ditempatkan. */
    public function scopeUntukSiswa(Builder $query, User $user): void
    {
        $query->whereHas('penugasanGuru', fn (Builder $p) => $p->where('kelas_id', $user->kelas_id));
    }

    /** Guru: materi pada mapel dan kelas yang ia ampu. */
    public function scopeUntukGuru(Builder $query, User $user): void
    {
        $query->whereHas('penugasanGuru', fn (Builder $p) => $p->where('guru_id', $user->id));
    }

    public function adalahTerbit(): bool
    {
        return $this->status === StatusMateri::Terbit;
    }
}
