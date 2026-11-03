<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penomoran SPD per tahun. nomor_awal = nomor terakhir yang sudah terpakai di luar SIMPEL
 * (sebelum SIMPEL dipakai), jadi nomor dari SIMPEL dimulai dari nomor_awal + 1. Baris per
 * tahun ini juga dipakai sebagai kunci (lockForUpdate) saat mengambil nomor, supaya dua
 * penyimpanan bersamaan tidak mendapat nomor yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penomoran_spd', function (Blueprint $table) {
            $table->id('id_penomoran_spd');
            $table->unsignedSmallInteger('tahun')->unique();
            $table->unsignedInteger('nomor_awal')->default(0);
            $table->timestamps();
        });

        DB::table('penomoran_spd')->insert([
            'tahun' => 2026,
            'nomor_awal' => 271,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('penomoran_spd');
    }
};
