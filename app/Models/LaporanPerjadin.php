<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanPerjadin extends Model
{
    protected $table = 'laporan_perjadin';
    protected $primaryKey = 'id_laporan';

    protected $fillable = ['id_perjalanan_dinas', 'kesimpulan_hasil_kegiatan', 'tindak_lanjut'];

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}