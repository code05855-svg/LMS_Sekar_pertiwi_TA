<?php
 
namespace App\Models;
 
use App\Enums\JenisJawaban;
use App\Enums\StatusPengumpulan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property array<int, string> $jenis_jawaban  Jenis jawaban yang diterima (dokumen, foto, tautan, teks)
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
        'jenis_jawaban',
    ];
 
    protected function casts(): array
    {
        return [
            'tenggat' => 'datetime',
            'nilai_maksimum' => 'integer',
        ];
    }
 
    /**
     * Jenis jawaban yang diterima. Di basis data disimpan sebagai teks dipisah
     * koma ("dokumen,foto"); di PHP berupa array. Kosong/tidak valid = semua jenis.
     */
    protected function jenisJawaban(): Attribute
    {
        return Attribute::make(
            get: function (?string $nilai) {
                $daftar = array_values(array_intersect(JenisJawaban::nilaiSemua(), explode(',', (string) $nilai)));
 
                return $daftar ?: JenisJawaban::nilaiSemua();
            },
            set: fn (array|string|null $nilai) => implode(',', array_values(array_intersect(
                JenisJawaban::nilaiSemua(),
                is_array($nilai) ? $nilai : explode(',', (string) $nilai)
            ))) ?: implode(',', JenisJawaban::nilaiSemua()),
        );
    }
 
    public function menerima(JenisJawaban|string $jenis): bool
    {
        $nilai = $jenis instanceof JenisJawaban ? $jenis->value : $jenis;
 
        return in_array($nilai, $this->jenis_jawaban, true);
    }
 
    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------
 
    public function lampiran(): HasMany
    {
        return $this->hasMany(LampiranTugas::class, 'tugas_id')->orderBy('id');
    }
 
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
