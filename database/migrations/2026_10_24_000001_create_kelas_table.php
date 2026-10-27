<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel kelas (Model Kelas | DFD D1.2).
 * Tahun ajaran disimpan sebagai teks, mis. '2026/2027'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->enum('tingkat', ['VII', 'VIII', 'IX'])->comment('Tingkat kelas SMP');
            $table->string('nama', 30)->comment('Nama rombel, mis. VII A');
            $table->string('tahun_ajaran', 9)->comment('Mis. 2026/2027');
            $table->timestamps();

            $table->unique(['nama', 'tahun_ajaran'], 'kelas_nama_tahun_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
