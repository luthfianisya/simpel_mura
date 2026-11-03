<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perihal kini diisi lewat textarea (teks panjang), jadi kolomnya diperbesar dari
 * VARCHAR(255) ke TEXT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->text('perihal')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->string('perihal')->nullable()->change();
        });
    }
};
