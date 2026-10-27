<?php

namespace App\Models;

use App\Enums\StatusPengumpulan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tugas dari guru | DFD D3.1.
 *
 * @property int $id
 * @property int $penugasan_guru_id
 * @property string $judul
 * @property string|null $instruksi
 * @property \Illuminate\Support\Carbon $tenggat
 * @property int $nilai_maksimum
 * @property-read PenugasanGuru $penugasanGuru
 */
class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = [
        'penugasan_guru_id',
        'judul',
        'instruksi',
        'tenggat',
        'nilai_maksimum',
    ];

    protected function casts(): array
    {
        return [
            'tenggat' => 'datetime',
            'nilai_maksimum' => 'integer',
        ];
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    public function penugasanGuru(): BelongsTo
    {
        return $this->belongsTo(PenugasanGuru::class, 'penugasan_guru_id');
    }

    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'tugas_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    /** Tugas pada kelas siswa, atau pada mapel+kelas yang diampu guru. */
    public function scopeDiaksesOleh(Builder $query, User $user): void
    {
        $query->whereHas('penugasanGuru', fn (Builder $p) => $p->diaksesOleh($user));
    }

    /** Tugas yang tenggatnya belum lewat. */
    public function scopeBerjalan(Builder $query): void
    {
        $query->where('tenggat', '>=', now());
    }

    /** Tenggat terdekat lebih dulu. */
    public function scopeUrutTenggat(Builder $query): void
    {
        $query->orderBy('tenggat');
    }

    // ------------------------------------------------------------------
    // Status pengumpulan
    // ------------------------------------------------------------------

    /**
     * Status bila tugas dikumpulkan pada $waktu (default: sekarang).
     * Dipakai Siswa/PengumpulanTugasController saat menyimpan.
     */
    public function statusUntuk(?CarbonInterface $waktu = null): StatusPengumpulan
    {
        return StatusPengumpulan::dariWaktu($waktu ?? now(), $this->tenggat);
    }
}
