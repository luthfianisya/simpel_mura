<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PegawaiMitra extends Model
{
    protected $table = 'pegawai_mitra';
    protected $primaryKey = 'id_pegawai_mitra';

    protected $fillable = [
        'nip', 'nama', 'jabatan', 'pangkat', 'golongan',
        'status_kepegawaian', 'peran_pejabat',
    ];

    public function perjalananDinasSebagaiPemohon()
    {
        return $this->hasMany(PerjalananDinas::class, 'id_pemohon', 'id_pegawai_mitra');
    }

    public function perjalananDinasSebagaiPelaksana()
    {
        return $this->hasMany(PerjalananDinas::class, 'id_pegawai_mitra_pelaksana', 'id_pegawai_mitra');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id_pegawai_mitra', 'id_pegawai_mitra');
    }
}