<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai_mitra', function (Blueprint $table) {
            $table->id('id_pegawai_mitra');
            $table->string('nip')->nullable();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('pangkat')->nullable();
            $table->string('golongan')->nullable();
            $table->enum('status_kepegawaian', ['pegawai', 'mitra'])->default('pegawai');
            $table->enum('peran_pejabat', ['kepala_satker', 'ppk', 'bendahara'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_mitra');
    }
};
