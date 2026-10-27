<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel bagian_materi (Model BagianMateri | DFD D5.1).
 *
 * Potongan teks hasil ekstraksi dokumen, dibagi per halaman/bagian. Dicari
 * chatbot dengan FULLTEXT (proses 6.3). FULLTEXT membutuhkan MySQL 8 atau
 * MariaDB (tidak didukung SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagian_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_materi_id')
                ->constrained('berkas_materi')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('nomor_bagian');
            $table->unsignedInteger('halaman')->nullable();
            $table->mediumText('teks');
            $table->timestamps();

            $table->unique(['berkas_materi_id', 'nomor_bagian'], 'bagian_materi_berkas_nomor_unique');
            $table->fullText('teks'); // sumber pencarian chatbot
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagian_materi');
    }
};
