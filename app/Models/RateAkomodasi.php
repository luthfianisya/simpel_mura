<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateAkomodasi extends Model
{
    protected $table = 'rate_akomodasi';
    protected $primaryKey = 'id_rate_akomodasi';

    protected $fillable = ['nama_kecamatan', 'tarif_per_malam', 'keterangan'];
}