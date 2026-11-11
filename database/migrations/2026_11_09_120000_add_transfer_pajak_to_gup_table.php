<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data tambahan per GUP yang diisi tim keuangan: tanggal transfer dana GUP dan nilai pajaknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gup', function (Blueprint $table) {
            $table->date('tanggal_transfer')->nullable()->after('tanggal_gup');
            $table->decimal('pajak', 15, 2)->nullable()->after('tanggal_transfer');
        });
    }

    public function down(): void
    {
        Schema::table('gup', function (Blueprint $table) {
            $table->dropColumn(['tanggal_transfer', 'pajak']);
        });
    }
};
