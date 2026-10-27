<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel materi (Model Materi | DFD D2.1).
 * Hanya materi berstatus 'terbit' yang tampil ke siswa dan dicari chatbot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_guru_id')
                ->constrained('penugasan_guru')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['draf', 'terbit'])->default('draf');
            $table->timestamps();

            $table->index(['penugasan_guru_id', 'status'], 'materi_penugasan_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
