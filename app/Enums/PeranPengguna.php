<?php

namespace App\Enums;

/**
 * Peran akun pada kolom users.peran.
 */
enum PeranPengguna: string
{
    case Admin = 'admin';
    case Guru = 'guru';
    case Siswa = 'siswa';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Guru => 'Guru',
            self::Siswa => 'Siswa',
        };
    }
}
