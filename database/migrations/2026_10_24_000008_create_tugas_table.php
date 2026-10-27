<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel tugas (Model Tugas | DFD D3.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_guru_id')
                ->constrained('penugasan_guru')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('judul', 200);
            $table->text('instruksi')->nullable();
            $table->dateTime('tenggat');
            $table->unsignedSmallInteger('nilai_maksimum')->default(100);
            $table->timestamps();

            $table->index(['penugasan_guru_id', 'tenggat'], 'tugas_penugasan_tenggat_index');
        });

        // Laravel belum punya Blueprint untuk CHECK, jadi ditambahkan manual.
        // Didukung MySQL 8.0.16+ dan MariaDB 10.2+ (Laragon memakai MySQL 8).
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE tugas ADD CONSTRAINT tugas_nilai_maksimum_check CHECK (nilai_maksimum > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
