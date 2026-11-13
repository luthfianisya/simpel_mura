<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BacaImportExcel;
use App\Models\Dokumentasi;
use App\Models\JenisKegiatan;
use App\Models\Kuitansi;
use App\Models\LaporanPerjadin;
use App\Models\PegawaiMitra;
use App\Models\PenomoranSpd;
use App\Models\PengeluaranRiil;
use App\Models\PerjalananDinas;
use App\Models\RateAkomodasi;
use App\Models\RateTransport;
use App\Models\RincianBiaya;
use App\Models\SuratPernyataan;
use App\Models\WilayahDesa;
use App\Models\WilayahKabupatenKota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PerjalananDinasController extends Controller
{
    use BacaImportExcel;

    protected array $jenisSpdWajib = ['biasa', 'dalam_kota_lebih_8_jam'];

    private const JENIS_PERJADIN_LABELS = [
        'biasa' => 'Biasa',
        'dalam_kota_kurang_8_jam' => 'Dalam Kota <=8 Jam',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota >8 Jam',
    ];

    private const YA_TIDAK = ['Ya', 'Tidak'];

    private const JUMLAH_REKOMENDASI_DESA = 8;

    // Pesan & nama kolom (sesuai header Excel) untuk error import, supaya yang dilaporkan
    // langsung menunjuk kolom yang harus diperbaiki di file.
    private const PESAN_VALIDASI_IMPORT = [
        'required' => 'kolom ":attribute" wajib diisi.',
        'date' => 'kolom ":attribute" harus tanggal dengan format YYYY-MM-DD.',
        'after_or_equal' => 'kolom ":attribute" tidak boleh sebelum ":date".',
        'exists' => 'kolom ":attribute" tidak ditemukan.',
        'numeric' => 'kolom ":attribute" harus berupa angka.',
        'integer' => 'kolom ":attribute" harus berupa angka bulat.',
        'min' => 'kolom ":attribute" tidak boleh negatif.',
        'max' => 'kolom ":attribute" terlalu panjang (maks. :max karakter).',
        'in' => 'kolom ":attribute" tidak valid.',
        'string' => 'kolom ":attribute" harus berupa teks.',
    ];

    private const NAMA_KOLOM_IMPORT = [
        'jenis_perjadin' => 'Jenis Perjalanan Dinas', 'no_surat_tugas' => 'No Surat Tugas',
        'tanggal_surat_tugas' => 'Tanggal Surat Tugas', 'perihal' => 'Perihal', 'uraian_tugas' => 'Uraian Tugas',
        'id_pegawai_mitra_pelaksana' => 'NIP Pelaksana', 'tanggal_mulai' => 'Tanggal Mulai',
        'tanggal_selesai' => 'Tanggal Selesai', 'pembebanan' => 'Pembebanan (MAK)', 'no_form_permintaan' => 'No Form Permintaan',
        'desa_asal' => 'Desa/Kel Asal', 'kabupaten_asal' => 'Kabupaten/Kota Asal',
        'desa_tujuan' => 'Desa/Kel Tujuan', 'kabupaten_tujuan' => 'Kabupaten/Kota Tujuan',
        'id_penandatangan_st' => 'NIP Penandatangan ST', 'id_ppk' => 'NIP PPK', 'id_bendahara' => 'NIP Bendahara',
        'tanggal_spd' => 'Tanggal SPD', 'angkutan' => 'Angkutan', 'tanggal_kuitansi' => 'Tanggal Kuitansi',
        'rincian.uang_harian' => 'Uang Harian (Rp)', 'rincian.transport' => 'Transport (Rp)', 'rincian.penginapan' => 'Penginapan (Rp)',
        'rincian_hari.uang_harian' => 'Jumlah Hari Uang Harian', 'rincian_hari.transport' => 'Jumlah Hari Transport',
        'rincian_hari.penginapan' => 'Jumlah Hari Penginapan',
        'pengeluaran.*.uraian' => 'Pengeluaran - Uraian', 'pengeluaran.*.nominal' => 'Pengeluaran - Nominal',
    ];

    // Urutan & nama kolom template import — dipakai bareng oleh importTemplate() (nulis
    // header) dan import() (baca baris), supaya dua-duanya selalu sinkron.
    private const KOLOM_IMPORT = [
        'jenis_perjadin', 'no_surat_tugas', 'tanggal_surat_tugas', 'jenis_kegiatan', 'perihal', 'uraian_tugas',
        'nip_pelaksana', 'tanggal_mulai', 'tanggal_selesai', 'pembebanan',
        'desa_asal', 'kabupaten_asal', 'desa_tujuan', 'kabupaten_tujuan',
        'nip_penandatangan_st', 'nip_ppk', 'nip_bendahara',
        'tanggal_spd', 'angkutan', 'tingkat_biaya',
        'uang_harian', 'transport', 'penginapan',
        'pengeluaran1_jenis', 'pengeluaran1_uraian', 'pengeluaran1_nominal',
        'pengeluaran2_jenis', 'pengeluaran2_uraian', 'pengeluaran2_nominal',
        'pengeluaran3_jenis', 'pengeluaran3_uraian', 'pengeluaran3_nominal',
        'tanggal_kuitansi',
        'pernyataan_tidak_pakai_kendaraan_dinas', 'pernyataan_tidak_menginap_hotel',
        'pernyataan_keterlambatan', 'pernyataan_keterlambatan_keterangan',
        'kesimpulan_hasil_kegiatan', 'tindak_lanjut', 'status_draft',
        'uang_harian_hari', 'transport_hari', 'penginapan_hari',
        'no_form_permintaan',
    ];

    protected array $relasiRekap = [
        'pemohon', 'pelaksana', 'penandatanganSt', 'ppk', 'bendahara', 'jenisKegiatan',
        'rincianBiaya', 'pengeluaranRiil', 'kuitansi', 'suratPernyataan', 'laporan', 'dokumentasi',
    ];

    /**
     * Semua perjalanan dinas dari semua user.
     */
    public function index()
    {
        $daftarPerjalananDinas = PerjalananDinas::with($this->relasiRekap)->latest()->get();
        $dokumenDataById = $daftarPerjalananDinas->mapWithKeys(
            fn ($pd) => [$pd->id_perjalanan_dinas => $pd->toPreviewData()]
        );

        return view('perjalanan-dinas.index', compact('daftarPerjalananDinas', 'dokumenDataById'));
    }

    /**
     * Perjalanan dinas dengan pemohon = user yang sedang login.
     */
    public function mine(Request $request)
    {
        $pegawaiId = $request->user()->id_pegawai_mitra;

        $daftarPerjalananDinas = $pegawaiId
            ? PerjalananDinas::with($this->relasiRekap)->where('id_pemohon', $pegawaiId)->latest()->get()
            : collect();

        $dokumenDataById = $daftarPerjalananDinas->mapWithKeys(
            fn ($pd) => [$pd->id_perjalanan_dinas => $pd->toPreviewData()]
        );

        return view('perjalanan-dinas.saya', compact('daftarPerjalananDinas', 'dokumenDataById', 'pegawaiId'));
    }

    public function create(Request $request)
    {
        $jenis = $request->query('jenis');
        if (!in_array($jenis, ['biasa', 'dalam_kota_kurang_8_jam', 'dalam_kota_lebih_8_jam'], true)) {
            $jenis = 'biasa';
        }

        // Fitur "Copy": duplikat record jadi draft baru. old('field') di view otomatis
        // terisi dari record sumber lewat flashInput, tanpa perlu ubah field manapun di view.
        // Guard hasOldInput(): kalau ini adalah redirect-back akibat validasi gagal saat submit,
        // JANGAN timpa old-input yang sudah ada dengan data sumber lagi.
        if (!$request->session()->hasOldInput()) {
            $sumber = PerjalananDinas::with(['rincianBiaya', 'pengeluaranRiil', 'suratPernyataan', 'kuitansi', 'laporan'])
                ->find($request->query('from'));
            if ($sumber) {
                // Nomor SPD, Nomor Form Permintaan & tanggal laporan tidak ikut disalin — tiap dokumen punya nomor/tanggal sendiri.
                session()->flashInput(array_merge($sumber->toFormPrefill(), ['no_spd' => null, 'no_form_permintaan' => null, 'tanggal_laporan' => null]));
            }
        }

        return view('perjalanan-dinas.wizard', $this->formViewData($request, $jenis, 'create', null));
    }

    /**
     * Form edit — hanya untuk pemohon sendiri, dan hanya selama masih draft.
     */
    public function edit(Request $request, PerjalananDinas $perjalananDinas)
    {
        abort_unless($perjalananDinas->bisaDiubahOleh($request->user()->id_pegawai_mitra), 403);

        $perjalananDinas->load(['rincianBiaya', 'pengeluaranRiil', 'suratPernyataan', 'kuitansi', 'laporan', 'dokumentasi']);
        if (!$request->session()->hasOldInput()) {
            session()->flashInput($perjalananDinas->toFormPrefill());
        }

        return view('perjalanan-dinas.wizard', $this->formViewData($request, $perjalananDinas->jenis_perjadin, 'edit', $perjalananDinas));
    }

    /**
     * Halaman lihat (read-only) — wizard yang sama, tapi semua input dikunci.
     */
    public function show(Request $request, PerjalananDinas $perjalananDinas)
    {
        $perjalananDinas->load(['rincianBiaya', 'pengeluaranRiil', 'suratPernyataan', 'kuitansi', 'laporan', 'dokumentasi']);
        session()->flashInput($perjalananDinas->toFormPrefill());

        return view('perjalanan-dinas.wizard', $this->formViewData($request, $perjalananDinas->jenis_perjadin, 'view', $perjalananDinas));
    }

    /**
     * Kumpulan variabel yang dibutuhkan view wizard, dipakai bareng oleh create/edit/show
     * supaya ketiganya render form (atau versi read-only-nya) yang identik.
     */
    private function formViewData(Request $request, string $jenis, string $mode, ?PerjalananDinas $perjalananDinas): array
    {
        $butuhSpd = in_array($jenis, $this->jenisSpdWajib, true);
        $pemohonPegawai = $request->user()->pegawaiMitra;
        $pegawaiMitraList = PegawaiMitra::orderBy('nama')->get();
        $pegawaiList = PegawaiMitra::where('status_kepegawaian', 'pegawai')->orderBy('nama')->get();
        $mitraList = PegawaiMitra::where('status_kepegawaian', 'mitra')->orderBy('nama')->get();
        $jenisKegiatanList = JenisKegiatan::orderBy('nama_kegiatan')->get();

        $idPelaksanaLama = old('id_pegawai_mitra_pelaksana');
        $jenisPelaksanaLama = $idPelaksanaLama
            ? ($mitraList->contains('id_pegawai_mitra', $idPelaksanaLama) ? 'mitra' : 'pegawai')
            : '';

        // No. Surat Tugas disimpan lengkap format "B-{angka}/62130/KU.340/{tahun}",
        // tapi di form user cuma input angkanya saja.
        $oldNoSuratTugas = old('no_surat_tugas');
        $noSuratTugasAngka = '';
        if ($oldNoSuratTugas && preg_match('/^B-(\d+)\/62130\/KU\.340\/\d{4}$/', $oldNoSuratTugas, $m)) {
            $noSuratTugasAngka = $m[1];
        }

        [
            'kecamatanMurungRaya' => $kecamatanMurungRaya,
            'kabupatenKotaIndonesia' => $kabupatenKotaIndonesia,
            'rekomendasiKabupatenKota' => $rekomendasiKabupatenKota,
            'rekomendasiDesa' => $rekomendasiDesa,
        ] = $this->dataWilayahUntukForm();

        // Dipakai JS di step Rincian Biaya (rekomendasi nilai akomodasi) & Pengeluaran Riil
        // (peringatan kalau nominal transportasi melebihi rate resmi) — lihat SK KPA Nomor
        // 13 & 14 Tahun 2026 yang datanya sudah di-seed ke rate_akomodasi & rate_transport.
        $rateAkomodasiByKecamatan = RateAkomodasi::pluck('tarif_per_malam', 'nama_kecamatan');
        $rateTransportMaxByWilayah = RateTransport::selectRaw('nama_wilayah, MAX(nilai_pp) as maksimum')
            ->groupBy('nama_wilayah')
            ->pluck('maksimum', 'nama_wilayah');
        $desaKeKecamatan = WilayahDesa::pluck('kecamatan', 'nama');

        // Perjalanan dinas dalam kota (<8 jam maupun >8 jam) selalu di Kabupaten Murung
        // Raya sendiri, jadi field Kabupaten/Kota dikunci ke situ untuk kedua jenis ini.
        $isDalamKota = in_array($jenis, ['dalam_kota_kurang_8_jam', 'dalam_kota_lebih_8_jam'], true);
        $defaultKabupatenAsal = old('kabupaten_asal', 'Kabupaten Murung Raya');
        $defaultKabupatenTujuan = old('kabupaten_tujuan', $isDalamKota ? 'Kabupaten Murung Raya' : '');

        // File dokumentasi yang sudah tersimpan (mode edit/view) — input file browser tidak
        // bisa di-prefill, jadi ini dikirim terpisah untuk mengisi preview Laporan di JS.
        $existingDokumentasi = $perjalananDinas && $perjalananDinas->relationLoaded('dokumentasi')
            ? $perjalananDinas->dokumentasi->map(fn ($d) => [
                'id' => $d->id_dokumentasi,
                'nama_file' => $d->nama_file,
                'caption' => $d->caption,
                'url' => Storage::disk('public')->url($d->path_file),
            ])->values()->all()
            : [];

        return compact(
            'jenis', 'butuhSpd', 'pemohonPegawai', 'pegawaiMitraList', 'pegawaiList', 'mitraList', 'jenisKegiatanList',
            'idPelaksanaLama', 'jenisPelaksanaLama',
            'oldNoSuratTugas', 'noSuratTugasAngka', 'kecamatanMurungRaya',
            'kabupatenKotaIndonesia', 'rekomendasiKabupatenKota', 'rekomendasiDesa',
            'isDalamKota', 'defaultKabupatenAsal', 'defaultKabupatenTujuan',
            'rateAkomodasiByKecamatan', 'rateTransportMaxByWilayah', 'desaKeKecamatan',
            'mode', 'perjalananDinas', 'existingDokumentasi'
        );
    }

    /**
     * Data master Kabupaten/Kota & Desa/Kelurahan untuk dropdown Asal/Tujuan, diambil dari
     * database (dikelola admin lewat menu Kelola Wilayah) dan diurutkan berdasarkan
     * jumlah_dipilih supaya wilayah yang paling sering dipakai naik ke atas/masuk
     * grup "Rekomendasi" — memudahkan user memilih tanpa harus mencari-cari.
     */
    private function dataWilayahUntukForm(): array
    {
        $semuaDesa = WilayahDesa::orderByDesc('jumlah_dipilih')->orderBy('nama')->get();
        $rekomendasi = $semuaDesa->where('jumlah_dipilih', '>', 0)->take(self::JUMLAH_REKOMENDASI_DESA);
        $idRekomendasi = $rekomendasi->pluck('id')->all();

        $kecamatanMurungRaya = [];
        foreach ($semuaDesa as $item) {
            if (in_array($item->id, $idRekomendasi, true)) {
                continue; // sudah tampil di grup Rekomendasi, tidak diduplikasi di grup kecamatannya
            }
            $kecamatanMurungRaya[$item->kecamatan][$item->jenis][] = $item->nama;
        }
        foreach ($kecamatanMurungRaya as &$wilayah) {
            $wilayah += ['desa' => [], 'kelurahan' => []];
            sort($wilayah['desa']);
            sort($wilayah['kelurahan']);
        }
        unset($wilayah);
        ksort($kecamatanMurungRaya);

        // Kabupaten/Kota Asal & Tujuan: seluruh Indonesia, dikelompokkan per provinsi
        // (optgroup) — konsepnya sama seperti Desa/Kel. yang dikelompokkan per kecamatan
        // di atas. Provinsi tempat BPS Kab. Murung Raya berada (Kalimantan Tengah)
        // ditaruh paling atas karena paling sering relevan.
        $semuaKabupatenKota = WilayahKabupatenKota::orderByDesc('jumlah_dipilih')->orderBy('nama')->get();
        $rekomendasiKabupatenKota = $semuaKabupatenKota->where('jumlah_dipilih', '>', 0)->take(self::JUMLAH_REKOMENDASI_DESA);
        $idRekomendasiKabupatenKota = $rekomendasiKabupatenKota->pluck('id')->all();

        $kabupatenKotaIndonesia = [];
        foreach ($semuaKabupatenKota as $item) {
            if (in_array($item->id, $idRekomendasiKabupatenKota, true)) {
                continue; // sudah tampil di grup Rekomendasi, tidak diduplikasi di grup provinsinya
            }
            $kabupatenKotaIndonesia[$item->provinsi ?? 'Lainnya'][] = $item->nama;
        }
        foreach ($kabupatenKotaIndonesia as &$daftarNama) {
            sort($daftarNama);
        }
        unset($daftarNama);
        uksort($kabupatenKotaIndonesia, function ($a, $b) {
            if ($a === 'Kalimantan Tengah') return -1;
            if ($b === 'Kalimantan Tengah') return 1;
            return $a <=> $b;
        });

        return [
            'kecamatanMurungRaya' => $kecamatanMurungRaya,
            'kabupatenKotaIndonesia' => $kabupatenKotaIndonesia,
            'rekomendasiKabupatenKota' => $rekomendasiKabupatenKota->pluck('nama')->values()->all(),
            'rekomendasiDesa' => $rekomendasi->map(fn ($d) => $d->nama_lengkap)->values()->all(),
        ];
    }

    /**
     * Catat pemilihan Kabupaten/Kota & Desa/Kelurahan Asal-Tujuan untuk basis rekomendasi
     * dropdown. Sengaja hanya dipanggil saat data baru dibuat (store/import), bukan saat
     * update, supaya draft yang berkali-kali disimpan tidak menggandakan hitungan.
     */
    private function catatPemilihanWilayah(array $validated): void
    {
        foreach (array_filter([$validated['kabupaten_asal'] ?? null, $validated['kabupaten_tujuan'] ?? null]) as $nama) {
            WilayahKabupatenKota::where('nama', $nama)->increment('jumlah_dipilih');
        }

        foreach ([$validated['desa_asal'] ?? null, $validated['desa_tujuan'] ?? null] as $daftar) {
            foreach ($this->pisahDaftarDenganDan($daftar) as $label) {
                if (str_starts_with($label, 'Desa ')) {
                    [$jenis, $nama] = ['desa', substr($label, 5)];
                } elseif (str_starts_with($label, 'Kelurahan ')) {
                    [$jenis, $nama] = ['kelurahan', substr($label, 10)];
                } else {
                    continue;
                }
                WilayahDesa::where('jenis', $jenis)->where('nama', $nama)->increment('jumlah_dipilih');
            }
        }
    }

    /**
     * Kebalikan dari formatDaftarDenganDan() di wizard.blade.php — memecah string
     * "Desa A, Desa B, dan Kelurahan C" kembali jadi array per item.
     */
    private function pisahDaftarDenganDan(?string $str): array
    {
        if (!$str) {
            return [];
        }

        $str = preg_replace('/,?\s*dan\s+/i', ', ', $str);

        return array_values(array_filter(array_map('trim', explode(',', $str))));
    }

    /**
     * Pratinjau nomor SPD berikutnya (lihat PenomoranSpd). Nomor finalnya ditetapkan ulang
     * oleh server saat dokumen disimpan (tentukanNomorSpd), jadi kalau nomor ini keburu
     * dipakai orang lain, dokumen otomatis mendapat nomor berikutnya — tidak akan dobel.
     */
    public function nomorSpdBerikutnya(Request $request)
    {
        $tahun = (int) $request->query('tahun', now()->year);

        return response()->json([
            'no_spd' => PenomoranSpd::berikutnya($tahun),
        ]);
    }

    /**
     * Cek apakah nomor SPD yang diketik manual sudah dipakai dokumen lain (selain
     * dokumen yang sedang diedit). Hanya untuk umpan balik di form; penentu akhirnya
     * tetap tentukanNomorSpd saat disimpan.
     */
    public function cekNomorSpd(Request $request)
    {
        $validated = $request->validate([
            'no_spd' => 'required|string|max:255',
            'kecuali' => 'nullable|integer',
        ]);

        return response()->json([
            'dipakai' => PenomoranSpd::sudahDipakai($validated['no_spd'], $validated['kecuali'] ?? null),
        ]);
    }

    /**
     * Nomor SPD yang disimpan untuk sebuah perjalanan dinas. Dipanggil di dalam
     * DB::transaction() penyimpanan supaya kunci penomoran (PenomoranSpd::ambil) bertahan
     * sampai nomornya tersimpan.
     *
     * - Jenis tanpa SPD → null (nomornya dilepas dan nanti dipakai dokumen lain).
     * - Dokumen yang sudah punya nomor → tetap nomor itu.
     * - Draft yang belum "Ambil Nomor" → tetap kosong.
     * - Selain itu (sudah klik "Ambil Nomor", atau final tanpa nomor) → ambil nomor baru.
     * - $selaluAmbil (import Excel) → selalu diambilkan nomor baru, termasuk untuk draft,
     *   karena template import tidak punya kolom No SPD (penomoran sepenuhnya otomatis).
     */
    /**
     * Pesan kalau nomor SPD yang tersimpan beda dengan yang tampil di form (mis. nomor
     * pratinjau keburu dipakai orang lain yang menyimpan lebih dulu). Null kalau sama.
     */
    private function peringatanNomorSpdBerubah(Request $request, ?string $noSpdTersimpan): ?string
    {
        $noSpdForm = trim((string) $request->input('no_spd'));
        if ($noSpdForm === '' || !$noSpdTersimpan || $noSpdForm === $noSpdTersimpan) {
            return null;
        }

        return "Nomor SPD {$noSpdForm} sudah dipakai dokumen lain, sehingga dokumen Anda mendapat nomor {$noSpdTersimpan}. "
            . 'Jika sempat mengekspor/mencetak SPD dengan nomor lama, ekspor ulang dokumennya.';
    }

    private function tentukanNomorSpd(array $validated, ?PerjalananDinas $perjalananDinas = null, bool $selaluAmbil = false): ?string
    {
        if (!in_array($validated['jenis_perjadin'], $this->jenisSpdWajib, true)) {
            return null;
        }

        $diminta = trim((string) ($validated['no_spd'] ?? ''));

        // Nomor diketik sendiri: dipakai apa adanya selama belum dipakai dokumen lain.
        // Dicek di dalam transaksi penyimpanan; ValidationException me-rollback transaksinya.
        if (!$selaluAmbil && $diminta !== '' && ($validated['no_spd_sumber'] ?? null) === 'manual'
            && $diminta !== $perjalananDinas?->no_spd) {
            if (PenomoranSpd::sudahDipakai($diminta, $perjalananDinas?->id_perjalanan_dinas)) {
                throw ValidationException::withMessages([
                    'no_spd' => "Nomor SPD {$diminta} sudah dipakai dokumen lain. Gunakan nomor lain atau klik Ambil Nomor.",
                ]);
            }

            return $diminta;
        }

        if ($perjalananDinas?->no_spd) {
            return $perjalananDinas->no_spd;
        }
        if (!$selaluAmbil && $diminta === '' && $validated['status_draft'] !== 'selesai') {
            return null;
        }

        $tanggal = $validated['tanggal_spd'] ?? $validated['tanggal_mulai'] ?? null;
        $tahun = $tanggal ? (int) substr($tanggal, 0, 4) : now()->year;

        return PenomoranSpd::ambil($tahun, $perjalananDinas?->id_perjalanan_dinas);
    }

    // Opsi "Lainnya..." di dropdown Jenis Kegiatan — bikin baris jenis_kegiatan baru
    // (tanpa template uraian/perihal/MAK, karena memang belum ada) supaya bisa langsung
    // dipilih di form yang sama, dan otomatis tersedia buat perjalanan dinas berikutnya
    // juga. firstOrCreate berdasarkan nama supaya tidak dobel kalau nama yang sama
    // sudah pernah ditambahkan sebelumnya.
    public function tambahJenisKegiatan(Request $request)
    {
        $validated = $request->validate([
            'nama_kegiatan' => 'required|string|max:255',
        ]);

        $jenisKegiatan = JenisKegiatan::firstOrCreate(['nama_kegiatan' => $validated['nama_kegiatan']]);

        return response()->json([
            'id_jenis_kegiatan' => $jenisKegiatan->id_jenis_kegiatan,
            'nama_kegiatan' => $jenisKegiatan->nama_kegiatan,
        ]);
    }

    /**
     * @param bool $isDraft Simpan sebagai draft (status_draft = draft_sesi1) sengaja tidak
     * dibatasi sama sekali — semua field Data Umum & SPD boleh kosong, supaya user bisa
     * menyimpan progres kapan saja. Wajib-isi hanya berlaku saat "Selesai & Simpan".
     */
    private function validationRules(bool $butuhSpd, bool $isDraft = false, string $jenisPerjadin = ''): array
    {
        $wajib = $isDraft ? 'nullable' : 'required';
        // Perjalanan dinas "biasa" (antar kabupaten/kota) tidak butuh Desa/Kel. asal &
        // tujuan — cukup Kabupaten/Kota sebagai referensi wilayah (lihat juga
        // wilayahAsal/wilayahTujuan di dokumen-perjadin.js yang jatuh balik ke kabupaten
        // untuk dokumen). Untuk jenis "dalam kota" tetap wajib seperti sebelumnya.
        $wajibDesa = ($isDraft || $jenisPerjadin === 'biasa') ? 'nullable' : 'required';

        return [
            'jenis_perjadin' => 'required|in:biasa,dalam_kota_kurang_8_jam,dalam_kota_lebih_8_jam',
            'status_draft' => 'required|in:draft_sesi1,selesai',
            'no_surat_tugas' => "{$wajib}|string|max:255",
            'tanggal_surat_tugas' => "{$wajib}|date",
            // Jenis Kegiatan tidak lagi ditampilkan di form (lihat wizard) — opsional.
            'id_jenis_kegiatan' => 'nullable|exists:jenis_kegiatan,id_jenis_kegiatan',
            'perihal' => "{$wajib}|string|max:2000",
            'uraian_tugas' => 'nullable|string',
            'id_pegawai_mitra_pelaksana' => "{$wajib}|exists:pegawai_mitra,id_pegawai_mitra",
            'tanggal_mulai' => "{$wajib}|date",
            'tanggal_selesai' => $isDraft ? 'nullable|date' : 'required|date|after_or_equal:tanggal_mulai',
            'pembebanan' => "{$wajib}|string|max:255",
            'no_form_permintaan' => 'nullable|string|max:255',
            'desa_asal' => "{$wajibDesa}|string|max:255",
            'kabupaten_asal' => "{$wajib}|string|max:255",
            'desa_tujuan' => "{$wajibDesa}|string|max:255",
            'kabupaten_tujuan' => "{$wajib}|string|max:255",
            'id_penandatangan_st' => 'nullable|exists:pegawai_mitra,id_pegawai_mitra',
            'id_ppk' => 'nullable|exists:pegawai_mitra,id_pegawai_mitra',
            'id_bendahara' => 'nullable|exists:pegawai_mitra,id_pegawai_mitra',

            // Nomor SPD ditetapkan server (tentukanNomorSpd): dari "Ambil Nomor" (otomatis) atau
            // diketik sendiri (manual, ditolak kalau sudah dipakai). Kosong saat "Selesai &
            // Simpan" -> nomornya diambilkan otomatis.
            'no_spd' => 'nullable|string|max:255',
            'no_spd_sumber' => 'nullable|in:otomatis,manual',
            'tanggal_spd' => $isDraft ? 'nullable|date' : ($butuhSpd ? 'required|date' : 'nullable|date'),
            'angkutan' => 'nullable|string|max:255',

            'rincian' => 'nullable|array',
            'rincian.uang_harian' => 'nullable|numeric|min:0',
            'rincian.transport' => 'nullable|numeric|min:0',
            'rincian.penginapan' => 'nullable|numeric|min:0',
            'rincian_hari' => 'nullable|array',
            'rincian_hari.uang_harian' => 'nullable|integer|min:0',
            'rincian_hari.transport' => 'nullable|integer|min:0',
            'rincian_hari.penginapan' => 'nullable|integer|min:0',

            'pengeluaran' => 'nullable|array',
            'pengeluaran.*.uraian' => 'required_with:pengeluaran|string',
            'pengeluaran.*.jenis' => 'required_with:pengeluaran|in:transportasi,akomodasi',
            'pengeluaran.*.nominal' => 'required_with:pengeluaran|numeric|min:0',

            'tanggal_kuitansi' => 'nullable|date',

            'pernyataan' => 'nullable|array',
            'pernyataan.tidak_pakai_kendaraan_dinas' => 'nullable|boolean',
            'pernyataan.tidak_menginap_hotel' => 'nullable|boolean',
            'pernyataan.keterlambatan' => 'nullable|boolean',
            'pernyataan_keterlambatan_keterangan' => 'nullable|string',

            'kesimpulan_hasil_kegiatan' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
            'tanggal_laporan' => 'nullable|date',

            'dokumentasi' => 'nullable|array',
            'dokumentasi.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf',
            // Dikirim array terpisah (bukan digabung 1 field per file) supaya tetap sinkron
            // dengan index array file "dokumentasi.*" di atas — lihat simpanDataTerkait().
            'dokumentasi_caption' => 'nullable|array',
            'dokumentasi_caption.*' => 'nullable|string|max:255',
            // Urutan foto hasil drag di wizard, isinya token "lama:{id_dokumentasi}" untuk
            // file yang sudah tersimpan dan "baru:{index dokumentasi.*}" untuk file baru.
            'dokumentasi_urutan' => 'nullable|array',
            'dokumentasi_urutan.*' => ['string', 'regex:/^(lama|baru):\d+$/'],
        ];
    }

    /**
     * Field-field PerjalananDinas dari hasil validasi, dipakai bareng oleh store() & update().
     */
    private function perjalananDinasAttributes(array $validated): array
    {
        return [
            // Field Data Umum & SPD boleh kosong saat disimpan sebagai draft (lihat
            // validationRules()), jadi semuanya dibaca dengan fallback null di sini —
            // bukan cuma yang memang selalu opsional.
            'id_pegawai_mitra_pelaksana' => $validated['id_pegawai_mitra_pelaksana'] ?? null,
            'no_surat_tugas' => $validated['no_surat_tugas'] ?? null,
            'tanggal_surat_tugas' => $validated['tanggal_surat_tugas'] ?? null,
            'perihal' => $validated['perihal'] ?? null,
            'uraian_tugas' => $validated['uraian_tugas'] ?? null,
            'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'pembebanan' => $validated['pembebanan'] ?? null,
            'no_form_permintaan' => $validated['no_form_permintaan'] ?? null,
            'id_jenis_kegiatan' => $validated['id_jenis_kegiatan'] ?? null,
            'jenis_perjadin' => $validated['jenis_perjadin'],
            'no_spd' => $validated['no_spd'] ?? null,
            'tanggal_spd' => $validated['tanggal_spd'] ?? null,
            'angkutan' => $validated['angkutan'] ?? null,
            'desa_asal' => $validated['desa_asal'] ?? null,
            'kabupaten_asal' => $validated['kabupaten_asal'] ?? null,
            'desa_tujuan' => $validated['desa_tujuan'] ?? null,
            'kabupaten_tujuan' => $validated['kabupaten_tujuan'] ?? null,
            'id_penandatangan_st' => $validated['id_penandatangan_st'] ?? null,
            'id_ppk' => $validated['id_ppk'] ?? null,
            'id_bendahara' => $validated['id_bendahara'] ?? null,
            'status_draft' => $validated['status_draft'],
        ];
    }

    /**
     * Simpan rincian/pengeluaran/kuitansi/pernyataan/laporan/dokumentasi baru untuk sebuah
     * PerjalananDinas yang baru dibuat/baru di-reset relasinya (lihat update()).
     */
    private function simpanDataTerkait(PerjalananDinas $perjalananDinas, array $validated, Request $request): void
    {
        foreach (['uang_harian', 'transport', 'penginapan'] as $jenisKomponen) {
            $nominal = $validated['rincian'][$jenisKomponen] ?? 0;
            if ($nominal > 0) {
                RincianBiaya::create([
                    'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                    'jenis_komponen' => $jenisKomponen,
                    'jumlah_hari' => $validated['rincian_hari'][$jenisKomponen] ?? null,
                    'nominal' => $nominal,
                ]);
            }
        }

        foreach ($validated['pengeluaran'] ?? [] as $pengeluaran) {
            PengeluaranRiil::create([
                'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                'uraian' => $pengeluaran['uraian'],
                'jenis' => $pengeluaran['jenis'],
                'nominal' => $pengeluaran['nominal'],
            ]);
        }

        if (!empty($validated['tanggal_kuitansi'])) {
            Kuitansi::updateOrCreate(
                ['id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas],
                ['tanggal_kuitansi' => $validated['tanggal_kuitansi']]
            );
        } else {
            Kuitansi::where('id_perjalanan_dinas', $perjalananDinas->id_perjalanan_dinas)->delete();
        }

        foreach (['tidak_pakai_kendaraan_dinas', 'tidak_menginap_hotel', 'keterlambatan'] as $jenisKondisi) {
            if (!empty($validated['pernyataan'][$jenisKondisi])) {
                SuratPernyataan::create([
                    'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                    'jenis_kondisi' => $jenisKondisi,
                    'keterangan' => $jenisKondisi === 'keterlambatan' ? ($validated['pernyataan_keterlambatan_keterangan'] ?? null) : null,
                ]);
            }
        }

        if (!empty($validated['kesimpulan_hasil_kegiatan'])) {
            LaporanPerjadin::updateOrCreate(
                ['id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas],
                [
                    'kesimpulan_hasil_kegiatan' => $validated['kesimpulan_hasil_kegiatan'],
                    'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
                    'tanggal_laporan' => $validated['tanggal_laporan'] ?? null,
                ]
            );
        } else {
            LaporanPerjadin::where('id_perjalanan_dinas', $perjalananDinas->id_perjalanan_dinas)->delete();
        }

        // File baru ditaruh di belakang file yang sudah ada; kalau wizard mengirim urutan
        // hasil drag (dokumentasi_urutan), urutan itu yang dipakai — lihat aturUrutanDokumentasi().
        $dokumentasiCaptions = $request->input('dokumentasi_caption', []);
        $urutanTerakhir = (int) Dokumentasi::where('id_perjalanan_dinas', $perjalananDinas->id_perjalanan_dinas)->max('urutan');
        $idDokumentasiBaru = [];
        foreach ($request->file('dokumentasi', []) as $index => $file) {
            $path = Storage::disk('public')->putFile('dokumentasi', $file);
            $idDokumentasiBaru[$index] = Dokumentasi::create([
                'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                'nama_file' => $file->getClientOriginalName(),
                'caption' => $dokumentasiCaptions[$index] ?? null,
                'urutan' => $urutanTerakhir + $index + 1,
                'path_file' => $path,
                'uploaded_at' => now(),
            ])->id_dokumentasi;
        }

        $this->aturUrutanDokumentasi($perjalananDinas, $validated['dokumentasi_urutan'] ?? [], $idDokumentasiBaru);
    }

    /**
     * Terapkan urutan foto dari wizard. Token "lama:{id}" menunjuk file yang sudah tersimpan
     * (hanya milik perjalanan dinas ini yang diproses), "baru:{index}" menunjuk file yang
     * barusan diunggah di request yang sama.
     */
    private function aturUrutanDokumentasi(PerjalananDinas $perjalananDinas, array $urutan, array $idDokumentasiBaru): void
    {
        foreach (array_values($urutan) as $posisi => $token) {
            [$jenis, $kunci] = explode(':', $token, 2);
            $idDokumentasi = $jenis === 'lama' ? (int) $kunci : ($idDokumentasiBaru[(int) $kunci] ?? null);
            if (!$idDokumentasi) {
                continue;
            }

            Dokumentasi::where('id_dokumentasi', $idDokumentasi)
                ->where('id_perjalanan_dinas', $perjalananDinas->id_perjalanan_dinas)
                ->update(['urutan' => $posisi + 1]);
        }
    }

    public function store(Request $request)
    {
        // Pemohon = identitas pegawai user yang sedang login, bukan input dari client.
        $pemohonId = $request->user()->id_pegawai_mitra;
        if (!$pemohonId) {
            return redirect()->route('profile.edit')
                ->with('status', 'pegawai-required');
        }

        // SPD wajib untuk jenis "biasa" maupun "dalam kota > 8 jam" (diperlakukan sama).
        $butuhSpd = in_array($request->input('jenis_perjadin'), $this->jenisSpdWajib, true);
        $isDraft = $request->input('status_draft') !== 'selesai';
        $validated = $request->validate($this->validationRules($butuhSpd, $isDraft, (string) $request->input('jenis_perjadin')));

        $noSpd = DB::transaction(function () use ($validated, $request, $pemohonId) {
            $validated['no_spd'] = $this->tentukanNomorSpd($validated);
            $perjalananDinas = PerjalananDinas::create(array_merge(
                $this->perjalananDinasAttributes($validated),
                ['id_pemohon' => $pemohonId]
            ));

            $this->simpanDataTerkait($perjalananDinas, $validated, $request);
            $this->catatPemilihanWilayah($validated);

            return $perjalananDinas->no_spd;
        });

        return redirect()->route('dashboard')
            ->with('success', 'Perjalanan dinas berhasil disimpan.' . ($noSpd ? " Nomor SPD: {$noSpd}." : ''))
            ->with('warning', $this->peringatanNomorSpdBerubah($request, $noSpd));
    }

    public function update(Request $request, PerjalananDinas $perjalananDinas)
    {
        abort_unless($perjalananDinas->bisaDiubahOleh($request->user()->id_pegawai_mitra), 403);

        $butuhSpd = in_array($request->input('jenis_perjadin'), $this->jenisSpdWajib, true);
        $isDraft = $request->input('status_draft') !== 'selesai';
        $validated = $request->validate($this->validationRules($butuhSpd, $isDraft, (string) $request->input('jenis_perjadin')));

        DB::transaction(function () use ($validated, $request, $perjalananDinas) {
            $validated['no_spd'] = $this->tentukanNomorSpd($validated, $perjalananDinas);
            $perjalananDinas->update($this->perjalananDinasAttributes($validated));

            // Rincian/pengeluaran/pernyataan gampangnya dibongkar-pasang ulang (bukan
            // di-diff satu-satu) — datanya kecil dan tidak ada identitas per-baris yang
            // perlu dipertahankan. Dokumentasi (file upload) sengaja TIDAK dihapus di sini,
            // hanya ditambah, karena tidak ada UI untuk menghapus file yang sudah ada.
            $perjalananDinas->rincianBiaya()->delete();
            $perjalananDinas->pengeluaranRiil()->delete();
            $perjalananDinas->suratPernyataan()->delete();

            $this->simpanDataTerkait($perjalananDinas, $validated, $request);
        });

        return redirect()->route('dashboard')
            ->with('success', 'Perjalanan dinas berhasil diperbarui.' . ($perjalananDinas->no_spd ? " Nomor SPD: {$perjalananDinas->no_spd}." : ''))
            ->with('warning', $this->peringatanNomorSpdBerubah($request, $perjalananDinas->no_spd));
    }

    /**
     * Buka kembali perjalanan dinas yang statusnya sudah "selesai" (final/terkunci)
     * supaya bisa diedit lagi — status_draft dikembalikan ke draft_sesi1. Hanya
     * pemohonnya sendiri yang boleh, terlepas dari status saat ini.
     */
    public function bukaKembali(Request $request, PerjalananDinas $perjalananDinas)
    {
        abort_unless($perjalananDinas->dimilikiOleh($request->user()->id_pegawai_mitra), 403);

        $perjalananDinas->update(['status_draft' => 'draft_sesi1']);

        return redirect()->route('perjalanan-dinas.edit', $perjalananDinas)
            ->with('success', 'Perjalanan dinas dibuka kembali dan siap diedit.');
    }

    public function destroy(Request $request, PerjalananDinas $perjalananDinas)
    {
        abort_unless($perjalananDinas->bisaDiubahOleh($request->user()->id_pegawai_mitra), 403);

        $perjalananDinas->load('dokumentasi');
        foreach ($perjalananDinas->dokumentasi as $dokumentasi) {
            Storage::disk('public')->delete($dokumentasi->path_file);
        }

        $perjalananDinas->delete();

        return redirect()->back()->with('success', 'Perjalanan dinas berhasil dihapus.');
    }

    /**
     * Hapus satu file dokumentasi yang sudah tersimpan — dipanggil lewat tombol "x" di
     * thumbnail pada step Dokumentasi (fetch/AJAX, bukan submit form utuh, supaya tidak
     * perlu keluar dari wizard hanya untuk menghapus satu file).
     */
    public function hapusDokumentasi(Request $request, PerjalananDinas $perjalananDinas, Dokumentasi $dokumentasi)
    {
        abort_unless($perjalananDinas->bisaDiubahOleh($request->user()->id_pegawai_mitra), 403);
        abort_unless($dokumentasi->id_perjalanan_dinas === $perjalananDinas->id_perjalanan_dinas, 404);

        Storage::disk('public')->delete($dokumentasi->path_file);
        $dokumentasi->delete();

        return response()->noContent();
    }

    /**
     * Ubah caption foto Dokumentasi yang SUDAH tersimpan — endpoint terpisah (bukan
     * lewat update() form utama) karena update() sengaja tidak pernah mengubah baris
     * Dokumentasi yang sudah ada, lihat komentar di simpanDataTerkait().
     */
    public function updateCaptionDokumentasi(Request $request, PerjalananDinas $perjalananDinas, Dokumentasi $dokumentasi)
    {
        abort_unless($perjalananDinas->bisaDiubahOleh($request->user()->id_pegawai_mitra), 403);
        abort_unless($dokumentasi->id_perjalanan_dinas === $perjalananDinas->id_perjalanan_dinas, 404);

        $validated = $request->validate([
            'caption' => 'nullable|string|max:255',
        ]);

        $dokumentasi->update(['caption' => $validated['caption'] ?? null]);

        return response()->noContent();
    }

    /**
     * Template Excel untuk bikin banyak perjalanan dinas sekaligus (mis. 1 pemohon untuk
     * 5 pelaksana) — semua field kecuali Dokumentasi (itu tetap diunggah satu-satu lewat
     * halaman Edit sesudahnya, karena berupa file).
     */
    public function importTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Perjalanan Dinas');

        // Urutannya mengikuti KOLOM_IMPORT. No SPD sengaja tidak ada: nomornya selalu
        // diberikan otomatis oleh sistem saat import.
        $headers = [
            'Jenis Perjalanan Dinas', 'No Surat Tugas', 'Tanggal Surat Tugas (YYYY-MM-DD)', 'Jenis Kegiatan', 'Perihal', 'Uraian Tugas',
            'NIP Pelaksana', 'Tanggal Mulai (YYYY-MM-DD)', 'Tanggal Selesai (YYYY-MM-DD)', 'Pembebanan (MAK)',
            'Desa/Kel Asal', 'Kabupaten/Kota Asal', 'Desa/Kel Tujuan', 'Kabupaten/Kota Tujuan',
            'NIP Penandatangan ST (kosongkan = otomatis)', 'NIP PPK (kosongkan = otomatis)', 'NIP Bendahara (kosongkan = otomatis)',
            'Tanggal SPD (YYYY-MM-DD)', 'Angkutan', 'Tingkat Biaya',
            'Uang Harian (Rp)', 'Transport (Rp)', 'Penginapan (Rp)',
            'Pengeluaran 1 - Jenis', 'Pengeluaran 1 - Uraian', 'Pengeluaran 1 - Nominal',
            'Pengeluaran 2 - Jenis', 'Pengeluaran 2 - Uraian', 'Pengeluaran 2 - Nominal',
            'Pengeluaran 3 - Jenis', 'Pengeluaran 3 - Uraian', 'Pengeluaran 3 - Nominal',
            'Tanggal Kuitansi (YYYY-MM-DD)',
            'Tidak Menggunakan Kendaraan Dinas (Ya/Tidak)', 'Tidak Menginap di Hotel (Ya/Tidak)',
            'Keterlambatan Pengajuan Tagihan (Ya/Tidak)', 'Keterangan Keterlambatan',
            'Kesimpulan Hasil Kegiatan', 'Tindak Lanjut (1 poin per baris, Alt+Enter utk baris baru)', 'Status (Draft/Selesai)',
            'Jumlah Hari Uang Harian', 'Jumlah Hari Transport', 'Jumlah Hari Penginapan',
            'No Form Permintaan',
        ];
        $kol = fn (string $field) => self::kolomImport($field);

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        foreach ($headers as $i => $header) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setWidth(22);
        }
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1B449C');
        $sheet->getStyle('A1:' . $lastCol . '1')->getAlignment()->setWrapText(true)->setVertical('center');
        $sheet->getRowDimension(1)->setRowHeight(36);
        // Header + baris contoh dibekukan supaya contohnya selalu terlihat saat mengisi.
        $sheet->freezePane('A3');

        // Kolom tanggal & nomor diformat teks — kalau dibiarkan format tanggal bawaan
        // Excel, nilainya jadi serial number pas dibaca ulang PhpSpreadsheet dan gampang
        // salah tafsir. Lebih aman minta diketik "YYYY-MM-DD" langsung.
        $kolomTeks = ['no_surat_tugas', 'tanggal_surat_tugas', 'nip_pelaksana', 'tanggal_mulai', 'tanggal_selesai',
            'nip_penandatangan_st', 'nip_ppk', 'nip_bendahara', 'tanggal_spd', 'tanggal_kuitansi', 'no_form_permintaan'];
        foreach ($kolomTeks as $field) {
            $sheet->getStyle($kol($field) . '2:' . $kol($field) . '500')->getNumberFormat()->setFormatCode('@');
        }

        // Baris 2 = contoh pengisian. Ditandai "[CONTOH]" di kolom Perihal; baris bertanda
        // itu SELALU dilewati saat import, jadi boleh dibiarkan di file.
        $contoh = [
            'Biasa', 'B-1122/62130/KU.340/2026', '2026-08-13', 'Pendataan Lapangan', self::PENANDA_CONTOH . ' Pendataan Sakernas Agustus 2026', '',
            '19990307 202104 1 001', '2026-08-15', '2026-08-19', 'DIPA BPS Kabupaten Murung Raya',
            'Desa Contoh', 'Kabupaten Murung Raya', 'Desa Tujuan Contoh', 'Kabupaten Murung Raya',
            '', '', '',
            '2026-08-13', 'Kendaraan Umum', 'C',
            500000, 300000, 700000,
            '', '', '',
            '', '', '',
            '', '', '',
            '',
            'Tidak', 'Tidak', 'Tidak', '',
            '', '', 'Draft',
            5, '', 4,
            'FP-001/2026',
        ];
        $sheet->fromArray($contoh, null, 'A2', true);
        $gayaContoh = $sheet->getStyle('A2:' . $lastCol . '2');
        $gayaContoh->getFont()->setItalic(true)->getColor()->setRGB('8A6D00');
        $gayaContoh->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF4CC');
        $sheet->getComment('A2')->setAuthor('SIMPEL')->setWidth('260pt')->setHeight('70pt')
            ->getText()->createTextRun('Baris CONTOH: tidak ikut diimport selama kolom Perihal diawali "[CONTOH]". '
                . 'Mulai isi data Anda di baris 3. No SPD tidak perlu diisi — diberikan otomatis.');

        $addDropdown = function (string $col, array $options, string $title) use ($sheet) {
            for ($row = 2; $row <= 200; $row++) {
                $validation = $sheet->getCell($col . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setPromptTitle($title);
                $validation->setFormula1('"' . implode(',', $options) . '"');
            }
        };

        $addDropdown($kol('jenis_perjadin'), array_values(self::JENIS_PERJADIN_LABELS), 'Jenis Perjalanan Dinas');
        $addDropdown($kol('jenis_kegiatan'), JenisKegiatan::orderBy('nama_kegiatan')->pluck('nama_kegiatan')->all(), 'Jenis Kegiatan');
        $addDropdown($kol('tingkat_biaya'), ['A', 'B', 'C'], 'Tingkat Biaya');
        foreach ([1, 2, 3] as $n) {
            $addDropdown($kol("pengeluaran{$n}_jenis"), ['Transportasi', 'Akomodasi'], 'Jenis Pengeluaran');
        }
        $addDropdown($kol('pernyataan_tidak_pakai_kendaraan_dinas'), self::YA_TIDAK, 'Tidak Menggunakan Kendaraan Dinas');
        $addDropdown($kol('pernyataan_tidak_menginap_hotel'), self::YA_TIDAK, 'Tidak Menginap di Hotel');
        $addDropdown($kol('pernyataan_keterlambatan'), self::YA_TIDAK, 'Keterlambatan Pengajuan Tagihan');
        $addDropdown($kol('status_draft'), ['Draft', 'Selesai'], 'Status');

        // Sheet petunjuk singkat (import hanya membaca sheet pertama).
        $petunjuk = $spreadsheet->createSheet();
        $petunjuk->setTitle('Petunjuk');
        $petunjuk->fromArray([
            ['Petunjuk Pengisian Template Import Perjalanan Dinas'],
            [''],
            ['1. Isi data di sheet "Template Perjalanan Dinas", mulai dari baris 3. Satu baris = satu perjalanan dinas.'],
            ['2. Baris 2 (kuning) adalah CONTOH. Baris yang kolom Perihal-nya diawali "[CONTOH]" tidak akan ikut diimport, jadi boleh dibiarkan.'],
            ['   Jika ingin memakai baris 2 untuk data sungguhan, hapus tulisan "[CONTOH]" dari kolom Perihal.'],
            ['3. No SPD tidak perlu diisi — sistem memberikan nomor SPD berikutnya secara otomatis (untuk jenis Biasa dan Dalam Kota > 8 Jam).'],
            ['4. Tanggal diketik dengan format YYYY-MM-DD, contoh 2026-08-15.'],
            ['5. NIP Penandatangan ST, PPK, dan Bendahara boleh dikosongkan — otomatis memakai pejabat yang terdaftar.'],
            ['6. Status "Draft" boleh belum lengkap; status "Selesai" wajib lengkap sesuai form.'],
            ['7. Dokumentasi (foto/file) tidak ikut diimport — unggah lewat menu Edit setelah data masuk.'],
        ], null, 'A1');
        $petunjuk->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $petunjuk->getColumnDimension('A')->setWidth(130);

        $spreadsheet->setActiveSheetIndex(0);
        $sheet->setSelectedCell('A3');
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template_perjalanan_dinas.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Huruf kolom Excel untuk sebuah field di KOLOM_IMPORT (mis. 'perihal' → 'E'). */
    private static function kolomImport(string $field): string
    {
        return Coordinate::stringFromColumnIndex(array_search($field, self::KOLOM_IMPORT, true) + 1);
    }

    /**
     * Import banyak perjalanan dinas sekaligus dari file Excel (lihat importTemplate()).
     * Tiap baris = 1 perjalanan dinas baru, pemohonnya user yang sedang login. Baris yang
     * gagal validasi dilewati (dilaporkan alasannya), baris lain tetap diproses.
     */
    public function import(Request $request)
    {
        $pemohonId = $request->user()->id_pegawai_mitra;
        if (!$pemohonId) {
            return redirect()->route('profile.edit')->with('status', 'pegawai-required');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        // Nilai mentah (tanpa format tampilan Excel) supaya tanggal yang terlanjur diketik
        // sebagai tanggal Excel bisa dibaca tepat lewat normalisasiTanggalImport().
        $rows = $spreadsheet->getSheet(0)->toArray(null, true, false, false);

        // Template versi lama masih punya kolom "No SPD" — kolom itu dibuang supaya urutan
        // kolom lainnya tetap cocok. Nomor SPD selalu diberikan otomatis.
        $kolomNoSpdLama = collect($rows[0] ?? [])
            ->search(fn ($h) => str_starts_with(strtolower(trim((string) $h)), 'no spd'));

        $pejabatOtomatis = PegawaiMitra::whereIn('peran_pejabat', ['kepala_satker', 'ppk', 'bendahara'])
            ->get()->keyBy('peran_pejabat');
        $jenisKegiatanByNama = JenisKegiatan::pluck('id_jenis_kegiatan', 'nama_kegiatan');

        $berhasil = 0;
        $gagal = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue; // baris header
            }
            $nomorBaris = $i + 1;
            if ($kolomNoSpdLama !== false) {
                array_splice($row, $kolomNoSpdLama, 1);
            }

            $data = array_combine(
                self::KOLOM_IMPORT,
                array_pad(array_slice($row, 0, count(self::KOLOM_IMPORT)), count(self::KOLOM_IMPORT), null)
            );
            $data = array_map(fn ($v) => $v !== null ? trim((string) $v) : '', $data);
            foreach (['tanggal_surat_tugas', 'tanggal_mulai', 'tanggal_selesai', 'tanggal_spd', 'tanggal_kuitansi'] as $kolomTanggal) {
                $data[$kolomTanggal] = $this->normalisasiTanggalImport($data[$kolomTanggal]);
            }

            // Baris kosong (mis. sisa baris dropdown yang belum diisi) dilewati diam-diam.
            if ($data['no_surat_tugas'] === '' && $data['perihal'] === '') {
                continue;
            }
            // Baris contoh dari template tidak ikut diimport (termasuk contoh template lama).
            if ($this->barisContohImport($data)) {
                continue;
            }

            $jenisPerjadin = array_search($data['jenis_perjadin'], self::JENIS_PERJADIN_LABELS, true);
            if ($jenisPerjadin === false) {
                $jenisPerjadin = in_array($data['jenis_perjadin'], array_keys(self::JENIS_PERJADIN_LABELS), true)
                    ? $data['jenis_perjadin']
                    : null;
            }

            if ($jenisPerjadin === null) {
                $gagal[] = "Baris {$nomorBaris}: Jenis Perjalanan Dinas \"{$data['jenis_perjadin']}\" tidak dikenal (pilih dari dropdown: "
                    . implode(' / ', self::JENIS_PERJADIN_LABELS) . ').';
                continue;
            }

            $idJenisKegiatan = $jenisKegiatanByNama[$data['jenis_kegiatan']] ?? null;
            // NIP dicocokkan tanpa spasi, jadi "19990307 202104 1 001" = "199903072021041001".
            $cariNip = fn (string $nip) => PegawaiMitra::whereRaw("REPLACE(nip, ' ', '') = ?", [str_replace(' ', '', $nip)])
                ->value('id_pegawai_mitra');
            $nipTakDikenal = collect(['nip_pelaksana' => 'NIP Pelaksana', 'nip_penandatangan_st' => 'NIP Penandatangan ST',
                'nip_ppk' => 'NIP PPK', 'nip_bendahara' => 'NIP Bendahara'])
                ->filter(fn ($label, $kolom) => $data[$kolom] !== '' && !$cariNip($data[$kolom]))
                ->map(fn ($label, $kolom) => "{$label} {$data[$kolom]}");
            if ($nipTakDikenal->isNotEmpty()) {
                $gagal[] = "Baris {$nomorBaris}: " . $nipTakDikenal->implode(', ') . ' tidak terdaftar di data Pegawai/Mitra.';
                continue;
            }

            $idPelaksana = $data['nip_pelaksana'] !== '' ? $cariNip($data['nip_pelaksana']) : null;
            $resolvePejabat = fn (string $nipKolom, string $peran) => $data[$nipKolom] !== ''
                ? $cariNip($data[$nipKolom])
                : $pejabatOtomatis->get($peran)?->id_pegawai_mitra;
            $idPenandatanganSt = $resolvePejabat('nip_penandatangan_st', 'kepala_satker');
            $idPpk = $resolvePejabat('nip_ppk', 'ppk');
            $idBendahara = $resolvePejabat('nip_bendahara', 'bendahara');

            $butuhSpd = in_array($jenisPerjadin, $this->jenisSpdWajib, true);

            $pengeluaran = [];
            foreach ([1, 2, 3] as $n) {
                $jenis = strtolower($data["pengeluaran{$n}_jenis"]) === 'akomodasi' ? 'akomodasi' : 'transportasi';
                if ($data["pengeluaran{$n}_uraian"] !== '' || $data["pengeluaran{$n}_nominal"] !== '') {
                    $pengeluaran[] = [
                        'jenis' => $jenis,
                        'uraian' => $data["pengeluaran{$n}_uraian"],
                        'nominal' => $this->angkaImport($data["pengeluaran{$n}_nominal"]) ?? 0,
                    ];
                }
            }

            $tindakLanjut = str_replace(["\r\n", "\r"], "\n", $data['tindak_lanjut']);

            $validated = [
                'jenis_perjadin' => $jenisPerjadin,
                'status_draft' => strtolower($data['status_draft']) === 'selesai' ? 'selesai' : 'draft_sesi1',
                'no_surat_tugas' => $data['no_surat_tugas'],
                'tanggal_surat_tugas' => $data['tanggal_surat_tugas'] ?: null,
                'id_jenis_kegiatan' => $idJenisKegiatan,
                'perihal' => $data['perihal'],
                'uraian_tugas' => $data['uraian_tugas'] ?: null,
                'id_pegawai_mitra_pelaksana' => $idPelaksana,
                'tanggal_mulai' => $data['tanggal_mulai'] ?: null,
                'tanggal_selesai' => $data['tanggal_selesai'] ?: null,
                'pembebanan' => $data['pembebanan'],
                'no_form_permintaan' => $data['no_form_permintaan'] ?: null,
                'desa_asal' => $data['desa_asal'],
                'kabupaten_asal' => $data['kabupaten_asal'],
                'desa_tujuan' => $data['desa_tujuan'],
                'kabupaten_tujuan' => $data['kabupaten_tujuan'],
                'id_penandatangan_st' => $idPenandatanganSt,
                'id_ppk' => $idPpk,
                'id_bendahara' => $idBendahara,
                'no_spd' => null, // diberikan otomatis di bawah
                'tanggal_spd' => $data['tanggal_spd'] ?: null,
                'angkutan' => $data['angkutan'] ?: null,
                'rincian' => [
                    'uang_harian' => $this->angkaImport($data['uang_harian']) ?? 0,
                    'transport' => $this->angkaImport($data['transport']) ?? 0,
                    'penginapan' => $this->angkaImport($data['penginapan']) ?? 0,
                ],
                'rincian_hari' => [
                    'uang_harian' => $this->angkaImport($data['uang_harian_hari'], true),
                    'transport' => $this->angkaImport($data['transport_hari'], true),
                    'penginapan' => $this->angkaImport($data['penginapan_hari'], true),
                ],
                'pengeluaran' => $pengeluaran,
                'tanggal_kuitansi' => $data['tanggal_kuitansi'] ?: null,
                'pernyataan' => [
                    'tidak_pakai_kendaraan_dinas' => strtolower($data['pernyataan_tidak_pakai_kendaraan_dinas']) === 'ya',
                    'tidak_menginap_hotel' => strtolower($data['pernyataan_tidak_menginap_hotel']) === 'ya',
                    'keterlambatan' => strtolower($data['pernyataan_keterlambatan']) === 'ya',
                ],
                'pernyataan_keterlambatan_keterangan' => $data['pernyataan_keterlambatan_keterangan'] ?: null,
                'kesimpulan_hasil_kegiatan' => $data['kesimpulan_hasil_kegiatan'] ?: null,
                'tindak_lanjut' => $tindakLanjut ?: null,
            ];

            $validator = Validator::make(
                $validated,
                $this->validationRules($butuhSpd, $validated['status_draft'] !== 'selesai', $jenisPerjadin),
                self::PESAN_VALIDASI_IMPORT,
                self::NAMA_KOLOM_IMPORT
            );
            if ($validator->fails()) {
                $gagal[] = "Baris {$nomorBaris}: " . $validator->errors()->first();
                continue;
            }

            DB::transaction(function () use ($validated, $request, $pemohonId) {
                $validated['no_spd'] = $this->tentukanNomorSpd($validated, null, true);
                $perjalananDinas = PerjalananDinas::create(array_merge(
                    $this->perjalananDinasAttributes($validated),
                    ['id_pemohon' => $pemohonId]
                ));
                $this->simpanDataTerkait($perjalananDinas, $validated, $request);
                $this->catatPemilihanWilayah($validated);
            });
            $berhasil++;
        }

        if ($berhasil === 0 && !$gagal) {
            return back()->with('error', 'Tidak ada data yang diimport. Isi data mulai baris 3 — baris contoh '
                . '(Perihal diawali ' . self::PENANDA_CONTOH . ') tidak ikut diimport.');
        }

        $pesan = "Import selesai: {$berhasil} perjalanan dinas berhasil dibuat.";
        if ($gagal) {
            $pesan .= ' ' . count($gagal) . ' baris gagal: ' . implode(' | ', array_slice($gagal, 0, 10));
            if (count($gagal) > 10) {
                $pesan .= ' (dan ' . (count($gagal) - 10) . ' lainnya)';
            }
            return back()->with('error', $pesan);
        }

        return back()->with('success', $pesan);
    }

    /**
     * Baris contoh di template import: Perihal diawali PENANDA_CONTOH, atau persis contoh
     * template versi lama (sebelum ada penanda) yang sering ikut terunggah.
     */
    private function barisContohImport(array $data): bool
    {
        if ($this->diawaliPenandaContoh($data['perihal'])) {
            return true;
        }

        return $data['no_surat_tugas'] === 'B-1122/62130/KU.340/2026'
            && $data['perihal'] === 'Pendataan Sakernas Agustus 2026'
            && $data['desa_asal'] === 'Desa Contoh';
    }
}
