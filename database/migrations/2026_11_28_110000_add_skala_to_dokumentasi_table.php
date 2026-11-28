<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ukuran tampil foto dokumentasi di Laporan (persen dari ukuran penuh, 30–100). Rasio
// foto tetap terkunci; mengecilkan foto membuat lebih banyak foto muat di satu lembar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->unsignedTinyInteger('skala')->default(100)->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->dropColumn('skala');
        });
    }
};
