<?php

namespace App\Enums;

/**
 * Status materi pada kolom materi.status.
 * Hanya materi 'terbit' yang tampil ke siswa dan dicari chatbot.
 */
enum StatusMateri: string
{
    case Draf = 'draf';
    case Terbit = 'terbit';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Terbit => 'Terbit',
        };
    }
}
