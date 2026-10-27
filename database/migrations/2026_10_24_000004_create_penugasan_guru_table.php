<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel penugasan_guru (Model PenugasanGuru | DFD D1.4).
 *
 * Guru mengampu satu mapel pada satu kelas. Satu pasangan kelas+mapel hanya
 * memiliki satu guru pengampu. Materi, tugas, dan forum menempel pada baris
 * ini. Aplikasi memastikan users.peran = 'guru'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->index()
                ->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('kelas_id')
                ->constrained('kelas')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('mata_pelajaran_id')->index()
                ->constrained('mata_pelajaran')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['kelas_id', 'mata_pelajaran_id'], 'penugasan_guru_kelas_mapel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_guru');
    }
};
