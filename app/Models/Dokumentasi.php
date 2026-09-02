<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumentasi extends Model
{
    protected $table = 'dokumentasi';
    protected $primaryKey = 'id_dokumentasi';

    protected $fillable = ['id_perjalanan_dinas', 'nama_file', 'path_file', 'uploaded_at'];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}