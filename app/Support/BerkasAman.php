<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mengirim berkas unggahan pengguna dengan aman.
 *
 * Berkas hanya dibuka langsung di peramban (inline) bila jenisnya ada di
 * daftar putih di bawah. Jenis lain, terutama SVG dan HTML yang bisa memuat
 * skrip, selalu diunduh sebagai lampiran. Jenis yang dipakai adalah hasil
 * pembacaan isi berkas saat unggah, bukan ekstensi dari pengguna.
 */
final class BerkasAman
{
    /** @var array<int, string> */
    public const MIME_LANGSUNG = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public static function bisaLangsung(?string $mime): bool
    {
        return in_array($mime, self::MIME_LANGSUNG, true);
    }

    public static function kirim(string $disk, ?string $path, string $namaAsli, ?string $mime, bool $lihat = false): StreamedResponse
    {
        /** @var FilesystemAdapter $penyimpanan */
        $penyimpanan = Storage::disk($disk);

        abort_unless($path && $penyimpanan->exists($path), 404, 'Berkas tidak ditemukan di penyimpanan.');

        $header = ['X-Content-Type-Options' => 'nosniff'];

        if ($lihat && self::bisaLangsung($mime)) {
            return $penyimpanan->response($path, $namaAsli, $header + ['Content-Type' => $mime], 'inline');
        }

        return $penyimpanan->download($path, $namaAsli, $header);
    }
}
