<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Mengosongkan seluruh data transaksi perjalanan dinas (beserta monitoring SPJ & GUP)
 * tanpa menyentuh data master: pegawai/mitra, user, wilayah, rate, jenis kegiatan,
 * pengaturan penomoran SPD, dan dokumen pendukung.
 *
 *   php artisan simpel:reset-perjalanan-dinas
 *   php artisan simpel:reset-perjalanan-dinas --force   (tanpa tanya, mis. untuk skrip)
 */
class ResetDataPerjalananDinas extends Command
{
    protected $signature = 'simpel:reset-perjalanan-dinas {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus semua data perjalanan dinas, monitoring SPJ, dan GUP (data pegawai/mitra & master tetap ada)';

    /** Urutan hapus: anak dulu, baru induk (aman walau foreign key aktif). */
    private const TABEL = [
        'monitoring_spj' => 'Monitoring SPJ (termasuk Rincian Non-Perjadin)',
        'gup' => 'GUP',
        'dokumentasi' => 'Dokumentasi',
        'laporan_perjadin' => 'Laporan perjalanan dinas',
        'surat_pernyataan' => 'Surat pernyataan',
        'kuitansi' => 'Kuitansi',
        'pengeluaran_riil' => 'Pengeluaran riil',
        'rincian_biaya' => 'Rincian biaya',
        'perjalanan_dinas' => 'Perjalanan dinas',
    ];

    /** Folder file foto dokumentasi di disk "public". */
    private const FOLDER_DOKUMENTASI = 'dokumentasi';

    public function handle(): int
    {
        $this->warn('Data berikut akan DIHAPUS PERMANEN:');
        $this->table(['Data', 'Jumlah baris'], collect(self::TABEL)
            ->map(fn (string $label, string $tabel) => [$label, DB::table($tabel)->count()])
            ->values()
            ->all());
        $jumlahFile = count(Storage::disk('public')->allFiles(self::FOLDER_DOKUMENTASI));
        $this->line("File foto dokumentasi: {$jumlahFile} file");
        $this->newLine();
        $this->info('Tetap aman: pegawai/mitra, user, wilayah, rate, jenis kegiatan, penomoran SPD, dokumen pendukung.');
        $this->line('Setelah reset, nomor SPD dimulai lagi dari nomor awal yang diatur di penomoran SPD.');
        $this->newLine();

        if (! $this->option('force')) {
            $this->warn('Pastikan database sudah di-backup (mis. Export di phpMyAdmin).');
            if ($this->ask('Ketik RESET untuk melanjutkan') !== 'RESET') {
                $this->info('Dibatalkan. Tidak ada data yang dihapus.');

                return self::FAILURE;
            }
        }

        DB::transaction(function () {
            foreach (array_keys(self::TABEL) as $tabel) {
                DB::table($tabel)->delete();
            }
        });

        // Nomor ID mulai dari 1 lagi (ALTER TABLE di MySQL tidak bisa di dalam transaksi).
        if (DB::getDriverName() === 'mysql') {
            foreach (array_keys(self::TABEL) as $tabel) {
                DB::statement("ALTER TABLE `{$tabel}` AUTO_INCREMENT = 1");
            }
        }

        Storage::disk('public')->deleteDirectory(self::FOLDER_DOKUMENTASI);

        $this->info('Selesai. Data perjalanan dinas, monitoring SPJ, dan GUP sudah kosong.');

        return self::SUCCESS;
    }
}
