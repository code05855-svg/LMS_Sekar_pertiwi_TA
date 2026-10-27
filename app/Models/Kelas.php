<?php

namespace App\Models;

use App\Enums\PeranPengguna;
use App\Enums\TingkatKelas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kelas / rombel | DFD D1.2.
 *
 * @property int $id
 * @property TingkatKelas $tingkat
 * @property string $nama           Mis. "VII A"
 * @property string $tahun_ajaran   Mis. "2026/2027"
 */
class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = [
        'tingkat',
        'nama',
        'tahun_ajaran',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => TingkatKelas::class,
        ];
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    /** Siswa yang ditempatkan di kelas ini (users.kelas_id, peran siswa). */
    public function siswa(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_id')
            ->where('peran', PeranPengguna::Siswa);
    }

    /** Guru pengampu dan mapel pada kelas ini. */
    public function penugasanGuru(): HasMany
    {
        return $this->hasMany(PenugasanGuru::class, 'kelas_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    public function scopeTahunAjaran(Builder $query, string $tahunAjaran): void
    {
        $query->where('tahun_ajaran', $tahunAjaran);
    }

    /** Urut VII, VIII, IX (urutan ENUM di database), lalu nama. */
    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('tingkat')->orderBy('nama');
    }
}
