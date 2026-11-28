<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Narasi "Untuk pembayaran" Kuitansi yang disunting sendiri oleh user (mis. dipersingkat
// supaya Kuitansi tetap 1 lembar). Kosong = dibuat otomatis dari Perihal, tujuan & tanggal.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuitansi', function (Blueprint $table) {
            $table->text('uraian_pembayaran')->nullable()->after('tanggal_kuitansi');
        });
    }

    public function down(): void
    {
        Schema::table('kuitansi', function (Blueprint $table) {
            $table->dropColumn('uraian_pembayaran');
        });
    }
};
