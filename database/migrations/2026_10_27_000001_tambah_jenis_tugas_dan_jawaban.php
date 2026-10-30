<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
 
/**
 * Jenis tugas dan jenis jawaban.
 *
 * - tugas.jenis_jawaban      : jenis jawaban yang diterima (dokumen,foto,tautan,teks).
 * - lampiran_tugas           : lampiran dari guru (dokumen, foto, atau tautan) pada sebuah tugas.
 * - pengumpulan_tugas.jenis  : jenis jawaban siswa. Kolom berkas boleh NULL karena
 *                              jawaban berupa tautan atau teks tidak punya berkas.
 *
 * Aman dijalankan walau SQL manual (perbaikan.sql) sudah dijalankan lebih dulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tugas', 'jenis_jawaban')) {
            Schema::table('tugas', function (Blueprint $table) {
                $table->string('jenis_jawaban', 60)->default('dokumen,foto,tautan,teks')->after('nilai_maksimum');
            });
        }
 
        if (! Schema::hasTable('lampiran_tugas')) {
            Schema::create('lampiran_tugas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete()->cascadeOnUpdate();
                $table->enum('jenis', ['dokumen', 'foto', 'tautan']);
                $table->string('judul', 200)->nullable();
                $table->string('nama_asli')->nullable();
                $table->string('path', 500)->nullable();
                $table->string('mime', 100)->nullable();
                $table->unsignedBigInteger('ukuran')->nullable()->comment('Ukuran dalam byte');
                $table->string('tautan', 500)->nullable();
                $table->timestamps();
            });
        }
 
        if (! Schema::hasColumn('pengumpulan_tugas', 'jenis')) {
            Schema::table('pengumpulan_tugas', function (Blueprint $table) {
                $table->enum('jenis', ['dokumen', 'foto', 'tautan', 'teks'])->default('dokumen')->after('siswa_id');
                $table->string('tautan', 500)->nullable()->after('ukuran');
                $table->mediumText('teks')->nullable()->after('tautan');
            });
 
            Schema::table('pengumpulan_tugas', function (Blueprint $table) {
                $table->string('nama_asli')->nullable()->change();
                $table->string('path', 500)->nullable()->change();
                $table->string('mime', 100)->nullable()->change();
                $table->unsignedBigInteger('ukuran')->nullable()->comment('Ukuran dalam byte')->change();
            });
 
            // Jawaban lama semuanya berkas; yang bertipe gambar dianggap foto.
            DB::table('pengumpulan_tugas')->where('mime', 'like', 'image/%')->update(['jenis' => 'foto']);
        }
    }
 
    public function down(): void
    {
        // Jawaban tautan/teks tidak punya berkas, jadi dibuang sebelum kolom berkas diwajibkan lagi.
        DB::table('pengumpulan_tugas')->whereIn('jenis', ['tautan', 'teks'])->delete();
 
        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            $table->string('nama_asli')->nullable(false)->change();
            $table->string('path', 500)->nullable(false)->change();
            $table->string('mime', 100)->nullable(false)->change();
            $table->unsignedBigInteger('ukuran')->nullable(false)->comment('Ukuran dalam byte')->change();
            $table->dropColumn(['jenis', 'tautan', 'teks']);
        });
 
        Schema::dropIfExists('lampiran_tugas');
 
        Schema::table('tugas', function (Blueprint $table) {
            $table->dropColumn('jenis_jawaban');
        });
    }
};
