<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback/kendala sistem dari semua akun. Administrator mengisi tindak lanjut (solusi)
 * dan menandai apakah sudah selesai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id('id_feedback');
            $table->foreignId('id_user')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kategori');
            $table->string('judul');
            $table->text('isi');

            $table->boolean('selesai')->default(false);
            $table->text('tindak_lanjut')->nullable();
            $table->foreignId('ditindaklanjuti_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditindaklanjuti_pada')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
