<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PeranPengguna;

class SiswaController extends AkunController
{
    protected function peran(): PeranPengguna
    {
        return PeranPengguna::Siswa;
    }

    protected function folder(): string
    {
        return 'siswa';
    }

    protected function labelAkun(): string
    {
        return 'siswa';
    }
}
