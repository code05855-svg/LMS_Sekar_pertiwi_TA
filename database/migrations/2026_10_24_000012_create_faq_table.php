<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel faq (Model Faq | DFD D5.2).
 * FAQ dasar penggunaan LMS; diisi lewat FaqSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan');
            $table->text('jawaban');
            $table->string('kata_kunci')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            // opsional: menyaring FAQ bila daftarnya sudah panjang
            $table->fullText(['pertanyaan', 'kata_kunci'], 'faq_pencarian_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq');
    }
};
