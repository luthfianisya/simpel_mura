<?php

namespace App\Http\Controllers;

use App\Models\Dokumentasi;
use App\Models\JenisKegiatan;
use App\Models\Kuitansi;
use App\Models\LaporanPerjadin;
use App\Models\PegawaiMitra;
use App\Models\PengeluaranRiil;
use App\Models\PerjalananDinas;
use App\Models\RincianBiaya;
use App\Models\SuratPernyataan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PerjalananDinasController extends Controller
{
    protected array $jenisSpdWajib = ['biasa', 'dalam_kota_lebih_8_jam'];

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

        $butuhSpd = in_array($jenis, $this->jenisSpdWajib, true);
        $pemohonPegawai = $request->user()->pegawaiMitra;
        $pegawaiMitraList = PegawaiMitra::orderBy('nama')->get();
        $jenisKegiatanList = JenisKegiatan::orderBy('nama_kegiatan')->get();

        // Fitur "Copy": duplikat record jadi draft baru. old('field') di view otomatis
        // terisi dari record sumber lewat flashInput, tanpa perlu ubah field manapun di view.
        // Guard hasOldInput(): kalau ini adalah redirect-back akibat validasi gagal saat submit,
        // JANGAN timpa old-input yang sudah ada dengan data sumber lagi.
        if (!$request->session()->hasOldInput()) {
            $sumber = PerjalananDinas::with(['rincianBiaya', 'pengeluaranRiil', 'suratPernyataan', 'kuitansi', 'laporan'])
                ->find($request->query('from'));
            if ($sumber) {
                session()->flashInput($sumber->toFormPrefill());
            }
        }

        // No. Surat Tugas disimpan lengkap format "B-{angka}/62130/KU.340/{tahun}",
        // tapi di form user cuma input angkanya saja.
        $oldNoSuratTugas = old('no_surat_tugas');
        $noSuratTugasAngka = '';
        if ($oldNoSuratTugas && preg_match('/^B-(\d+)\/62130\/KU\.340\/\d{4}$/', $oldNoSuratTugas, $m)) {
            $noSuratTugasAngka = $m[1];
        }

        return view('perjalanan-dinas.wizard', compact(
            'jenis', 'butuhSpd', 'pemohonPegawai', 'pegawaiMitraList', 'jenisKegiatanList',
            'oldNoSuratTugas', 'noSuratTugasAngka'
        ));
    }

    /**
     * Nomor SPD berikutnya yang belum pernah dipakai, format: {urut}/62130/KU.340/{tahun}.
     * Bagian "62130/KU.340" tetap, hanya nomor urut & tahun yang berubah.
     */
    public function nomorSpdBerikutnya(Request $request)
    {
        $tahun = (int) $request->query('tahun', now()->year);

        $nomorTerbesar = 0;
        PerjalananDinas::whereNotNull('no_spd')
            ->where('no_spd', 'like', "%/62130/KU.340/{$tahun}")
            ->pluck('no_spd')
            ->each(function ($noSpd) use ($tahun, &$nomorTerbesar) {
                if (preg_match('/^(\d+)\/62130\/KU\.340\/' . $tahun . '$/', trim($noSpd), $m)) {
                    $nomorTerbesar = max($nomorTerbesar, (int) $m[1]);
                }
            });

        return response()->json([
            'no_spd' => ($nomorTerbesar + 1) . "/62130/KU.340/{$tahun}",
        ]);
    }

    public function store(Request $request)
    {
        // Pemohon = identitas pegawai user yang sedang login, bukan input dari client.
        $pemohonId = $request->user()->id_pegawai_mitra;
        if (!$pemohonId) {
            return redirect()->route('profile.edit')
                ->with('status', 'pegawai-required');
        }

        // SPD wajib untuk jenis "biasa". Untuk "dalam kota > 8 jam", wajib-tidaknya
        // ditentukan oleh toggle "Apakah SPD?" di Data Umum (field is_spd).
        $jenisPerjadin = $request->input('jenis_perjadin');
        $butuhSpd = $jenisPerjadin === 'biasa'
            || ($jenisPerjadin === 'dalam_kota_lebih_8_jam' && $request->boolean('is_spd'));

        $validated = $request->validate([
            'jenis_perjadin' => 'required|in:biasa,dalam_kota_kurang_8_jam,dalam_kota_lebih_8_jam',
            'is_spd' => 'nullable|boolean',
            'no_surat_tugas' => 'required|string|max:255',
            'tanggal_surat_tugas' => 'required|date',
            'id_jenis_kegiatan' => 'required|exists:jenis_kegiatan,id_jenis_kegiatan',
            'perihal' => 'required|string|max:255',
            'uraian_tugas' => 'nullable|string',
            'id_pegawai_mitra_pelaksana' => 'required|exists:pegawai_mitra,id_pegawai_mitra',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'pembebanan' => 'required|string|max:255',
            'desa_asal' => 'required|string|max:255',
            'kabupaten_asal' => 'required|string|max:255',
            'desa_tujuan' => 'required|string|max:255',
            'kabupaten_tujuan' => 'required|string|max:255',
            'id_penandatangan_st' => 'nullable|exists:pegawai_mitra,id_pegawai_mitra',
            'id_ppk' => 'nullable|exists:pegawai_mitra,id_pegawai_mitra',
            'id_bendahara' => 'nullable|exists:pegawai_mitra,id_pegawai_mitra',

            'no_spd' => $butuhSpd ? 'required|string|max:255' : 'nullable|string|max:255',
            'tanggal_spd' => $butuhSpd ? 'required|date' : 'nullable|date',
            'angkutan' => 'nullable|string|max:255',

            'rincian' => 'required|array|min:1',
            'rincian.*.jenis_komponen' => 'required|in:uang_harian,transport,penginapan',
            'rincian.*.nominal' => 'required|numeric|min:0',

            'pengeluaran' => 'nullable|array',
            'pengeluaran.*.uraian' => 'required_with:pengeluaran|string|max:255',
            'pengeluaran.*.jenis' => 'required_with:pengeluaran|in:transportasi,akomodasi',
            'pengeluaran.*.nominal' => 'required_with:pengeluaran|numeric|min:0',

            'tanggal_kuitansi' => 'nullable|date',

            'pernyataan' => 'nullable|array',
            'pernyataan.*.jenis_kondisi' => 'required_with:pernyataan|in:tidak_pakai_kendaraan_dinas,tidak_menginap_hotel,keterlambatan',
            'pernyataan.*.keterangan' => 'nullable|string',

            'kesimpulan_hasil_kegiatan' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',

            'dokumentasi' => 'nullable|array',
            'dokumentasi.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf',
        ]);

        DB::transaction(function () use ($validated, $request, $pemohonId) {
            $perjalananDinas = PerjalananDinas::create([
                'id_pemohon' => $pemohonId,
                'id_pegawai_mitra_pelaksana' => $validated['id_pegawai_mitra_pelaksana'],
                'no_surat_tugas' => $validated['no_surat_tugas'],
                'tanggal_surat_tugas' => $validated['tanggal_surat_tugas'],
                'perihal' => $validated['perihal'],
                'uraian_tugas' => $validated['uraian_tugas'] ?? null,
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'pembebanan' => $validated['pembebanan'],
                'id_jenis_kegiatan' => $validated['id_jenis_kegiatan'],
                'jenis_perjadin' => $validated['jenis_perjadin'],
                'no_spd' => $validated['no_spd'] ?? null,
                'tanggal_spd' => $validated['tanggal_spd'] ?? null,
                'angkutan' => $validated['angkutan'] ?? null,
                'desa_asal' => $validated['desa_asal'],
                'kabupaten_asal' => $validated['kabupaten_asal'],
                'desa_tujuan' => $validated['desa_tujuan'],
                'kabupaten_tujuan' => $validated['kabupaten_tujuan'],
                'id_penandatangan_st' => $validated['id_penandatangan_st'] ?? null,
                'id_ppk' => $validated['id_ppk'] ?? null,
                'id_bendahara' => $validated['id_bendahara'] ?? null,
                'status_draft' => !empty($validated['kesimpulan_hasil_kegiatan']) ? 'selesai' : 'draft_sesi1',
            ]);

            foreach ($validated['rincian'] as $rincian) {
                RincianBiaya::create([
                    'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                    'jenis_komponen' => $rincian['jenis_komponen'],
                    'nominal' => $rincian['nominal'],
                ]);
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
                Kuitansi::create([
                    'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                    'tanggal_kuitansi' => $validated['tanggal_kuitansi'],
                ]);
            }

            foreach ($validated['pernyataan'] ?? [] as $pernyataan) {
                SuratPernyataan::create([
                    'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                    'jenis_kondisi' => $pernyataan['jenis_kondisi'],
                    'keterangan' => $pernyataan['keterangan'] ?? null,
                ]);
            }

            if (!empty($validated['kesimpulan_hasil_kegiatan'])) {
                LaporanPerjadin::create([
                    'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                    'kesimpulan_hasil_kegiatan' => $validated['kesimpulan_hasil_kegiatan'],
                    'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
                ]);
            }

            foreach ($request->file('dokumentasi', []) as $file) {
                $path = Storage::disk('public')->putFile('dokumentasi', $file);
                Dokumentasi::create([
                    'id_perjalanan_dinas' => $perjalananDinas->id_perjalanan_dinas,
                    'nama_file' => $file->getClientOriginalName(),
                    'path_file' => $path,
                    'uploaded_at' => now(),
                ]);
            }
        });

        return redirect()->route('dashboard')
            ->with('success', 'Perjalanan dinas berhasil disimpan.');
    }
}
