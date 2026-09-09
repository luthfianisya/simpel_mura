<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahDesa extends Model
{
    protected $table = 'wilayah_desa';

    protected $fillable = ['kecamatan', 'jenis', 'nama', 'jumlah_dipilih'];

    /**
     * Format tampilan sesuai konvensi form, mis. "Desa Beriwit" / "Kelurahan Puruk Cahu".
     */
    public function getNamaLengkapAttribute(): string
    {
        return ucfirst($this->jenis) . ' ' . $this->nama;
    }
}
