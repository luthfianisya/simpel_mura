<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tanggal tanda tangan Surat Pernyataan Keterlambatan (dipilih user di form; default
// tanggal form terakhir diedit). Pernyataan lain memakai tanggal selesai perjalanan dinas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_pernyataan', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('surat_pernyataan', function (Blueprint $table) {
            $table->dropColumn('tanggal');
        });
    }
};
