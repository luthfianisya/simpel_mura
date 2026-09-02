<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateTransport extends Model
{
    protected $table = 'rate_transport';
    protected $primaryKey = 'id_rate_transport';

    protected $fillable = ['level', 'nama_wilayah', 'kecamatan_induk', 'moda_transportasi', 'nilai_pp'];
}