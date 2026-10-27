<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel riwayat_chat (Model RiwayatChat | DFD D6).
 *
 * Satu baris = satu tanya-jawab. sesi_id = UUID satu percakapan (disimpan di
 * session Laravel, dipakai ulang sampai pengguna memulai percakapan baru).
 * sumber (JSON) berisi rujukan materi yang dipakai jawaban, mis.
 *   [{"materi_id":5,"materi":"Fotosintesis","mapel":"IPA",
 *     "berkas_id":12,"berkas":"fotosintesis.pdf","halaman":3}]
 * status = 'gagal' bila n8n tidak terhubung atau Gemini gagal (jawaban
 * cadangan). Baris tetap disimpan, tetapi tidak ikut rata-rata waktu respons.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->uuid('sesi_id')->index()->comment('UUID sesi percakapan');
            $table->text('pertanyaan');
            $table->mediumText('jawaban')->nullable();
            $table->json('sumber')->nullable();
            $table->unsignedInteger('token_masuk')->nullable();
            $table->unsignedInteger('token_keluar')->nullable();
            $table->unsignedInteger('waktu_respons_ms')->nullable();
            $table->enum('status', ['berhasil', 'gagal'])->default('berhasil');
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'riwayat_chat_user_waktu_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_chat');
    }
};
