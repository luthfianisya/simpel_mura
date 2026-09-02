<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuitansi', function (Blueprint $table) {
            $table->id('id_kuitansi');
            $table->foreignId('id_perjalanan_dinas')->constrained('perjalanan_dinas', 'id_perjalanan_dinas')->cascadeOnDelete();
            $table->date('tanggal_kuitansi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuitansi');
    }
};
