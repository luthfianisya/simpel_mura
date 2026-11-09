<?php

namespace App\Http\Controllers;

use App\Models\Gup;
use App\Models\MonitoringSpj;
use App\Models\PerjalananDinas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MonitoringSpjController extends Controller
{
    /**
     * Baris tabel ada dua macam:
     * - SPJ perjalanan dinas yang sudah selesai (final). Kolom MAK, tanggal, uraian, nominal,
     *   dan nama penerima diambil dari data perjalanan dinas; sisanya dari monitoring_spj.
     * - Rincian Non-Perjadin: diinput langsung oleh PPK / PPSPM / Bendahara untuk SPJ di luar
     *   perjalanan dinas (baris monitoring_spj tanpa id_perjalanan_dinas).
     * Keduanya dinormalisasi jadi array yang sama ($daftarBaris) supaya view cukup satu loop.
     *
     * Tabelnya dikelompokkan per GUP (DataTables RowGroup): kelompok "Belum Masuk GUP" paling
     * atas, lalu GUP terbaru ke terlama. Tiap baris diberi kunci kelompok (kunci_gup) yang
     * urut secara teks, supaya urutan kelompok tetap meski tabel diurutkan kolom lain.
     */
    public function index()
    {
        $daftarPerjalananDinas = PerjalananDinas::with([
            'pelaksana', 'kuitansi', 'rincianBiaya', 'pengeluaranRiil', 'monitoringSpj',
        ])
            ->where('status_draft', 'selesai')
            ->latest()
            ->get();
        $daftarRincian = MonitoringSpj::rincianNonPerjadin()->latest()->get();

        $daftarGup = Gup::denganNomor()
            ->sortBy(fn (Gup $gup) => [-$gup->tahun, -$gup->nomor])
            ->values();

        $kelompokGup = ['0000' => [
            'id' => null, 'label' => 'Belum Masuk GUP', 'tanggal' => null, 'tanggal_teks' => null, 'jumlah' => 0, 'total' => 0,
        ]];
        $kunciPerIdGup = [];
        foreach ($daftarGup as $i => $gup) {
            $kunci = sprintf('%04d', $i + 1);
            $kunciPerIdGup[$gup->id_gup] = $kunci;
            $kelompokGup[$kunci] = [
                'id' => $gup->id_gup,
                'label' => $gup->label,
                'tanggal' => optional($gup->tanggal_gup)->format('Y-m-d'),
                'tanggal_teks' => $gup->tanggal_gup ? $gup->tanggal_gup->locale('id')->translatedFormat('d F Y') : null,
                'jumlah' => 0,
                'total' => 0,
            ];
        }

        $daftarBaris = $daftarPerjalananDinas->map(fn (PerjalananDinas $pd) => [
            'kunci' => (string) $pd->id_perjalanan_dinas,
            'rincian' => false,
            'no' => $pd->id_perjalanan_dinas,
            'spj' => $pd->monitoringSpj,
            'nomor_fp' => $pd->no_form_permintaan,
            'pembebanan' => $pd->pembebanan,
            'tanggal' => optional($pd->kuitansi)->tanggal_kuitansi,
            'uraian' => $pd->perihal,
            'nominal' => (float) $pd->total_biaya,
            'penerima' => $pd->pelaksana->nama ?? '-',
            'perjalanan_dinas' => $pd,
        ])->concat($daftarRincian->map(fn (MonitoringSpj $m) => [
            'kunci' => 'r' . $m->id_monitoring_spj,
            'rincian' => true,
            'no' => 0,
            'spj' => $m,
            'nomor_fp' => $m->nomor_fp,
            'pembebanan' => $m->pembebanan_rincian,
            'tanggal' => $m->tanggal_rincian,
            'uraian' => $m->uraian_rincian,
            'nominal' => (float) $m->nominal_rincian,
            'penerima' => $m->penerima_rincian ?: '-',
            'perjalanan_dinas' => null,
        ]))->map(function (array $baris) use ($kunciPerIdGup, &$kelompokGup) {
            $baris['kunci_gup'] = $kunciPerIdGup[optional($baris['spj'])->id_gup] ?? '0000';
            $kelompokGup[$baris['kunci_gup']]['jumlah']++;
            $kelompokGup[$baris['kunci_gup']]['total'] += $baris['nominal'];

            return $baris;
        })->values();

        return view('monitoring-spj.index', [
            'daftarBaris' => $daftarBaris,
            'kelompokGup' => $kelompokGup,
            // Pilihan "Masukkan ke GUP yang sudah ada": hanya GUP yang berisi SPJ di tabel ini.
            'pilihanGup' => collect($kelompokGup)->filter(fn ($k) => $k['id'] && $k['jumlah'] > 0),
            'posisiSpj' => MonitoringSpj::POSISI_SPJ,
            'caraBayar' => MonitoringSpj::CARA_BAYAR,
            'jenisPembayaran' => MonitoringSpj::JENIS_PEMBAYARAN,
            'bisaIsi' => request()->user()->bisaIsiMonitoringSpj(),
        ]);
    }

    public function update(Request $request, PerjalananDinas $perjalananDinas)
    {
        $this->cekAkses($request);
        $validated = $this->isianTervalidasi($request);

        DB::transaction(function () use ($perjalananDinas, $validated) {
            $perjalananDinas->monitoringSpj()->updateOrCreate([], $validated);
            Gup::doesntHave('monitoringSpj')->delete();
        });

        return redirect()->route('monitoring-spj.index')->with('success', 'Monitoring SPJ berhasil disimpan.');
    }

    /** Tambah Rincian Non-Perjadin, bisa langsung dimasukkan ke salah satu GUP. */
    public function rincianStore(Request $request)
    {
        $this->cekAkses($request);
        $idGup = $request->validate(['id_gup' => ['nullable', 'integer', 'exists:gup,id_gup']])['id_gup'] ?? null;
        $validated = $this->isianTervalidasi($request, true);

        // Langsung dimasukkan ke GUP → jenis pembayaran GUP & tanggalnya mengikuti GUP.
        if ($idGup) {
            $validated['id_gup'] = $idGup;
            $validated['jenis_pembayaran'] = 'GUP';
            $validated['tanggal_gup'] = Gup::find($idGup)->tanggal_gup;
        }

        DB::transaction(fn () => MonitoringSpj::create($validated));

        return redirect()->route('monitoring-spj.index')->with('success', 'Rincian non-perjalanan dinas berhasil ditambahkan.');
    }

    public function rincianUpdate(Request $request, MonitoringSpj $monitoringSpj)
    {
        $this->cekAkses($request);
        abort_unless($monitoringSpj->is_rincian, 404);
        $validated = $this->isianTervalidasi($request, true);

        DB::transaction(function () use ($monitoringSpj, $validated) {
            $monitoringSpj->update($validated);
            Gup::doesntHave('monitoringSpj')->delete();
        });

        return redirect()->route('monitoring-spj.index')->with('success', 'Rincian non-perjalanan dinas berhasil disimpan.');
    }

    public function rincianDestroy(Request $request, MonitoringSpj $monitoringSpj)
    {
        $this->cekAkses($request);
        abort_unless($monitoringSpj->is_rincian, 404);

        DB::transaction(function () use ($monitoringSpj) {
            $monitoringSpj->delete();
            Gup::doesntHave('monitoringSpj')->delete();
        });

        return redirect()->route('monitoring-spj.index')->with('success', 'Rincian non-perjalanan dinas berhasil dihapus.');
    }

    /**
     * Ubah banyak baris sekaligus dengan nilai yang sama. Field yang dikosongkan di form
     * (atau "Tidak diubah" untuk pilihan Ya/Tidak) dibiarkan apa adanya di tiap baris.
     * ids = perjalanan dinas, rincian_ids = Rincian Non-Perjadin (id_monitoring_spj).
     */
    public function bulkUpdate(Request $request)
    {
        $this->cekAkses($request);

        $validated = $request->validate(self::aturanBaris() + [
            'tanggal_diperiksa_ppk' => ['nullable', 'date'],
            'no_spp_spm' => ['nullable', 'string', 'max:255'],
            'tanggal_spp' => ['nullable', 'date'],
            'tanggal_spm' => ['nullable', 'date'],
            'no_sp2d' => ['nullable', 'string', 'max:255'],
            'tanggal_sp2d' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'posisi_spj' => ['nullable', Rule::in(MonitoringSpj::POSISI_SPJ)],
            'cara_bayar' => ['nullable', Rule::in(array_keys(MonitoringSpj::CARA_BAYAR)), 'prohibited_unless:sudah_dibayar,1'],
            'jenis_pembayaran' => ['nullable', Rule::in(MonitoringSpj::JENIS_PEMBAYARAN)],
            'sudah_dibayar' => ['nullable', 'boolean'],
            'sudah_spby' => ['nullable', 'boolean'],
            'sudah_kuitansi' => ['nullable', 'boolean'],
            'sudah_drpp' => ['nullable', 'boolean'],
        ], self::pesanBaris() + [
            'cara_bayar.prohibited_unless' => 'Tunai/Transfer hanya bisa diisi jika pembayaran sudah dilakukan.',
        ]);

        $perubahan = collect($validated)->except(['ids', 'rincian_ids'])->filter(fn ($nilai) => $nilai !== null && $nilai !== '')->all();
        if (empty($perubahan)) {
            return back()->withErrors(['bulk' => 'Belum ada field yang diisi untuk diubah.']);
        }

        $daftarPerjalananDinas = PerjalananDinas::with('monitoringSpj')->whereIn('id_perjalanan_dinas', $validated['ids'] ?? [])->get();
        $daftarRincian = MonitoringSpj::rincianNonPerjadin()->whereIn('id_monitoring_spj', $validated['rincian_ids'] ?? [])->get();

        // Status pembayaran diubah jadi "Belum Dibayar" → Tunai/Transfer ikut dikosongkan.
        if (array_key_exists('sudah_dibayar', $perubahan) && !$perubahan['sudah_dibayar']) {
            $perubahan['cara_bayar'] = null;
        }
        // Jenis pembayaran diganti ke selain GUP → baris-baris itu keluar dari kelompok GUP-nya.
        if (isset($perubahan['jenis_pembayaran']) && $perubahan['jenis_pembayaran'] !== 'GUP') {
            $perubahan['id_gup'] = null;
            $perubahan['tanggal_gup'] = null;
        }
        $perubahan['diubah_oleh'] = $request->user()->id;

        // SPJ yang (akan) berposisi "Dikembalikan" belum bisa dibayar — termasuk baris yang
        // posisinya sudah begitu dan tidak ikut diubah.
        $perubahanUntuk = function (?MonitoringSpj $spj) use ($perubahan) {
            $posisiAkhir = $perubahan['posisi_spj'] ?? optional($spj)->posisi_spj;
            if ($posisiAkhir === MonitoringSpj::POSISI_DIKEMBALIKAN) {
                $perubahan['sudah_dibayar'] = false;
                $perubahan['cara_bayar'] = null;
            }

            return $perubahan;
        };

        DB::transaction(function () use ($daftarPerjalananDinas, $daftarRincian, $perubahanUntuk) {
            foreach ($daftarPerjalananDinas as $pd) {
                $pd->monitoringSpj()->updateOrCreate([], $perubahanUntuk($pd->monitoringSpj));
            }
            foreach ($daftarRincian as $rincian) {
                $rincian->update($perubahanUntuk($rincian));
            }
            Gup::doesntHave('monitoringSpj')->delete();
        });

        return redirect()->route('monitoring-spj.index')
            ->with('success', ($daftarPerjalananDinas->count() + $daftarRincian->count()) . ' baris monitoring SPJ berhasil diperbarui.');
    }

    /**
     * Aturan pilihan baris untuk aksi massal (Edit Terpilih & Kelompokkan ke GUP): ids =
     * perjalanan dinas berstatus selesai, rincian_ids = Rincian Non-Perjadin. Minimal satu.
     */
    public static function aturanBaris(): array
    {
        return [
            'ids' => ['nullable', 'array', 'required_without:rincian_ids'],
            'ids.*' => ['integer', Rule::exists('perjalanan_dinas', 'id_perjalanan_dinas')->where('status_draft', 'selesai')],
            'rincian_ids' => ['nullable', 'array', 'required_without:ids'],
            'rincian_ids.*' => ['integer', Rule::exists('monitoring_spj', 'id_monitoring_spj')->whereNull('id_perjalanan_dinas')],
        ];
    }

    public static function pesanBaris(): array
    {
        return [
            'ids.required_without' => 'Pilih minimal satu baris terlebih dahulu.',
            'rincian_ids.required_without' => 'Pilih minimal satu baris terlebih dahulu.',
        ];
    }

    /**
     * Validasi isian modal Edit (satu baris) + aturan turunannya. $rincian = true untuk
     * Rincian Non-Perjadin, yang juga mengisi data tanggal/uraian/penerima/pembebanan/nominal.
     */
    private function isianTervalidasi(Request $request, bool $rincian = false): array
    {
        $aturan = [
            'tanggal_diperiksa_ppk' => ['nullable', 'date'],
            'no_spp_spm' => ['nullable', 'string', 'max:255'],
            'tanggal_spp' => ['nullable', 'date'],
            'tanggal_spm' => ['nullable', 'date'],
            'no_sp2d' => ['nullable', 'string', 'max:255'],
            'tanggal_sp2d' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'posisi_spj' => ['nullable', Rule::in(MonitoringSpj::POSISI_SPJ)],
            'cara_bayar' => ['nullable', Rule::in(array_keys(MonitoringSpj::CARA_BAYAR)), 'prohibited_unless:sudah_dibayar,1'],
            'jenis_pembayaran' => ['nullable', Rule::in(MonitoringSpj::JENIS_PEMBAYARAN)],
        ];
        if ($rincian) {
            $aturan += [
                'tanggal_rincian' => ['nullable', 'date'],
                'uraian_rincian' => ['required', 'string', 'max:2000'],
                'penerima_rincian' => ['nullable', 'string', 'max:255'],
                'pembebanan_rincian' => ['nullable', 'string', 'max:255'],
                'nomor_fp' => ['nullable', 'string', 'max:255'],
                'nominal_rincian' => ['required', 'numeric', 'min:0'],
            ];
        }

        $validated = $request->validate($aturan, [
            'cara_bayar.prohibited_unless' => 'Tunai/Transfer hanya bisa diisi jika pembayaran sudah dilakukan.',
        ], [
            'uraian_rincian' => 'Uraian',
            'nominal_rincian' => 'Nominal',
            'tanggal_rincian' => 'Tanggal',
        ]);

        // Checkbox yang tidak dicentang tidak ikut terkirim, jadi dibaca pakai boolean().
        foreach (['sudah_dibayar', 'sudah_spby', 'sudah_kuitansi', 'sudah_drpp'] as $kolom) {
            $validated[$kolom] = $request->boolean($kolom);
        }
        // SPJ yang dikembalikan ke pembuat belum bisa dibayar.
        if (($validated['posisi_spj'] ?? null) === MonitoringSpj::POSISI_DIKEMBALIKAN) {
            $validated['sudah_dibayar'] = false;
        }
        // Tunai/Transfer hanya bermakna kalau pembayaran sudah dilakukan.
        if (!$validated['sudah_dibayar']) {
            $validated['cara_bayar'] = null;
        }
        // Jenis pembayaran bukan GUP lagi → keluar dari kelompok GUP-nya.
        if (($validated['jenis_pembayaran'] ?? null) !== 'GUP') {
            $validated['id_gup'] = null;
            $validated['tanggal_gup'] = null;
        }
        $validated['diubah_oleh'] = $request->user()->id;

        return $validated;
    }

    private function cekAkses(Request $request): void
    {
        abort_unless($request->user()->bisaIsiMonitoringSpj(), 403, 'Monitoring SPJ hanya bisa diisi oleh PPK / PPSPM / Bendahara.');
    }
}
