<?php

namespace App\Enums;

/**
 * Tahap pengindeksan berkas untuk chatbot (berkas_materi.status_indeks).
 *
 *   menunggu -> diproses -> terindeks | gagal
 *
 * Mengganti file PDF mengembalikan status ke 'menunggu'. Chatbot hanya
 * mencari pada berkas 'terindeks'.
 */
enum StatusIndeks: string
{
    case Menunggu = 'menunggu';
    case Diproses = 'diproses';
    case Terindeks = 'terindeks';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Diproses => 'Diproses',
            self::Terindeks => 'Terindeks',
            self::Gagal => 'Gagal',
        };
    }
}
