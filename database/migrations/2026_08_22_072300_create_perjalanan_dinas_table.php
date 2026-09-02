<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perjalanan_dinas', function (Blueprint $table) {
            $table->id('id_perjalanan_dinas');

            $table->foreignId('id_pemohon')->constrained('pegawai_mitra', 'id_pegawai_mitra');
            $table->foreignId('id_pegawai_mitra_pelaksana')->constrained('pegawai_mitra', 'id_pegawai_mitra');
            $table->string('id_grup')->nullable()->comment('Mengelompokkan pegawai_mitra dalam satu ST yang sama');

            $table->string('no_surat_tugas');
            $table->date('tanggal_surat_tugas');
            $table->string('perihal');
            $table->text('uraian_tugas')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('pembebanan');

            $table->foreignId('id_jenis_kegiatan')->constrained('jenis_kegiatan', 'id_jenis_kegiatan'); // <-- berubah
            $table->enum('jenis_perjadin', ['biasa', 'dalam_kota_kurang_8_jam', 'dalam_kota_lebih_8_jam']);

            $table->string('no_spd')->nullable();
            $table->date('tanggal_spd')->nullable();
            $table->string('angkutan')->nullable();

            $table->string('desa_asal');
            $table->string('kabupaten_asal');
            $table->string('desa_tujuan');
            $table->string('kabupaten_tujuan');

            $table->foreignId('id_penandatangan_st')->nullable()->constrained('pegawai_mitra', 'id_pegawai_mitra');
            $table->foreignId('id_ppk')->nullable()->constrained('pegawai_mitra', 'id_pegawai_mitra');
            $table->foreignId('id_bendahara')->nullable()->constrained('pegawai_mitra', 'id_pegawai_mitra');

            $table->enum('status_draft', ['draft_sesi1', 'sesi1_selesai', 'draft_sesi2', 'selesai'])->default('draft_sesi1');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perjalanan_dinas');
    }
};
