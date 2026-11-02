<?php
 
namespace App\Services;
 
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
 
/**
 * Menyimpan foto profil: dipotong persegi dari tengah, diperkecil ke 384 x 384
 * piksel, dan disimpan ulang sebagai JPEG. Menyimpan ulang membuang data EXIF
 * (termasuk lokasi GPS) dan kode apa pun yang terselip di dalam gambar.
 * Membutuhkan ekstensi GD (aktif bawaan di Laragon).
 */
class FotoProfilService
{
    private const SISI = 384;
 
    /** Cadangan memori (byte) di luar buffer gambar: gambar hasil, encoder JPEG, dan kerangka Laravel. */
    private const CADANGAN_MEMORI = 32 * 1024 * 1024;
 
    /**
     * Apakah memori PHP cukup untuk membuka gambar berukuran ini? GD memegang 4 byte per piksel
     * (ditambah sedikit ruang kerja dekoder), jadi foto kamera beresolusi tinggi bisa menghabiskan
     * memory_limit (bawaan Laragon 128M) dan membuat halaman galat 500. Dicek sebelum diproses
     * supaya pengguna mendapat pesan yang jelas. memory_limit = -1 berarti tanpa batas.
     */
    public static function cukupMemori(int $lebar, int $tinggi, ?int $batas = null, ?int $terpakai = null): bool
    {
        $batas ??= self::batasMemori();
 
        if ($batas === null) {
            return true;
        }
 
        $terpakai ??= memory_get_usage(true);
 
        return ($lebar * $tinggi * 5 + self::CADANGAN_MEMORI) <= ($batas - $terpakai);
    }
 
    /** memory_limit PHP dalam byte, atau null bila tanpa batas. */
    public static function batasMemori(): ?int
    {
        $nilai = ini_parse_quantity((string) ini_get('memory_limit'));
 
        return $nilai > 0 ? $nilai : null;
    }
 
    public function simpan(User $user, UploadedFile $berkas): void
    {
        $disk = Storage::disk('foto_profil');
        $nama = $user->id.'-'.Str::uuid().'.jpg';
 
        $disk->put($nama, $this->olah($berkas->getRealPath()));
 
        $lama = $user->foto_profil;
        $user->forceFill(['foto_profil' => $nama])->save();
 
        if ($lama) {
            $disk->delete($lama);
        }
    }
 
    public function hapus(User $user): void
    {
        if ($user->foto_profil) {
            Storage::disk('foto_profil')->delete($user->foto_profil);
            $user->forceFill(['foto_profil' => null])->save();
        }
    }
 
    private function olah(string $path): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Ekstensi PHP GD belum aktif (php.ini: extension=gd).');
        }
 
        $info = @getimagesize($path);
        $tipe = $info[2] ?? null;
 
        $gambar = match ($tipe) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };
 
        if (! $gambar) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }
 
        $lebar = imagesx($gambar);
        $tinggi = imagesy($gambar);
        $sisi = min($lebar, $tinggi);
 
        // Pangkas persegi dari tengah dan perkecil DULU, lalu buang gambar besarnya. Putaran EXIF
        // (kelipatan 90 derajat) dilakukan pada gambar kecil: hasilnya sama dengan memutar gambar
        // penuh lalu memangkas, tetapi memori yang dipakai hanya satu buffer besar, bukan dua.
        $hasil = imagecreatetruecolor(self::SISI, self::SISI);
        imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255)); // latar putih untuk PNG transparan
        imagecopyresampled(
            $hasil, $gambar,
            0, 0, intdiv($lebar - $sisi, 2), intdiv($tinggi - $sisi, 2),
            self::SISI, self::SISI, $sisi, $sisi
        );
        unset($gambar);
 
        // Foto dari ponsel sering menyimpan arah putar di EXIF; terapkan agar tegak.
        if ($tipe === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $arah = @exif_read_data($path)['Orientation'] ?? 1;
            $sudut = [3 => 180, 6 => -90, 8 => 90][$arah] ?? 0;
 
            if ($sudut !== 0 && ($putar = imagerotate($hasil, $sudut, 0))) {
                $hasil = $putar;
            }
        }
 
        ob_start();
        imagejpeg($hasil, null, 85);
        $jpeg = (string) ob_get_clean();
 
        return $jpeg;
    }
}
