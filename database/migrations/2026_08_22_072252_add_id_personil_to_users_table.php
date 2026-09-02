<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('id_pegawai_mitra')->nullable()->after('id')
                ->constrained('pegawai_mitra', 'id_pegawai_mitra')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_pegawai_mitra']);
            $table->dropColumn('id_pegawai_mitra');
        });
    }
};
