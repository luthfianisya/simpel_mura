<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah peran pejabat PPSPM, setara PPK & Bendahara (bisa mengisi Monitoring SPJ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai_mitra', function (Blueprint $table) {
            $table->enum('peran_pejabat', ['kepala_satker', 'ppk', 'ppspm', 'bendahara'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pegawai_mitra', function (Blueprint $table) {
            $table->enum('peran_pejabat', ['kepala_satker', 'ppk', 'bendahara'])->nullable()->change();
        });
    }
};
