<?php
 
namespace App\Enums;
 

enum KategoriInformasi: string
{
    case Pengumuman = 'pengumuman';
    case Berita = 'berita';
    case Prestasi = 'prestasi';
 
    public function label(): string
    {
        return match ($this) {
            self::Pengumuman => 'Pengumuman',
            self::Berita => 'Berita',
            self::Prestasi => 'Prestasi',
        };
    }
    public function lencana(): string
    {
        return match ($this) {
            self::Pengumuman => 'badge-danger',
            self::Berita => 'badge-info',
            self::Prestasi => 'badge-gold',
        };
    }
 
    /** @return array<int, string> */
    public static function nilaiSemua(): array
    {
        return array_column(self::cases(), 'value');
    }
}
