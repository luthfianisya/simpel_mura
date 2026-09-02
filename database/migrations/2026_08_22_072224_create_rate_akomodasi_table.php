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
        Schema::create('rate_akomodasi', function (Blueprint $table) {
            $table->id('id_rate_akomodasi');
            $table->string('nama_kecamatan');
            $table->decimal('tarif_per_malam', 12, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_akomodasi');
    }
};
