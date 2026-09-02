<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_riil', function (Blueprint $table) {
            $table->id('id_pengeluaran');
            $table->foreignId('id_perjalanan_dinas')->constrained('perjalanan_dinas', 'id_perjalanan_dinas')->cascadeOnDelete();
            $table->string('uraian');
            $table->decimal('nominal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_riil');
    }
};
