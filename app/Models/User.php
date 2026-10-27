<?php

namespace App\Models;

use App\Enums\PeranPengguna;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun pengguna (admin, guru, siswa) | DFD D1.1.
 *
 * Login memakai nomor_induk (NIS / NIP / kode admin), bukan email.
 * kelas_id hanya terisi untuk siswa.
 *
 * @property int $id
 * @property string $nomor_induk
 * @property string $nama
 * @property string|null $email
 * @property string $password
 * @property PeranPengguna $peran
 * @property int|null $kelas_id
 * @property bool $aktif
 * @property-read Kelas|null $kelas
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * Kolom yang boleh diisi massal. Gunakan hanya dengan $request->validated(),
     * jangan $request->all(). ProfilController hanya boleh mengubah password.
     */
    protected $fillable = [
        'nomor_induk',
        'nama',
        'email',
        'password',
        'peran',
        'kelas_id',
        'aktif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'peran' => PeranPengguna::class,
            'aktif' => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // Relasi
    // ------------------------------------------------------------------

    /** Kelas tempat siswa ditempatkan (null untuk admin dan guru). */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /** Guru: daftar kelas+mapel yang diampu. */
    public function penugasanGuru(): HasMany
    {
        return $this->hasMany(PenugasanGuru::class, 'guru_id');
    }

    /** Siswa: berkas tugas yang sudah dikumpulkan. */
    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'siswa_id');
    }

    public function forumTopik(): HasMany
    {
        return $this->hasMany(ForumTopik::class, 'user_id');
    }

    public function forumBalasan(): HasMany
    {
        return $this->hasMany(ForumBalasan::class, 'user_id');
    }

    public function riwayatChat(): HasMany
    {
        return $this->hasMany(RiwayatChat::class, 'user_id');
    }

    // ------------------------------------------------------------------
    // Scope
    // ------------------------------------------------------------------

    public function scopeAktif(Builder $query): void
    {
        $query->where('aktif', true);
    }

    public function scopeBerperan(Builder $query, PeranPengguna $peran): void
    {
        $query->where('peran', $peran);
    }

    public function scopeGuru(Builder $query): void
    {
        $query->where('peran', PeranPengguna::Guru);
    }

    public function scopeSiswa(Builder $query): void
    {
        $query->where('peran', PeranPengguna::Siswa);
    }

    /** Pencarian pada daftar akun admin: nama atau nomor induk. */
    public function scopeCari(Builder $query, ?string $kata): void
    {
        $kata = trim((string) $kata);

        if ($kata === '') {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->where('nama', 'like', "%{$kata}%")
            ->orWhere('nomor_induk', 'like', "%{$kata}%"));
    }

    // ------------------------------------------------------------------
    // Pemeriksaan peran
    // peran adalah enum PeranPengguna, jadi JANGAN membandingkannya dengan
    // string ($user->peran === 'guru' selalu false). Pakai method di bawah.
    // ------------------------------------------------------------------

    public function adalahAdmin(): bool
    {
        return $this->peran === PeranPengguna::Admin;
    }

    public function adalahGuru(): bool
    {
        return $this->peran === PeranPengguna::Guru;
    }

    public function adalahSiswa(): bool
    {
        return $this->peran === PeranPengguna::Siswa;
    }

    /** Dipakai middleware EnsurePeran: punyaPeran(PeranPengguna::Guru, ...). */
    public function punyaPeran(PeranPengguna ...$peran): bool
    {
        return in_array($this->peran, $peran, true);
    }
}
