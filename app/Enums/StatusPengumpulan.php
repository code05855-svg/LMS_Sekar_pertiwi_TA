<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Status pengumpulan tugas (pengumpulan_tugas.status).
 */
enum StatusPengumpulan: string
{
    case TepatWaktu = 'tepat_waktu';
    case Terlambat = 'terlambat';

    public function label(): string
    {
        return match ($this) {
            self::TepatWaktu => 'Tepat waktu',
            self::Terlambat => 'Terlambat',
        };
    }

    /**
     * Tepat waktu bila waktu kumpul <= tenggat (sama dengan query [4.4]
     * pada database_lms_sekolah.sql: NOW() <= tugas.tenggat).
     */
    public static function dariWaktu(CarbonInterface $waktuKumpul, CarbonInterface $tenggat): self
    {
        return $waktuKumpul->lessThanOrEqualTo($tenggat) ? self::TepatWaktu : self::Terlambat;
    }
}
