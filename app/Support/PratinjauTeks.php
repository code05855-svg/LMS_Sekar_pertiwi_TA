<?php
 
namespace App\Support;
 
use Illuminate\Support\Facades\Storage;
use ZipArchive;
 
final class PratinjauTeks
{
    private const BATAS_KARAKTER = 20000;
 
    private const BATAS_BERKAS_TXT = 300000;
 
    private const BATAS_XML_DOCX = 10 * 1024 * 1024;
 
    public static function bisa(?string $namaAsli): bool
    {
        return in_array(self::ekstensi($namaAsli), ['txt', 'docx'], true);
    }
 
    /** @return array{teks: string, dipotong: bool}|null */
    public static function dari(string $disk, ?string $path, ?string $namaAsli): ?array
    {
        if (! $path || ! self::bisa($namaAsli) || ! Storage::disk($disk)->exists($path)) {
            return null;
        }
 
        $mutlak = Storage::disk($disk)->path($path);
 
        $teks = self::ekstensi($namaAsli) === 'docx' ? self::dariDocx($mutlak) : self::dariTxt($mutlak);
 
        if ($teks === null) {
            return null;
        }
 
        $teks = trim(preg_replace("/\n{3,}/", "\n\n", $teks));
        $dipotong = mb_strlen($teks) > self::BATAS_KARAKTER;
 
        return [
            'teks' => $dipotong ? mb_substr($teks, 0, self::BATAS_KARAKTER) : $teks,
            'dipotong' => $dipotong,
        ];
    }
 
    private static function dariTxt(string $mutlak): ?string
    {
        $isi = @file_get_contents($mutlak, false, null, 0, self::BATAS_BERKAS_TXT);
 
        if ($isi === false) {
            return null;
        }
 
        return mb_check_encoding($isi, 'UTF-8') ? $isi : mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
    }
 
    private static function dariDocx(string $mutlak): ?string
    {
        $zip = new ZipArchive();
 
        if ($zip->open($mutlak) !== true) {
            return null;
        }
 
        try {
            $info = $zip->statName('word/document.xml');
 
            if ($info === false || $info['size'] > self::BATAS_XML_DOCX) {
                return null;
            }
 
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }
 
        if ($xml === false) {
            return null;
        }
 
        $xml = preg_replace('/<w:tab\b[^>]*\/>/', "\t", $xml);
        $xml = preg_replace('/<w:(?:br|cr)\b[^>]*\/>/', "\n", $xml);
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
 
        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
 
    private static function ekstensi(?string $namaAsli): string
    {
        return strtolower(pathinfo((string) $namaAsli, PATHINFO_EXTENSION));
    }
}
