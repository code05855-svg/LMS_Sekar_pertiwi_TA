<?php

namespace Database\Factories;

use App\Enums\PeranPengguna;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Password yang dipakai factory (di-hash sekali agar pembuatan massal cepat).
     */
    protected static ?string $password;

    /**
     * Bawaan: siswa tanpa kelas. Pakai state admin(), guru(), atau siswa().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_induk' => fake()->unique()->numerify('##########'),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'peran' => PeranPengguna::Siswa,
            'kelas_id' => null,
            'aktif' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'peran' => PeranPengguna::Admin,
            'kelas_id' => null,
        ]);
    }

    public function guru(): static
    {
        return $this->state(fn () => [
            'peran' => PeranPengguna::Guru,
            'kelas_id' => null,
        ]);
    }

    /** Siswa, opsional langsung ditempatkan ke sebuah kelas. */
    public function siswa(Kelas|int|null $kelas = null): static
    {
        return $this->state(fn () => [
            'peran' => PeranPengguna::Siswa,
            'kelas_id' => $kelas instanceof Kelas ? $kelas->id : $kelas,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
