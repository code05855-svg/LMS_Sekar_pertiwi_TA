<?php

namespace App\Support;

/** Pemformat tampilan yang dipakai banyak view (tanpa ekstensi intl). */
class Format
{
    /** 1536 -> "1,5 KB". */
    public static function ukuran(?int $byte): string
    {
        $byte = max(0, (int) $byte);

        if ($byte < 1024) {
            return $byte.' B';
        }

        $satuan = ['KB', 'MB', 'GB'];
        $nilai = $byte / 1024;
        $i = 0;

        while ($nilai >= 1024 && $i < count($satuan) - 1) {
            $nilai /= 1024;
            $i++;
        }

        return number_format($nilai, $nilai < 10 ? 1 : 0, ',', '.').' '.$satuan[$i];
    }

    /** "85.00" -> "85", "87.50" -> "87,5". */
    public static function nilai(int|float|string|null $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $nilai, 2, ',', ''), '0'), ',');
    }
}
