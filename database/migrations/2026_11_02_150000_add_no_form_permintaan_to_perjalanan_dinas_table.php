<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor Form Permintaan (FP) kini diisi di Data Umum tiap perjalanan dinas, bukan lagi di
 * Monitoring SPJ. Nomor FP yang sudah terlanjur diisi PPK/Bendahara di monitoring_spj
 * disalin ke sini supaya tidak hilang (kolom lamanya dibiarkan, tidak dipakai lagi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->string('no_form_permintaan')->nullable()->after('pembebanan');
        });

        DB::table('monitoring_spj')
            ->whereNotNull('nomor_fp')
            ->where('nomor_fp', '!=', '')
            ->orderBy('id_monitoring_spj')
            ->each(function ($spj) {
                DB::table('perjalanan_dinas')
                    ->where('id_perjalanan_dinas', $spj->id_perjalanan_dinas)
                    ->whereNull('no_form_permintaan')
                    ->update(['no_form_permintaan' => $spj->nomor_fp]);
            });
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->dropColumn('no_form_permintaan');
        });
    }
};
