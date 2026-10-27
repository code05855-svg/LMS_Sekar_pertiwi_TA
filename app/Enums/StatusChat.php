<?php

namespace App\Enums;

/**
 * Hasil satu tanya-jawab chatbot (riwayat_chat.status).
 * 'gagal' = n8n tidak terhubung atau Gemini gagal (jawaban cadangan).
 */
enum StatusChat: string
{
    case Berhasil = 'berhasil';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Berhasil => 'Berhasil',
            self::Gagal => 'Gagal',
        };
    }
}
