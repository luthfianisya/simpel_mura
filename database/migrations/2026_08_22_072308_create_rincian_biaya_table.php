<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rincian_biaya', function (Blueprint $table) {
            $table->id('id_rincian');
            $table->foreignId('id_perjalanan_dinas')->constrained('perjalanan_dinas', 'id_perjalanan_dinas')->cascadeOnDelete();
            $table->enum('jenis_komponen', ['uang_harian', 'transport', 'penginapan']);
            $table->decimal('nominal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rincian_biaya');
    }
};
