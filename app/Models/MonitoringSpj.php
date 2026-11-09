<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringSpj extends Model
{
    protected $table = 'monitoring_spj';
    protected $primaryKey = 'id_monitoring_spj';

    // Pilihan baku, disamakan dengan isian di spreadsheet monitoring SPJ sebelumnya.
    public const POSISI_SPJ = ['PPK', 'PPSPM', 'Bendahara', 'Dikembalikan'];
    // SPJ di posisi ini belum bisa dibayar (Status Pembayaran dipaksa "Belum Dibayar").
    public const POSISI_DIKEMBALIKAN = 'Dikembalikan';
    public const CARA_BAYAR = ['tunai' => 'Tunai', 'transfer' => 'Transfer'];
    public const JENIS_PEMBAYARAN = ['GUP', 'LS Bendahara', 'LS KPPN'];

    protected $fillable = [
        'id_perjalanan_dinas', 'id_gup',
        'tanggal_rincian', 'uraian_rincian', 'penerima_rincian', 'pembebanan_rincian', 'nominal_rincian',
        'tanggal_diperiksa_ppk', 'no_spp_spm', 'tanggal_spp', 'tanggal_spm',
        'no_sp2d', 'tanggal_sp2d', 'nomor_fp',
        'keterangan', 'posisi_spj', 'sudah_dibayar', 'cara_bayar',
        'sudah_spby', 'sudah_kuitansi', 'sudah_drpp', 'jenis_pembayaran', 'tanggal_gup',
        'diubah_oleh',
    ];

    protected $casts = [
        'tanggal_diperiksa_ppk' => 'date',
        'tanggal_spp' => 'date',
        'tanggal_spm' => 'date',
        'tanggal_sp2d' => 'date',
        'tanggal_gup' => 'date',
        'tanggal_rincian' => 'date',
        'nominal_rincian' => 'decimal:2',
        'sudah_dibayar' => 'boolean',
        'sudah_spby' => 'boolean',
        'sudah_kuitansi' => 'boolean',
        'sudah_drpp' => 'boolean',
    ];

    /**
     * Kelompok GUP tempat SPJ ini dimasukkan (lihat GupController). Kolom tanggal_gup di
     * tabel ini ikut disamakan dengan tanggal GUP-nya supaya tetap terbaca di tempat lain.
     */
    public function gup()
    {
        return $this->belongsTo(Gup::class, 'id_gup', 'id_gup');
    }

    /**
     * Rincian Non-Perjadin: baris yang diinput langsung oleh tim keuangan, bukan dari
     * perjalanan dinas (id_perjalanan_dinas kosong).
     */
    public function scopeRincianNonPerjadin($query)
    {
        return $query->whereNull('id_perjalanan_dinas');
    }

    public function getIsRincianAttribute(): bool
    {
        return $this->id_perjalanan_dinas === null;
    }

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}
