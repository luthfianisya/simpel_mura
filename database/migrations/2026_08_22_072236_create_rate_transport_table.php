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
        Schema::create('rate_transport', function (Blueprint $table) {
            $table->id('id_rate_transport');
            $table->enum('level', ['A', 'B', 'C'])->comment('A: Kabupaten-Kecamatan, B: Kecamatan-Desa, C: Kabupaten-Desa langsung');
            $table->string('nama_wilayah');
            $table->string('kecamatan_induk')->nullable();
            $table->string('moda_transportasi');
            $table->decimal('nilai_pp', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_transport');
    }
};
