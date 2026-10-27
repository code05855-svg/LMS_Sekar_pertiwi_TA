<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Data awal: akun admin dan FAQ dasar penggunaan LMS.
     * Guru, siswa, kelas, dan mapel dibuat admin lewat aplikasi.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            FaqSeeder::class,
        ]);
    }
}
