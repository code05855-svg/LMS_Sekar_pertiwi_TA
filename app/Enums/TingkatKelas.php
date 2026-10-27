<?php

namespace App\Enums;

/**
 * Tingkat kelas SMP pada kolom kelas.tingkat.
 */
enum TingkatKelas: string
{
    case VII = 'VII';
    case VIII = 'VIII';
    case IX = 'IX';

    public function label(): string
    {
        return 'Kelas '.$this->value;
    }
}
