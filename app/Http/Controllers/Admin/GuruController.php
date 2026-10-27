<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PeranPengguna;

class GuruController extends AkunController
{
    protected function peran(): PeranPengguna
    {
        return PeranPengguna::Guru;
    }

    protected function folder(): string
    {
        return 'guru';
    }

    protected function labelAkun(): string
    {
        return 'guru';
    }
}
