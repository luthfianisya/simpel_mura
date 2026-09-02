<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuanBiaya extends Model
{
    protected $table = 'satuan_biaya';
    protected $primaryKey = 'id_tarif';

    protected $fillable = ['golongan', 'nama_kota', 'tarif_uang_harian', 'tarif_penginapan'];
}