<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu kali GUP (Ganti Uang Persediaan) = sekumpulan SPJ yang dikelompokkan PPK/PPSPM/
 * Bendahara. Tanggal GUP boleh diisi belakangan.
 */
class Gup extends Model
{
    protected $table = 'gup';
    protected $primaryKey = 'id_gup';

    protected $fillable = ['tanggal_gup', 'dibuat_oleh'];

    protected $casts = [
        'tanggal_gup' => 'date',
    ];

    public function monitoringSpj()
    {
        return $this->hasMany(MonitoringSpj::class, 'id_gup');
    }

    /** Tahun GUP: dari tanggal GUP, atau tanggal dibuat kalau tanggalnya belum diisi. */
    public function getTahunAttribute(): int
    {
        return ($this->tanggal_gup ?? $this->created_at ?? now())->year;
    }

    /**
     * Semua GUP, masing-masing diberi atribut `nomor` (GUP ke-n dalam tahunnya) dan `label`.
     * Urutan dalam setahun: tanggal GUP, lalu urutan dibuat (GUP tanpa tanggal di akhir).
     * Nomor dihitung, bukan disimpan — jadi tidak bolong kalau ada GUP yang dihapus.
     */
    public static function denganNomor(): Collection
    {
        $semua = static::orderBy('id_gup')->get();

        $semua->groupBy(fn (Gup $gup) => $gup->tahun)->each(function ($gupTahunIni) {
            $gupTahunIni
                ->sortBy(fn (Gup $gup) => [$gup->tanggal_gup ? 0 : 1, optional($gup->tanggal_gup)->format('Y-m-d'), $gup->id_gup])
                ->values()
                ->each(function (Gup $gup, int $i) {
                    $gup->nomor = $i + 1;
                    $gup->label = 'GUP ke-' . ($i + 1) . ' Tahun ' . $gup->tahun;
                });
        });

        return $semua;
    }
}
