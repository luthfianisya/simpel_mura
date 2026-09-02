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
        Schema::table('pengeluaran_riil', function (Blueprint $table) {
            $table->enum('jenis', ['transportasi', 'akomodasi'])->default('transportasi')->after('uraian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengeluaran_riil', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
