<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';
    protected $primaryKey = 'id_feedback';

    public const KATEGORI = [
        'kendala' => 'Kendala / Error',
        'saran' => 'Saran Perbaikan',
        'pertanyaan' => 'Pertanyaan',
    ];

    protected $fillable = [
        'id_user', 'kategori', 'judul', 'isi',
        'selesai', 'tindak_lanjut', 'ditindaklanjuti_oleh', 'ditindaklanjuti_pada',
    ];

    protected $casts = [
        'selesai' => 'boolean',
        'ditindaklanjuti_pada' => 'datetime',
    ];

    public function getKategoriLabelAttribute(): string
    {
        return self::KATEGORI[$this->kategori] ?? $this->kategori;
    }

    public function pengirim()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function penindakLanjut()
    {
        return $this->belongsTo(User::class, 'ditindaklanjuti_oleh');
    }
}
