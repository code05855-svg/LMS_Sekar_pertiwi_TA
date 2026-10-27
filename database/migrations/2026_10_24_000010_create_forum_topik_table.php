<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel forum_topik (Model ForumTopik | DFD D4.1).
 *
 * Satu forum per kelas+mapel (lewat penugasan_guru_id). Teks saja.
 * disematkan / dikunci diatur guru (proses 5.4). Tidak diindeks chatbot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_topik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_guru_id')
                ->constrained('penugasan_guru')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->index()->comment('Pembuat topik (guru atau siswa)')
                ->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('judul', 200);
            $table->text('isi');
            $table->boolean('disematkan')->default(false);
            $table->boolean('dikunci')->default(false);
            $table->timestamps();

            $table->index(['penugasan_guru_id', 'disematkan', 'created_at'], 'forum_topik_penugasan_urut_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_topik');
    }
};
