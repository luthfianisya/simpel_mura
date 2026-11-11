<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tanggal penandatanganan Laporan. Null = pakai tanggal otomatis
        // (1 hari kerja setelah tanggal selesai pelaksanaan).
        Schema::table('laporan_perjadin', function (Blueprint $table) {
            $table->date('tanggal_laporan')->nullable()->after('tindak_lanjut');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_perjadin', function (Blueprint $table) {
            $table->dropColumn('tanggal_laporan');
        });
    }
};
