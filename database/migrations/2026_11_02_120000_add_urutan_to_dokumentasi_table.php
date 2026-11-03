<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Urutan foto dokumentasi per perjalanan dinas (diatur user dengan drag di wizard).
 * Data lama bernilai 0 semua, jadi tetap terurut sesuai id (urutan upload).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->default(0)->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }
};
