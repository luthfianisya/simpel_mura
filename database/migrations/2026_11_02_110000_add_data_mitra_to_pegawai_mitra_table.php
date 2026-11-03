<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data khusus mitra statistik: Sobat ID, alamat, dan peran mitra (pendataan saja atau
 * pendataan & pengolahan). Kolom pegawai (NIP, pangkat, golongan, peran pejabat) tidak
 * dipakai untuk mitra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai_mitra', function (Blueprint $table) {
            $table->string('sobat_id')->nullable()->after('nip');
            $table->text('alamat')->nullable()->after('golongan');
            $table->string('jenis_mitra')->nullable()->after('alamat')->comment('pendataan / pendataan_pengolahan');
        });
    }

    public function down(): void
    {
        Schema::table('pegawai_mitra', function (Blueprint $table) {
            $table->dropColumn(['sobat_id', 'alamat', 'jenis_mitra']);
        });
    }
};
