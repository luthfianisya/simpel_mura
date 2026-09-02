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
        Schema::create('satuan_biaya', function (Blueprint $table) {
            $table->id('id_tarif');
            $table->string('golongan');
            $table->string('nama_kota');
            $table->decimal('tarif_uang_harian', 12, 2);
            $table->decimal('tarif_penginapan', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan_biaya');
    }
};
