<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kuitansi extends Model
{
    protected $table = 'kuitansi';
    protected $primaryKey = 'id_kuitansi';

    protected $fillable = ['id_perjalanan_dinas', 'tanggal_kuitansi'];

    protected $casts = ['tanggal_kuitansi' => 'date'];

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}