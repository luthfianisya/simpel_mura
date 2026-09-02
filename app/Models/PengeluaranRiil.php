<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengeluaranRiil extends Model
{
    protected $table = 'pengeluaran_riil';
    protected $primaryKey = 'id_pengeluaran';

    protected $fillable = ['id_perjalanan_dinas', 'uraian', 'jenis', 'nominal'];

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}