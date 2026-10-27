<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pengumpulan_tugas (Model PengumpulanTugas | DFD D3.2).
 *
 * Satu siswa mengumpulkan satu berkas per tugas. Berkas jawaban TIDAK
 * diindeks chatbot. status dihitung saat pengumpulan: waktu_kumpul
 * dibandingkan dengan tugas.tenggat. nilai diisi guru (proses 3.6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')
                ->constrained('tugas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('siswa_id')->index()
                ->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('nama_asli');
            $table->string('path', 500);
            $table->string('mime', 100);
            $table->unsignedBigInteger('ukuran')->comment('Ukuran dalam byte');
            $table->dateTime('waktu_kumpul');
            $table->enum('status', ['tepat_waktu', 'terlambat']);
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('komentar')->nullable();
            $table->dateTime('dinilai_pada')->nullable();
            $table->timestamps();

            $table->unique(['tugas_id', 'siswa_id'], 'pengumpulan_tugas_tugas_siswa_unique');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE pengumpulan_tugas ADD CONSTRAINT pengumpulan_tugas_nilai_check CHECK (nilai IS NULL OR nilai >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumpulan_tugas');
    }
};
