<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengeluaranRiil extends Model
{
    protected $table = 'pengeluaran_riil';
    protected $primaryKey = 'id_pengeluaran';

    protected $fillable = ['id_perjalanan_dinas', 'uraian', 'susunan', 'jenis', 'nominal'];

    // Isian per bagian penyusun uraian di wizard (lihat migrasi add_susunan_to_pengeluaran_riil).
    protected $casts = ['susunan' => 'array'];

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}