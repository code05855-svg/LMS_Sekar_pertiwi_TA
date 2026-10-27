<?php

namespace App\Models;

use App\Enums\StatusIndeks;
use Illuminate\Database\Eloquent\Builder;
use App\Observers\BerkasMateriObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Berkas pembelajaran (PDF) milik materi | DFD D2.2.
 * Sumber pengindeksan chatbot.
 *
 * Siklus status_indeks (proses 6.4):
 *   menunggu -> diproses -> terindeks | gagal
 * Gunakan method tandai*() agar siklus dan kolom terkait selalu konsisten.
 *
 * @property int $id
 * @property int $materi_id
 * @property string $nama_asli
 * @property string $path
 * @property string $mime
 * @property int $ukuran
 * @property int|null $jumlah_halaman
 * @property StatusIndeks $status_indeks
 * @property string|null $pesan_galat
 * @property \Illuminate\Support\Carbon|null $diindeks_pada
 * @property-read Materi $materi
 */
#[ObservedBy(BerkasMateriObserver::class)]
class BerkasMateri extends Model
{
    protected $table = 'berkas_materi';

    protected $fillable = [
        'materi_id',
        'nama_asli',
        'path',
        'mime',
        'ukuran',
        'jumlah_halaman',
        'status_indeks',
        'pesan_galat',
        'diindeks_pada',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
            'jumlah_halaman' => 'integer',
            'status_indeks' => StatusIndeks::class,
            'diindeks_pada' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class, 'materi_id');
    }

    public function bagianMateri(): HasMany
    {
        return $this->hasMany(BagianMateri::class, 'berkas_materi_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    /** Hanya berkas yang boleh dicari chatbot. */
    public function scopeTerindeks(Builder $query): void
    {
        $query->where('status_indeks', StatusIndeks::Terindeks);
    }

    public function scopeStatus(Builder $query, StatusIndeks $status): void
    {
        $query->where('status_indeks', $status);
    }

    // ------------------------------------------------------------------
    // Siklus status indeks. Memakai updateQuietly agar tidak memicu
    // BerkasMateriObserver (Observer hanya bereaksi pada penggantian file).
    // ------------------------------------------------------------------

    /** Berkas dibuat atau file PDF diganti: sembunyikan indeks lama. */
    public function tandaiMenunggu(): bool
    {
        return $this->updateQuietly([
            'status_indeks' => StatusIndeks::Menunggu,
            'pesan_galat' => null,
            'diindeks_pada' => null,
        ]);
    }

    /** Berkas sudah dikirim ke n8n (Job KirimBerkasKeN8n). */
    public function tandaiDiproses(): bool
    {
        return $this->updateQuietly([
            'status_indeks' => StatusIndeks::Diproses,
            'pesan_galat' => null,
        ]);
    }

    /** n8n selesai menyimpan bagian_materi. */
    public function tandaiTerindeks(?int $jumlahHalaman = null): bool
    {
        return $this->updateQuietly([
            'status_indeks' => StatusIndeks::Terindeks,
            'jumlah_halaman' => $jumlahHalaman ?? $this->jumlah_halaman,
            'pesan_galat' => null,
            'diindeks_pada' => now(),
        ]);
    }

    /**
     * Pengindeksan gagal (PDF hasil scan, tidak terunduh, n8n tidak
     * terhubung). pesan_galat hanya 255 karakter, jadi pesan dipotong agar
     * pesan galat panjang tidak membuat penyimpanan status ikut gagal.
     */
    public function tandaiGagal(string $pesan): bool
    {
        return $this->updateQuietly([
            'status_indeks' => StatusIndeks::Gagal,
            'pesan_galat' => Str::limit($pesan, 250, ''),
            'diindeks_pada' => null,
        ]);
    }
}
