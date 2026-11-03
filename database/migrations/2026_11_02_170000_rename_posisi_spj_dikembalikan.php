<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pilihan Posisi SPJ "Dikembalikan ke Pembuat" diganti jadi "Dikembalikan"
 * (MonitoringSpj::POSISI_SPJ). Data yang tersimpan dengan nilai lama ikut diganti supaya
 * tetap terbaca (badge, filter, hitungan dashboard, dan kunci Pembayaran).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('monitoring_spj')
            ->where('posisi_spj', 'Dikembalikan ke Pembuat')
            ->update(['posisi_spj' => 'Dikembalikan']);
    }

    public function down(): void
    {
        DB::table('monitoring_spj')
            ->where('posisi_spj', 'Dikembalikan')
            ->update(['posisi_spj' => 'Dikembalikan ke Pembuat']);
    }
};
