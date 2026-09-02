<?php

namespace App\Models;

use App\Models\Dokumentasi;
use App\Models\JenisKegiatan;      
use App\Models\LaporanPerjadin;
use App\Models\PegawaiMitra;       
use App\Models\PengeluaranRiil;
use App\Models\RincianBiaya;
use App\Models\SuratPernyataan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PerjalananDinas extends Model
{
    protected $table = 'perjalanan_dinas';
    protected $primaryKey = 'id_perjalanan_dinas';

    protected $fillable = [
        'id_pemohon', 'id_pegawai_mitra_pelaksana', 'id_grup',
        'no_surat_tugas', 'tanggal_surat_tugas', 'perihal', 'uraian_tugas',
        'tanggal_mulai', 'tanggal_selesai', 'pembebanan',
        'id_jenis_kegiatan', 'jenis_perjadin',
        'no_spd', 'tanggal_spd', 'angkutan',
        'desa_asal', 'kabupaten_asal', 'desa_tujuan', 'kabupaten_tujuan',
        'id_penandatangan_st', 'id_ppk', 'id_bendahara', 'status_draft',
    ];

    protected $casts = [
        'tanggal_surat_tugas' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_spd' => 'date',
    ];

    public function pemohon()
    {
        return $this->belongsTo(PegawaiMitra::class, 'id_pemohon', 'id_pegawai_mitra');
    }

    public function pelaksana()
    {
        return $this->belongsTo(PegawaiMitra::class, 'id_pegawai_mitra_pelaksana', 'id_pegawai_mitra');
    }

    public function penandatanganSt()
    {
        return $this->belongsTo(PegawaiMitra::class, 'id_penandatangan_st', 'id_pegawai_mitra');
    }

    public function ppk()
    {
        return $this->belongsTo(PegawaiMitra::class, 'id_ppk', 'id_pegawai_mitra');
    }

    public function bendahara()
    {
        return $this->belongsTo(PegawaiMitra::class, 'id_bendahara', 'id_pegawai_mitra');
    }

    public function jenisKegiatan()
    {
        return $this->belongsTo(JenisKegiatan::class, 'id_jenis_kegiatan', 'id_jenis_kegiatan');
    }

    public function rincianBiaya()
    {
        return $this->hasMany(RincianBiaya::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }

    public function pengeluaranRiil()
    {
        return $this->hasMany(PengeluaranRiil::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }

    public function kuitansi()
    {
        return $this->hasOne(Kuitansi::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }

    public function suratPernyataan()
    {
        return $this->hasMany(SuratPernyataan::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }

    public function laporan()
    {
        return $this->hasOne(LaporanPerjadin::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }

    public function dokumentasi()
    {
        return $this->hasMany(Dokumentasi::class, 'id_perjalanan_dinas', 'id_perjalanan_dinas');
    }

    /**
     * Bentuk data nested untuk modul render dokumen (window.DokumenPerjadin di dokumen-perjadin.js).
     * Dipakai oleh modal "Lihat/Export" di dashboard.
     */
    public function toPreviewData(): array
    {
        return array_merge([
            'no_surat_tugas' => $this->no_surat_tugas,
            'tanggal_surat_tugas' => optional($this->tanggal_surat_tugas)->format('Y-m-d'),
            'perihal' => $this->perihal,
            'uraian_tugas' => $this->uraian_tugas,
            'tanggal_mulai' => optional($this->tanggal_mulai)->format('Y-m-d'),
            'tanggal_selesai' => optional($this->tanggal_selesai)->format('Y-m-d'),
            'pembebanan' => $this->pembebanan,
            'jenis_perjadin' => $this->jenis_perjadin,
            'tingkat_biaya' => null,
            'desa_asal' => $this->desa_asal,
            'kabupaten_asal' => $this->kabupaten_asal,
            'desa_tujuan' => $this->desa_tujuan,
            'kabupaten_tujuan' => $this->kabupaten_tujuan,
            'no_spd' => $this->no_spd,
            'tanggal_spd' => optional($this->tanggal_spd)->format('Y-m-d'),
            'angkutan' => $this->angkutan,
            'tanggal_kuitansi' => optional(optional($this->kuitansi)->tanggal_kuitansi)->format('Y-m-d'),
            'jenis_kegiatan_nama' => optional($this->jenisKegiatan)->nama_kegiatan,
            'kesimpulan_hasil_kegiatan' => optional($this->laporan)->kesimpulan_hasil_kegiatan,
            'tindak_lanjut' => optional($this->laporan)->tindak_lanjut,
            'pelaksana' => $this->pegawaiToArray($this->pelaksana),
            'penandatangan' => $this->pegawaiToArray($this->penandatanganSt),
            'ppk' => $this->pegawaiToArray($this->ppk),
            'bendahara' => $this->pegawaiToArray($this->bendahara),
        ], $this->relatedArraysData());
    }

    /**
     * Bentuk data flat sesuai nama field form wizard, dipakai untuk fitur "Copy" (duplikat jadi draft baru)
     * lewat session()->flashInput() di PerjalananDinasController@create.
     */
    public function toFormPrefill(): array
    {
        return array_merge([
            'no_surat_tugas' => $this->no_surat_tugas,
            'tanggal_surat_tugas' => optional($this->tanggal_surat_tugas)->format('Y-m-d'),
            'id_jenis_kegiatan' => $this->id_jenis_kegiatan,
            'perihal' => $this->perihal,
            'uraian_tugas' => $this->uraian_tugas,
            'id_pegawai_mitra_pelaksana' => $this->id_pegawai_mitra_pelaksana,
            'tanggal_mulai' => optional($this->tanggal_mulai)->format('Y-m-d'),
            'tanggal_selesai' => optional($this->tanggal_selesai)->format('Y-m-d'),
            'pembebanan' => $this->pembebanan,
            'desa_asal' => $this->desa_asal,
            'kabupaten_asal' => $this->kabupaten_asal,
            'desa_tujuan' => $this->desa_tujuan,
            'kabupaten_tujuan' => $this->kabupaten_tujuan,
            'id_penandatangan_st' => $this->id_penandatangan_st,
            'id_ppk' => $this->id_ppk,
            'id_bendahara' => $this->id_bendahara,
            'no_spd' => $this->no_spd,
            'tanggal_spd' => optional($this->tanggal_spd)->format('Y-m-d'),
            'angkutan' => $this->angkutan,
            'tanggal_kuitansi' => optional(optional($this->kuitansi)->tanggal_kuitansi)->format('Y-m-d'),
            'kesimpulan_hasil_kegiatan' => optional($this->laporan)->kesimpulan_hasil_kegiatan,
            'tindak_lanjut' => optional($this->laporan)->tindak_lanjut,
        ], $this->relatedArraysData());
    }

    private function relatedArraysData(): array
    {
        return [
            'rincian' => $this->rincianBiaya->map(fn ($r) => [
                'jenis_komponen' => $r->jenis_komponen,
                'nominal' => $r->nominal,
            ])->values()->all(),
            'pengeluaran' => $this->pengeluaranRiil->map(fn ($p) => [
                'uraian' => $p->uraian,
                'jenis' => $p->jenis,
                'nominal' => $p->nominal,
            ])->values()->all(),
            'pernyataan' => $this->suratPernyataan->map(fn ($p) => [
                'jenis_kondisi' => $p->jenis_kondisi,
                'keterangan' => $p->keterangan,
            ])->values()->all(),
            'dokumentasi' => $this->dokumentasi->map(fn ($d) => [
                'nama_file' => $d->nama_file,
                'url' => Storage::disk('public')->url($d->path_file),
            ])->values()->all(),
        ];
    }

    private function pegawaiToArray(?PegawaiMitra $pegawai): array
    {
        if (!$pegawai) {
            return ['nama' => '', 'nip' => '', 'jabatan' => '', 'pangkat' => '', 'golongan' => ''];
        }

        return [
            'nama' => $pegawai->nama,
            'nip' => $pegawai->nip,
            'jabatan' => $pegawai->jabatan,
            'pangkat' => $pegawai->pangkat,
            'golongan' => $pegawai->golongan,
        ];
    }
}