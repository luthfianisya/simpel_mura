<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratPernyataan extends Model
{
    protected $table = 'surat_pernyataan';
    protected $primaryKey = 'id_pernyataan';

    protected $fillable = ['id_perjalanan_dinas', 'jenis_kondisi', 'keterangan'];

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}