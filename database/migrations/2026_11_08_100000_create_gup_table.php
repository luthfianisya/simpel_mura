<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kelompok GUP: PPK/PPSPM/Bendahara mengelompokkan SPJ ke dalam satu GUP (dulu ditandai
 * garis hijau di spreadsheet). Nomor "GUP ke-n" tidak disimpan — dihitung per tahun dari
 * urutan tanggal (lihat Gup::denganNomor()).
 *
 * Data lama (Jenis Pembayaran GUP + Tanggal GUP yang sama) dikonversi jadi satu GUP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gup', function (Blueprint $table) {
            $table->id('id_gup');
            $table->date('tanggal_gup')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('monitoring_spj', function (Blueprint $table) {
            $table->foreignId('id_gup')->nullable()->after('id_perjalanan_dinas')
                ->constrained('gup', 'id_gup')->nullOnDelete();
        });

        $tanggalLama = DB::table('monitoring_spj')
            ->where('jenis_pembayaran', 'GUP')
            ->whereNotNull('tanggal_gup')
            ->distinct()
            ->orderBy('tanggal_gup')
            ->pluck('tanggal_gup');

        foreach ($tanggalLama as $tanggal) {
            $idGup = DB::table('gup')->insertGetId([
                'tanggal_gup' => $tanggal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('monitoring_spj')
                ->where('jenis_pembayaran', 'GUP')
                ->where('tanggal_gup', $tanggal)
                ->update(['id_gup' => $idGup]);
        }
    }

    public function down(): void
    {
        Schema::table('monitoring_spj', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_gup');
        });
        Schema::dropIfExists('gup');
    }
};
