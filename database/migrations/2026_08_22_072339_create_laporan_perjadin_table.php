<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_perjadin', function (Blueprint $table) {
            $table->id('id_laporan');
            $table->foreignId('id_perjalanan_dinas')->constrained('perjalanan_dinas', 'id_perjalanan_dinas')->cascadeOnDelete();
            $table->text('kesimpulan_hasil_kegiatan');
            $table->text('tindak_lanjut')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_perjadin');
    }
};
