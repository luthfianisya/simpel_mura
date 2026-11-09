<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris Monitoring SPJ di luar perjalanan dinas ("Rincian Non-Perjadin") yang diinput
 * langsung oleh PPK / PPSPM / Bendahara. Barisnya ditandai id_perjalanan_dinas = NULL dan
 * data yang biasanya diambil dari perjalanan dinas (tanggal, uraian, penerima, pembebanan,
 * nominal) disimpan di kolom *_rincian. Nomor FP memakai kolom nomor_fp yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_spj', function (Blueprint $table) {
            $table->dropForeign(['id_perjalanan_dinas']);
        });
        Schema::table('monitoring_spj', function (Blueprint $table) {
            $table->unsignedBigInteger('id_perjalanan_dinas')->nullable()->change();
            $table->foreign('id_perjalanan_dinas')->references('id_perjalanan_dinas')->on('perjalanan_dinas')->cascadeOnDelete();

            $table->date('tanggal_rincian')->nullable()->after('id_gup');
            $table->text('uraian_rincian')->nullable()->after('tanggal_rincian');
            $table->string('penerima_rincian')->nullable()->after('uraian_rincian');
            $table->string('pembebanan_rincian')->nullable()->after('penerima_rincian');
            $table->decimal('nominal_rincian', 15, 2)->nullable()->after('pembebanan_rincian');
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('monitoring_spj')->whereNull('id_perjalanan_dinas')->delete();

        Schema::table('monitoring_spj', function (Blueprint $table) {
            $table->dropColumn(['tanggal_rincian', 'uraian_rincian', 'penerima_rincian', 'pembebanan_rincian', 'nominal_rincian']);
            $table->dropForeign(['id_perjalanan_dinas']);
        });
        Schema::table('monitoring_spj', function (Blueprint $table) {
            $table->unsignedBigInteger('id_perjalanan_dinas')->nullable(false)->change();
            $table->foreign('id_perjalanan_dinas')->references('id_perjalanan_dinas')->on('perjalanan_dinas')->cascadeOnDelete();
        });
    }
};
