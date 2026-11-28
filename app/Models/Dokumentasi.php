<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumentasi extends Model
{
    protected $table = 'dokumentasi';
    protected $primaryKey = 'id_dokumentasi';

    protected $fillable = ['id_perjalanan_dinas', 'nama_file', 'caption', 'skala', 'urutan', 'path_file', 'uploaded_at'];

    protected $casts = ['uploaded_at' => 'datetime', 'skala' => 'integer'];

    /**
     * Data foto untuk renderer dokumen (preview/PDF): ukuran tampil (skala, persen) dan
     * ukuran asli gambar (piksel) supaya tinggi foto di Laporan bisa dihitung tepat
     * dengan rasio terkunci. PDF/berkas lain: lebar & tinggi null.
     */
    public function dataDokumen(): array
    {
        $ukuran = null;
        if (preg_match('/\.(jpe?g|png|gif|webp)$/i', (string) $this->path_file)) {
            $file = \Illuminate\Support\Facades\Storage::disk('public')->path($this->path_file);
            $ukuran = is_file($file) ? @getimagesize($file) : null;
        }

        return [
            'skala' => $this->skala ?: 100,
            'lebar' => $ukuran[0] ?? null,
            'tinggi' => $ukuran[1] ?? null,
        ];
    }

    public function perjalananDinas()
    {
        return $this->belongsTo(PerjalananDinas::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }
}