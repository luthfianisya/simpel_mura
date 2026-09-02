<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisKegiatan extends Model
{
    protected $table = 'jenis_kegiatan';
    protected $primaryKey = 'id_jenis_kegiatan';

    protected $fillable = ['nama_kegiatan', 'template_uraian_tugas', 'template_uraian_laporan', 'saran_mak_default'];

    public function perjalananDinas()
    {
        return $this->hasMany(PerjalananDinas::class, 'id_jenis_kegiatan', 'id_jenis_kegiatan');
    }
}