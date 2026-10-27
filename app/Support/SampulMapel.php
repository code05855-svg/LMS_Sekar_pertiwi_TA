<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Gambar sampul mata pelajaran. Dicocokkan dari nama mapel; mapel tanpa
 * gambar memakai gradasi warna dan inisial (lihat komponen x-sampul-mapel).
 */
class SampulMapel
{
    /** @var array<string, array<int, string>> berkas => kata kunci pada nama mapel */
    private const PETA = [
        'matematika' => ['matematika'],
        'bahasa-indonesia' => ['indonesia'],
        'bahasa-inggris' => ['inggris', 'english'],
        'ipa' => ['ipa', 'ilmu pengetahuan alam', 'sains'],
        'ips' => ['ips', 'ilmu pengetahuan sosial'],
        'informatika' => ['informatika', 'komputer', 'tik'],
    ];

    /** Warna gradasi cadangan (kelas Tailwind lengkap agar ikut terkompilasi). */
    private const GRADASI = [
        'from-navy to-navy-700',
        'from-danger to-danger-700',
        'from-success to-[#157553]',
        'from-gold-600 to-gold',
        'from-[#3b4f9a] to-navy',
    ];

    public static function url(string $namaMapel): ?string
    {
        $nama = Str::lower($namaMapel);

        foreach (self::PETA as $berkas => $kataKunci) {
            foreach ($kataKunci as $kata) {
                if (preg_match('/\b'.preg_quote($kata, '/').'\b/u', $nama)) {
                    return asset("images/mapel/{$berkas}.jpg");
                }
            }
        }

        return null;
    }

    public static function gradasi(string $namaMapel): string
    {
        return self::GRADASI[crc32($namaMapel) % count(self::GRADASI)];
    }

    public static function inisial(string $namaMapel): string
    {
        return Str::upper(Str::substr(preg_replace('/[^\p{L}\p{N}]/u', '', $namaMapel), 0, 2));
    }
}
