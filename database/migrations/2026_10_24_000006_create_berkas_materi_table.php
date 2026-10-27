<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel berkas_materi (Model BerkasMateri | DFD D2.2).
 *
 * Berkas pembelajaran (PDF) milik materi. Berkas disimpan lewat Laravel
 * Filesystem (disk privat 'materi'); kolom path berisi lokasinya.
 *
 * status_indeks = tahap pengindeksan untuk chatbot (proses 6.4):
 *   menunggu -> diproses -> terindeks | gagal
 * File PDF diganti guru => kembali ke 'menunggu'. Chatbot hanya mencari di
 * berkas berstatus 'terindeks'. pesan_galat berisi sebab bila 'gagal';
 * diindeks_pada berisi waktu indeks terakhir yang berhasil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->index()
                ->constrained('materi')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('nama_asli');
            $table->string('path', 500);
            $table->string('mime', 100);
            $table->unsignedBigInteger('ukuran')->comment('Ukuran dalam byte');
            $table->unsignedInteger('jumlah_halaman')->nullable();
            $table->enum('status_indeks', ['menunggu', 'diproses', 'terindeks', 'gagal'])->default('menunggu');
            $table->string('pesan_galat')->nullable()->comment('Sebab gagal indeks; NULL bila tidak gagal');
            $table->timestamp('diindeks_pada')->nullable();
            $table->timestamps();

            $table->index('status_indeks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas_materi');
    }
};
