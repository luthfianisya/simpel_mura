<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RincianBiaya extends Model
{
    protected $table = 'rincian_biaya';
    protected $primaryKey = 'id_rincian';

    protected $fillable = ['id_perjalanan_dinas', 'jenis_komponen', 'nominal'];

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}