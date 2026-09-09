<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran_riil', function (Blueprint $table) {
            $table->text('uraian')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_riil', function (Blueprint $table) {
            $table->string('uraian')->change();
        });
    }
};
