<?php

namespace App\Http\Controllers;

use App\Models\PerjalananDinas;
use Illuminate\Http\Request;

class MonitoringGupController extends Controller
{
    /**
     * Rekap GUP per tanggal, sama dengan sheet "Monitoring" di spreadsheet SPJ: satu GUP =
     * semua SPJ (perjalanan dinas selesai) dengan Jenis Pembayaran "GUP" dan Tanggal GUP yang
     * sama — di sheet "SPJ 2026" kelompok ini dipisah garis hijau. Nominal GUP = jumlah
     * nominal SPJ-nya (SUMIFS kolom nominal menurut jenis pembayaran & tanggal GUP).
     */
    public function index(Request $request)
    {
        $spjGup = PerjalananDinas::with(['pelaksana', 'kuitansi', 'rincianBiaya', 'pengeluaranRiil', 'monitoringSpj'])
            ->where('status_draft', 'selesai')
            ->whereHas('monitoringSpj', fn ($q) => $q->where('jenis_pembayaran', 'GUP'))
            ->get();

        $daftarTahun = $spjGup->map(fn ($pd) => optional($pd->monitoringSpj->tanggal_gup)->year)
            ->filter()->unique()->sortDesc()->values();
        $tahun = (int) $request->query('tahun', $daftarTahun->first() ?? now()->year);

        // SPJ GUP yang tanggal GUP-nya belum diisi PPK/PPSPM/Bendahara (belum masuk batch mana pun).
        $menunggu = $spjGup->filter(fn ($pd) => ! $pd->monitoringSpj->tanggal_gup)->values();

        $daftarGup = $spjGup
            ->filter(fn ($pd) => optional($pd->monitoringSpj->tanggal_gup)->year === $tahun)
            ->groupBy(fn ($pd) => $pd->monitoringSpj->tanggal_gup->format('Y-m-d'))
            ->sortKeys()
            ->values()
            ->map(fn ($spj, $i) => [
                'nomor' => $i + 1,
                'tanggal' => $spj->first()->monitoringSpj->tanggal_gup,
                'spj' => $spj->sortBy(fn ($pd) => optional(optional($pd->kuitansi)->tanggal_kuitansi)->format('Y-m-d'))->values(),
                'jumlah' => $spj->count(),
                'nominal' => $spj->sum(fn ($pd) => $pd->total_biaya),
                'sudah_dibayar' => $spj->filter(fn ($pd) => $pd->monitoringSpj->sudah_dibayar)->count(),
            ]);

        return view('monitoring-gup.index', [
            'daftarGup' => $daftarGup,
            'menunggu' => $menunggu,
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun->contains($tahun) ? $daftarTahun : $daftarTahun->push($tahun)->sortDesc()->values(),
        ]);
    }
}
