<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Penugasan guru: satu guru mengampu satu mapel pada satu kelas | DFD D1.4.
 * Materi, tugas, dan forum menempel pada baris ini.
 *
 * @property int $id
 * @property int $guru_id
 * @property int $kelas_id
 * @property int $mata_pelajaran_id
 * @property-read User $guru
 * @property-read Kelas $kelas
 * @property-read MataPelajaran $mataPelajaran
 * @property-read string $label
 */
class PenugasanGuru extends Model
{
    protected $table = 'penugasan_guru';

    protected $fillable = [
        'guru_id',
        'kelas_id',
        'mata_pelajaran_id',
    ];

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'penugasan_guru_id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'penugasan_guru_id');
    }

    public function forumTopik(): HasMany
    {
        return $this->hasMany(ForumTopik::class, 'penugasan_guru_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    /**
     * Penugasan yang boleh dilihat pengguna: guru melihat yang ia ampu,
     * siswa melihat yang ada di kelasnya (proses 4.1). Admin dan siswa tanpa
     * kelas mendapat hasil kosong.
     */
    public function scopeDiaksesOleh(Builder $query, User $user): void
    {
        match (true) {
            $user->adalahGuru() => $query->where('guru_id', $user->id),
            $user->adalahSiswa() && $user->kelas_id !== null => $query->where('kelas_id', $user->kelas_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    // ------------------------------------------------------------------
    // Hak akses (dipakai Policy dan controller)
    // ------------------------------------------------------------------

    /**
     * Versi satu baris dari scope diaksesOleh(): guru pengampu dan siswa di
     * kelas ini boleh mengakses materi, tugas, dan forum miliknya. Admin
     * tidak (sesuai ruang lingkup Kerangka: admin hanya mengelola data dasar).
     */
    public function bolehDiaksesOleh(User $user): bool
    {
        if ($user->adalahGuru()) {
            return (int) $this->guru_id === (int) $user->id;
        }

        if ($user->adalahSiswa()) {
            return $user->kelas_id !== null
                && (int) $this->kelas_id === (int) $user->kelas_id;
        }

        return false;
    }

    /** Label untuk dropdown/judul, mis. "IPA - VII A". */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => $this->mataPelajaran->nama.' - '.$this->kelas->nama);
    }
}
