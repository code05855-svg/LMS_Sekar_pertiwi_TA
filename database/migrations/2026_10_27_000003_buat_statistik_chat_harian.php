<?php 

use illuminate\Database\Migrations\Migration;
use illuminate\Database\Schema\Blueprint;
use illuminate\Support\Facades\Schema;


return new class extends Migration 
{
    public function up(): void
    {
        if (! Schema::hasTable('statistik_chat_harian')) {
            Schema::create('statistik_chat_harian', function (Blueprint $table){
                $table->date('tanggal')->primary();
                $table->unsignedInteger('jumlah')->default(0);
                $table->unsignedInteger('berhasil')->default(0);
                $table->unsignedInteger('gagal')->default(0);
                $table->unsignedBigInteger('token_masuk')->default(0);
                $table->unsignedBigInteger('token_keluar')->default(0);
                $table->unsignedBigInteger('total_waktu_ms')->default(0)->comment('Jumlah waktu respons, hanya yang berhasil');
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void 
    {
        Schema::table('riwayat_chat', function (Blueprint $table) {
            $table->dropIndex('riwayat_chat_created_at_index');
        });

        Schema::dropIfExists('statistik_chat_harian');
    }
};