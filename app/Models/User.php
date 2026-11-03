<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'id_pegawai_mitra',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function pegawaiMitra()
    {
        return $this->belongsTo(PegawaiMitra::class, 'id_pegawai_mitra', 'id_pegawai_mitra');
    }

    /**
     * Kolom manual Monitoring SPJ cuma boleh diisi pegawai yang peran pejabatnya
     * PPK, PPSPM, atau Bendahara (diatur di Kelola Pegawai), untuk semua perjalanan dinas.
     */
    public function bisaIsiMonitoringSpj(): bool
    {
        return in_array(optional($this->pegawaiMitra)->peran_pejabat, ['ppk', 'ppspm', 'bendahara'], true);
    }
}