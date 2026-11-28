<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Isian per bagian (tipe, moda, dari, ke, lokasi, PP, tanggal) yang menyusun uraian
// Pengeluaran Riil di wizard, supaya kartunya bisa dibuka lagi dalam bentuk yang sama.
// Kolom "uraian" tetap menyimpan kalimat jadinya (dipakai dokumen). Null = ditulis bebas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran_riil', function (Blueprint $table) {
            $table->text('susunan')->nullable()->after('uraian');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_riil', function (Blueprint $table) {
            $table->dropColumn('susunan');
        });
    }
};
