<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah_desa', function (Blueprint $table) {
            $table->id();
            $table->string('kecamatan');
            $table->enum('jenis', ['desa', 'kelurahan']);
            $table->string('nama');
            $table->unsignedInteger('jumlah_dipilih')->default(0);
            $table->timestamps();

            $table->unique(['kecamatan', 'jenis', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah_desa');
    }
};
