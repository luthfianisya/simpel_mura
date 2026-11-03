<?php

namespace App\Http\Controllers;

use App\Models\MonitoringSpj;
use App\Models\PerjalananDinas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MonitoringSpjController extends Controller
{
    /**
     * Satu baris = satu perjalanan dinas yang sudah selesai (final). Kolom MAK, tanggal,
     * uraian, nominal, dan nama penerima diambil dari data perjalanan dinas; sisanya dari
     * tabel monitoring_spj yang diisi PPK / PPSPM / Bendahara.
     */
    public function index()
    {
        $daftarPerjalananDinas = PerjalananDinas::with([
            'pelaksana', 'kuitansi', 'rincianBiaya', 'pengeluaranRiil', 'monitoringSpj',
        ])
            ->where('status_draft', 'selesai')
            ->latest()
            ->get();

        return view('monitoring-spj.index', [
            'daftarPerjalananDinas' => $daftarPerjalananDinas,
            'posisiSpj' => MonitoringSpj::POSISI_SPJ,
            'caraBayar' => MonitoringSpj::CARA_BAYAR,
            'jenisPembayaran' => MonitoringSpj::JENIS_PEMBAYARAN,
            'bisaIsi' => request()->user()->bisaIsiMonitoringSpj(),
        ]);
    }

    public function update(Request $request, PerjalananDinas $perjalananDinas)
    {
        abort_unless($request->user()->bisaIsiMonitoringSpj(), 403, 'Monitoring SPJ hanya bisa diisi oleh PPK / PPSPM / Bendahara.');

        $validated = $request->validate([
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
            'tanggal_gup' => ['nullable', 'date'],
        ], [
            'cara_bayar.prohibited_unless' => 'Tunai/Transfer hanya bisa diisi jika pembayaran sudah dilakukan.',
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
        $validated['diubah_oleh'] = $request->user()->id;

        $perjalananDinas->monitoringSpj()->updateOrCreate([], $validated);

        return redirect()->route('monitoring-spj.index')->with('success', 'Monitoring SPJ berhasil disimpan.');
    }

    /**
     * Ubah banyak baris sekaligus dengan nilai yang sama. Field yang dikosongkan di form
     * (atau "Tidak diubah" untuk pilihan Ya/Tidak) dibiarkan apa adanya di tiap baris.
     */
    public function bulkUpdate(Request $request)
    {
        abort_unless($request->user()->bisaIsiMonitoringSpj(), 403, 'Monitoring SPJ hanya bisa diisi oleh PPK / PPSPM / Bendahara.');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:perjalanan_dinas,id_perjalanan_dinas'],
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
            'tanggal_gup' => ['nullable', 'date'],
            'sudah_dibayar' => ['nullable', 'boolean'],
            'sudah_spby' => ['nullable', 'boolean'],
            'sudah_kuitansi' => ['nullable', 'boolean'],
            'sudah_drpp' => ['nullable', 'boolean'],
        ], [
            'ids.required' => 'Pilih minimal satu baris terlebih dahulu.',
            'cara_bayar.prohibited_unless' => 'Tunai/Transfer hanya bisa diisi jika pembayaran sudah dilakukan.',
        ]);

        $perubahan = collect($validated)->except('ids')->filter(fn ($nilai) => $nilai !== null && $nilai !== '')->all();
        if (empty($perubahan)) {
            return back()->withErrors(['bulk' => 'Belum ada field yang diisi untuk diubah.']);
        }

        $daftarPerjalananDinas = PerjalananDinas::with('monitoringSpj')->whereIn('id_perjalanan_dinas', $validated['ids'])->get();

        // Status pembayaran diubah jadi "Belum Dibayar" → Tunai/Transfer ikut dikosongkan.
        if (array_key_exists('sudah_dibayar', $perubahan) && !$perubahan['sudah_dibayar']) {
            $perubahan['cara_bayar'] = null;
        }
        $perubahan['diubah_oleh'] = $request->user()->id;
        DB::transaction(function () use ($daftarPerjalananDinas, $perubahan) {
            foreach ($daftarPerjalananDinas as $pd) {
                $perubahanBaris = $perubahan;
                // SPJ yang (akan) berposisi "Dikembalikan" belum bisa dibayar — termasuk baris
                // yang posisinya sudah begitu dan tidak ikut diubah.
                $posisiAkhir = $perubahanBaris['posisi_spj'] ?? optional($pd->monitoringSpj)->posisi_spj;
                if ($posisiAkhir === MonitoringSpj::POSISI_DIKEMBALIKAN) {
                    $perubahanBaris['sudah_dibayar'] = false;
                    $perubahanBaris['cara_bayar'] = null;
                }
                $pd->monitoringSpj()->updateOrCreate([], $perubahanBaris);
            }
        });

        return redirect()->route('monitoring-spj.index')
            ->with('success', $daftarPerjalananDinas->count() . ' baris monitoring SPJ berhasil diperbarui.');
    }
}
