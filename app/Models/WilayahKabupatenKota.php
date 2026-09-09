<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahKabupatenKota extends Model
{
    protected $table = 'wilayah_kabupaten_kota';

    protected $fillable = ['nama', 'provinsi', 'jumlah_dipilih'];
}
