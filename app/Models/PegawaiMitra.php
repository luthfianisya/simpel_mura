<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PegawaiMitra extends Model
{
    protected $table = 'pegawai_mitra';
    protected $primaryKey = 'id_pegawai_mitra';

    // Peran mitra statistik. Labelnya sekaligus dipakai sebagai "jabatan" mitra di
    // dokumen perjalanan dinas (Surat Tugas, SPD, dst).
    public const JENIS_MITRA = [
        'pendataan' => 'Mitra Pendataan',
        'pendataan_pengolahan' => 'Mitra Pendataan & Pengolahan',
    ];

    protected $fillable = [
        'nip', 'sobat_id', 'nama', 'jabatan', 'pangkat', 'golongan', 'alamat', 'jenis_mitra',
        'status_kepegawaian', 'peran_pejabat',
    ];

    public function getJenisMitraLabelAttribute(): ?string
    {
        return self::JENIS_MITRA[$this->jenis_mitra] ?? null;
    }

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
