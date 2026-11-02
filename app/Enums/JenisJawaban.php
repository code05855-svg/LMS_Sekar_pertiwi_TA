<?php

namespace App\Enums;

enum JenisJawaban: string
{
    case Dokumen = 'dokumen';
    case Foto = 'foto';
    case Tautan = 'tautan';
    case Teks = 'teks';

    public function label(): string
    {
        return match ($this) {
            self::Dokumen => 'Dokumen',
            self::Foto => 'Foto',
            self::Tautan => 'Tautan (link)',
            self::Teks => 'Teks',
        };
    }

    // nama ikon pada komponen x-ikon
    public function ikon(): string
    {

        return match ($this) {
            self::Dokumen => 'dokumen',
            self::Foto => 'foto',
            self::Tautan => 'tautan',
            self::Teks => 'teks',
        };
    }

    public function berkas(): bool
    {
        return match ($this) {
            self::Dokumen, self::Foto => true,
            self::Tautan, self::Teks => false,
        };
    }

    public static function nilaiSemua(): array
    {
        return array_column(self::cases(), 'value');
    }
}
