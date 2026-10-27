<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel users (Model User | DFD D1.1) beserta password_reset_tokens dan
 * sessions bawaan Laravel.
 *
 * Migration ini MENGGANTIKAN 0001_01_01_000000_create_users_table.php bawaan
 * Laravel (hapus berkas bawaan itu). Alasannya: users.kelas_id mengacu ke
 * tabel kelas, sehingga kelas harus dibuat lebih dulu.
 *
 * peran     : admin / guru / siswa
 * kelas_id  : hanya diisi untuk siswa (penempatan siswa ke kelas)
 * nomor_induk dipakai untuk login (NIS siswa, NIP guru, kode admin).
 * Hapus akun lewat kolom `aktif` (nonaktifkan), bukan DELETE, agar riwayat
 * tugas dan forum tetap utuh (FK lain memakai RESTRICT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_induk', 30)->unique()->comment('NIS / NIP / kode admin, dipakai untuk login');
            $table->string('nama', 100);
            $table->string('email', 100)->nullable()->unique();
            $table->string('password')->comment('Hash bcrypt dari Laravel');
            $table->enum('peran', ['admin', 'guru', 'siswa'])->index();
            $table->foreignId('kelas_id')->nullable()->index()->comment('Hanya untuk siswa')
                ->constrained('kelas')->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('aktif')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
