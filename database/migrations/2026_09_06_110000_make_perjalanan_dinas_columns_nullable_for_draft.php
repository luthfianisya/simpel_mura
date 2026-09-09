<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan sebagai draft (status_draft = draft_sesi1) sengaja tidak dibatasi sama
 * sekali — boleh kosong semua. Kolom-kolom ini semula NOT NULL, jadi perlu diubah
 * jadi nullable di level database juga, bukan cuma di validasi. Produksi (MySQL)
 * pakai raw SQL langsung; test suite (SQLite in-memory) pakai Schema::change()
 * biasa karena butuh doctrine/dbal, yang sudah ditambahkan ke composer.json.
 */
return new class extends Migration
{
    private array $kolomJadiNullable = [
        'id_pegawai_mitra_pelaksana' => 'BIGINT UNSIGNED',
        'no_surat_tugas' => 'VARCHAR(255)',
        'tanggal_surat_tugas' => 'DATE',
        'perihal' => 'VARCHAR(255)',
        'tanggal_mulai' => 'DATE',
        'tanggal_selesai' => 'DATE',
        'pembebanan' => 'VARCHAR(255)',
        'id_jenis_kegiatan' => 'BIGINT UNSIGNED',
        'desa_asal' => 'VARCHAR(255)',
        'kabupaten_asal' => 'VARCHAR(255)',
        'desa_tujuan' => 'VARCHAR(255)',
        'kabupaten_tujuan' => 'VARCHAR(255)',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite tidak punya ALTER COLUMN; Schema::change() di sini merekonstruksi
            // tabelnya sendiri secara native (tidak butuh doctrine/dbal untuk SQLite).
            Schema::table('perjalanan_dinas', function (Blueprint $table) {
                $table->unsignedBigInteger('id_pegawai_mitra_pelaksana')->nullable()->change();
                $table->string('no_surat_tugas')->nullable()->change();
                $table->date('tanggal_surat_tugas')->nullable()->change();
                $table->string('perihal')->nullable()->change();
                $table->date('tanggal_mulai')->nullable()->change();
                $table->date('tanggal_selesai')->nullable()->change();
                $table->string('pembebanan')->nullable()->change();
                $table->unsignedBigInteger('id_jenis_kegiatan')->nullable()->change();
                $table->string('desa_asal')->nullable()->change();
                $table->string('kabupaten_asal')->nullable()->change();
                $table->string('desa_tujuan')->nullable()->change();
                $table->string('kabupaten_tujuan')->nullable()->change();
            });
            return;
        }

        foreach ($this->kolomJadiNullable as $kolom => $tipe) {
            DB::statement("ALTER TABLE perjalanan_dinas MODIFY `{$kolom}` {$tipe} NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('perjalanan_dinas', function (Blueprint $table) {
                $table->unsignedBigInteger('id_pegawai_mitra_pelaksana')->nullable(false)->change();
                $table->string('no_surat_tugas')->nullable(false)->change();
                $table->date('tanggal_surat_tugas')->nullable(false)->change();
                $table->string('perihal')->nullable(false)->change();
                $table->date('tanggal_mulai')->nullable(false)->change();
                $table->date('tanggal_selesai')->nullable(false)->change();
                $table->string('pembebanan')->nullable(false)->change();
                $table->unsignedBigInteger('id_jenis_kegiatan')->nullable(false)->change();
                $table->string('desa_asal')->nullable(false)->change();
                $table->string('kabupaten_asal')->nullable(false)->change();
                $table->string('desa_tujuan')->nullable(false)->change();
                $table->string('kabupaten_tujuan')->nullable(false)->change();
            });
            return;
        }

        foreach ($this->kolomJadiNullable as $kolom => $tipe) {
            DB::statement("ALTER TABLE perjalanan_dinas MODIFY `{$kolom}` {$tipe} NOT NULL");
        }
    }
};
