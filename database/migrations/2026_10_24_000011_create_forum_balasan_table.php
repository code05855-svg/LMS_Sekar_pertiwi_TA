<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel forum_balasan (Model ForumBalasan | DFD D4.2).
 * Balasan satu tingkat (datar) pada topik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_balasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_topik_id')
                ->constrained('forum_topik')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->index()
                ->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->text('isi');
            $table->timestamps();

            $table->index(['forum_topik_id', 'created_at'], 'forum_balasan_topik_waktu_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_balasan');
    }
};
