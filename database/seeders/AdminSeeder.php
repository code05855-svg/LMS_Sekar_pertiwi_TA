<?php

namespace Database\Seeders;

use App\Enums\PeranPengguna;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Akun admin pertama. Nomor induk dan password dapat diatur lewat .env:
     *
     *   ADMIN_NOMOR_INDUK=admin
     *   ADMIN_PASSWORD=isi-password-sendiri
     *
     * Aman dijalankan berulang: akun yang sudah ada tidak diubah (password
     * yang sudah diganti admin tidak ditimpa).
     */
    public function run(): void
    {
        $nomorInduk = env('ADMIN_NOMOR_INDUK', 'admin');
        $password = env('ADMIN_PASSWORD');

        $admin = User::firstOrCreate(
            ['nomor_induk' => $nomorInduk],
            [
                'nama' => 'Administrator',
                'password' => $password ?? 'password',
                'peran' => PeranPengguna::Admin,
                'aktif' => true,
            ],
        );

        if ($admin->wasRecentlyCreated && $password === null) {
            $this->command?->warn("Admin dibuat dengan password bawaan 'password'. Segera ganti, atau isi ADMIN_PASSWORD di .env.");
        }
    }
}
