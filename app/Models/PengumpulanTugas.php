<?php

namespace App\Models;

use App\Enums\StatusPengumpulan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Berkas jawaban tugas milik siswa | DFD D3.2.
 * Tidak pernah diindeks chatbot.
 *
 * nilai, komentar, dan dinilai_pada SENGAJA tidak ada di $fillable: hanya
 * guru yang mengubahnya, lewat beriNilai(). Dengan begitu pengumpulan dari
 * siswa tidak bisa menyisipkan nilai sendiri.
 *
 * @property int $id
 * @property int $tugas_id
 * @property int $siswa_id
 * @property string $nama_asli
 * @property string $path
 * @property string $mime
 * @property int $ukuran
 * @property \Illuminate\Support\Carbon $waktu_kumpul
 * @property StatusPengumpulan $status
 * @property string|null $nilai          Desimal 2 angka, mis. "85.00"
 * @property string|null $komentar
 * @property \Illuminate\Support\Carbon|null $dinilai_pada
 * @property-read Tugas $tugas
 * @property-read User $siswa
 */
class PengumpulanTugas extends Model
{
    protected $table = 'pengumpulan_tugas';

    protected $fillable = [
        'tugas_id',
        'siswa_id',
        'nama_asli',
        'path',
        'mime',
        'ukuran',
        'waktu_kumpul',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
            'waktu_kumpul' => 'datetime',
            'status' => StatusPengumpulan::class,
            'nilai' => 'decimal:2',
            'dinilai_pada' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    public function scopeSudahDinilai(Builder $query): void
    {
        $query->whereNotNull('nilai');
    }

    public function scopeBelumDinilai(Builder $query): void
    {
        $query->whereNull('nilai');
    }

    public function scopeMilikSiswa(Builder $query, User $siswa): void
    {
        $query->where('siswa_id', $siswa->id);
    }

    // ------------------------------------------------------------------
    // Penilaian (proses 3.6)
    // ------------------------------------------------------------------

    /** Dinamai telahDinilai() karena sudahDinilai() sudah dipakai sebagai scope. */
    public function telahDinilai(): bool
    {
        return $this->nilai !== null;
    }

    /**
     * Simpan nilai dan komentar guru. Nilai harus 0 sampai nilai_maksimum
     * tugas (UpdateNilaiRequest memeriksa hal yang sama untuk pesan galat
     * yang ramah; pemeriksaan ini sebagai pengaman terakhir).
     */
    public function beriNilai(int|float|string $nilai, ?string $komentar = null): bool
    {
        $maksimum = $this->tugas->nilai_maksimum;

        if (! is_numeric($nilai) || $nilai < 0 || $nilai > $maksimum) {
            throw new InvalidArgumentException("Nilai harus berupa angka 0 sampai {$maksimum}.");
        }

        return $this->forceFill([
            'nilai' => $nilai,
            'komentar' => $komentar,
            'dinilai_pada' => now(),
        ])->save();
    }
}
