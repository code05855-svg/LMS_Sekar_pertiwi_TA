<?php
 
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
 
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
 
// Retensi riwayat chat: tiap hari pukul 02.00 (lihat config/lms.php: chatbot.retensi_hari).
// Jadwal ini berjalan bila "php artisan schedule:run" dipanggil tiap menit oleh Task Scheduler.
// Bila tidak, ChatbotController membersihkan sekali per hari saat halaman chatbot dibuka.
Schedule::command('chat:bersihkan')->dailyAt('02:00')->withoutOverlapping();

