<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rincian_biaya', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_hari')->nullable()->after('jenis_komponen');
        });
    }

    public function down(): void
    {
        Schema::table('rincian_biaya', function (Blueprint $table) {
            $table->dropColumn('jumlah_hari');
        });
    }
};
