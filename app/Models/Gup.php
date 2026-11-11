<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Satu kali GUP (Ganti Uang Persediaan) = sekumpulan SPJ yang dikelompokkan PPK/PPSPM/
 * Bendahara. Tanggal GUP, tanggal transfer, dan pajak boleh diisi belakangan.
 */
class Gup extends Model
{
    protected $table = 'gup';
    protected $primaryKey = 'id_gup';

    protected $fillable = ['tanggal_gup', 'tanggal_transfer', 'pajak', 'dibuat_oleh'];

    protected $casts = [
        'tanggal_gup' => 'date',
        'tanggal_transfer' => 'date',
        'pajak' => 'decimal:2',
    ];

    public const NAMA_BULAN = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function monitoringSpj()
    {
        return $this->hasMany(MonitoringSpj::class, 'id_gup');
    }

    /** Tanggal acuan GUP: tanggal GUP, atau tanggal dibuat kalau tanggalnya belum diisi. */
    public function getTanggalAcuanAttribute(): Carbon
    {
        return ($this->tanggal_gup ?? $this->created_at ?? now())->copy();
    }

    public function getTahunAttribute(): int
    {
        return $this->tanggal_acuan->year;
    }

    public function getBulanAttribute(): int
    {
        return $this->tanggal_acuan->month;
    }

    /**
     * Semua GUP, masing-masing diberi atribut `nomor` (GUP ke-n dalam BULANNYA) dan `label`,
     * mis. "GUP ke-1 Bulan November". Nomor mulai lagi dari 1 setiap bulan.
     * Urutan dalam sebulan: tanggal GUP, lalu urutan dibuat (GUP tanpa tanggal di akhir).
     * Nomor dihitung, bukan disimpan — jadi tidak bolong kalau ada GUP yang dihapus.
     */
    public static function denganNomor(): Collection
    {
        $semua = static::orderBy('id_gup')->get();

        $semua->groupBy(fn (Gup $gup) => $gup->tanggal_acuan->format('Y-m'))->each(function ($gupBulanIni) {
            $gupBulanIni
                ->sortBy(fn (Gup $gup) => [$gup->tanggal_gup ? 0 : 1, optional($gup->tanggal_gup)->format('Y-m-d'), $gup->id_gup])
                ->values()
                ->each(function (Gup $gup, int $i) {
                    $gup->nomor = $i + 1;
                    $gup->label = 'GUP ke-' . ($i + 1) . ' Bulan ' . self::NAMA_BULAN[$gup->bulan];
                });
        });

        return $semua;
    }
}
