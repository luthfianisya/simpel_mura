<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_kegiatan', function (Blueprint $table) {
            $table->text('template_perihal')->nullable()->after('template_uraian_tugas');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_kegiatan', function (Blueprint $table) {
            $table->dropColumn('template_perihal');
        });
    }
};
