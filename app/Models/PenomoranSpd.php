<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran SPD: {urut}/62130/KU.340/{tahun}.
 *
 * Aturan: nomor urut dimulai dari nomor_awal + 1 per tahun, tidak boleh dobel, dan tidak
 * boleh bolong — nomor yang dipakai selalu nomor terkecil yang belum terpakai setelah
 * nomor_awal. Normalnya ini berarti naik satu-satu; kalau ada dokumen bernomor yang
 * dihapus, nomornya dipakai lagi oleh dokumen berikutnya supaya urutannya tetap rapat.
 */
class PenomoranSpd extends Model
{
    protected $table = 'penomoran_spd';
    protected $primaryKey = 'id_penomoran_spd';

    protected $fillable = ['tahun', 'nomor_awal'];

    public static function format(int $urut, int $tahun): string
    {
        return "{$urut}/62130/KU.340/{$tahun}";
    }

    public static function nomorUrut(?string $noSpd, int $tahun): ?int
    {
        if ($noSpd && preg_match('/^(\d+)\/62130\/KU\.340\/' . $tahun . '$/', trim($noSpd), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Nomor berikutnya untuk ditampilkan (pratinjau). Nomor final tetap ditentukan oleh
     * ambil() saat dokumen disimpan, karena bisa saja sudah dipakai orang lain duluan.
     */
    public static function berikutnya(int $tahun, ?int $kecualiIdPerjalananDinas = null): string
    {
        $nomorAwal = (int) static::where('tahun', $tahun)->value('nomor_awal');

        return static::format(static::urutTerkecilKosong($tahun, $nomorAwal, $kecualiIdPerjalananDinas), $tahun);
    }

    /**
     * Ambil nomor final secara atomik. Wajib dipanggil di dalam DB::transaction() yang
     * sama dengan penyimpanan perjalanan dinasnya, supaya kunci baris tahun ini baru
     * dilepas setelah nomornya benar-benar tersimpan.
     */
    public static function ambil(int $tahun, ?int $kecualiIdPerjalananDinas = null): string
    {
        static::firstOrCreate(['tahun' => $tahun], ['nomor_awal' => 0]);
        $penomoran = static::where('tahun', $tahun)->lockForUpdate()->first();

        return static::format(static::urutTerkecilKosong($tahun, (int) $penomoran->nomor_awal, $kecualiIdPerjalananDinas), $tahun);
    }

    public static function sudahDipakai(string $noSpd, ?int $kecualiIdPerjalananDinas = null): bool
    {
        return PerjalananDinas::where('no_spd', trim($noSpd))
            ->when($kecualiIdPerjalananDinas, fn ($q) => $q->where('id_perjalanan_dinas', '!=', $kecualiIdPerjalananDinas))
            ->exists();
    }

    private static function urutTerkecilKosong(int $tahun, int $nomorAwal, ?int $kecualiIdPerjalananDinas): int
    {
        $terpakai = PerjalananDinas::where('no_spd', 'like', "%/62130/KU.340/{$tahun}")
            ->when($kecualiIdPerjalananDinas, fn ($q) => $q->where('id_perjalanan_dinas', '!=', $kecualiIdPerjalananDinas))
            ->pluck('no_spd')
            ->map(fn ($noSpd) => static::nomorUrut($noSpd, $tahun))
            ->filter(fn ($urut) => $urut !== null && $urut > $nomorAwal)
            ->flip();

        $urut = $nomorAwal + 1;
        while ($terpakai->has($urut)) {
            $urut++;
        }

        return $urut;
    }
}
