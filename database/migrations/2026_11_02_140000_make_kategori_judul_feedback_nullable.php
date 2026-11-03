<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Form feedback disederhanakan jadi uraian saja. Kategori & judul tidak lagi diisi,
 * tapi kolomnya dipertahankan (nullable) untuk data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('kategori')->nullable()->change();
            $table->string('judul')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('kategori')->nullable(false)->change();
            $table->string('judul')->nullable(false)->change();
        });
    }
};
