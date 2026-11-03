<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data monitoring SPJ per perjalanan dinas (1:1). Kolom MAK, tanggal, uraian, nominal,
 * dan nama penerima TIDAK disimpan di sini — diambil langsung dari perjalanan_dinas,
 * kuitansi, rincian_biaya, dan pengeluaran_riil. Tabel ini cuma menyimpan kolom yang
 * diisi manual oleh PPK / Bendahara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_spj', function (Blueprint $table) {
            $table->id('id_monitoring_spj');
            $table->foreignId('id_perjalanan_dinas')->unique()->constrained('perjalanan_dinas', 'id_perjalanan_dinas')->cascadeOnDelete();

            $table->date('tanggal_diperiksa_ppk')->nullable();
            $table->string('no_spp_spm')->nullable();
            $table->date('tanggal_spp')->nullable();
            $table->date('tanggal_spm')->nullable();
            $table->string('no_sp2d')->nullable();
            $table->date('tanggal_sp2d')->nullable();
            $table->string('nomor_fp')->nullable();

            $table->string('keterangan')->nullable();
            $table->string('posisi_spj')->nullable();
            $table->boolean('sudah_dibayar')->default(false);
            $table->string('cara_bayar')->nullable()->comment('tunai / transfer');
            $table->boolean('sudah_spby')->default(false);
            $table->boolean('sudah_kuitansi')->default(false);
            $table->boolean('sudah_drpp')->default(false);
            $table->string('jenis_pembayaran')->nullable();
            $table->date('tanggal_gup')->nullable();

            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_spj');
    }
};
