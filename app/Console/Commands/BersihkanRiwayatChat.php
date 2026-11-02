<?php
 
namespace App\Console\Commands;
 
use App\Services\PembersihRiwayatChat;
use Illuminate\Console\Command;
 
class BersihkanRiwayatChat extends Command
{
    protected $signature = 'chat:bersihkan {--hari= : Umur sesi dalam hari (bawaan CHAT_RETENSI_HARI, 30)} {--uji : Hanya hitung, tidak menghapus}';
 
    protected $description = 'Hapus riwayat chat yang sesinya sudah tidak aktif lebih dari N hari (ringkasannya disimpan)';
 
    public function handle(PembersihRiwayatChat $pembersih): int
    {
        $hari = $this->option('hari') !== null ? (int) $this->option('hari') : null;
        $uji = (bool) $this->option('uji');
 
        $hasil = $pembersih->bersihkan($hari, $uji);
 
        $this->info(($uji ? 'Akan dihapus: ' : 'Dihapus: ')."{$hasil['sesi']} sesi, {$hasil['baris']} pertanyaan.");
 
        return self::SUCCESS;
    }
}
