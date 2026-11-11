<?php

namespace App\Http\Controllers;

use App\Models\Gup;
use App\Models\MonitoringSpj;
use App\Models\PerjalananDinas;
use Illuminate\Http\Request;

class MonitoringGupController extends Controller
{
    /**
     * Rekap GUP per tahun, sama dengan sheet "Monitoring" di spreadsheet SPJ. Satu GUP = satu
     * kelompok yang dibuat PPK/PPSPM/Bendahara di Monitoring SPJ ("Kelompokkan ke GUP") —
     * di sheet "SPJ 2026" kelompok ini dipisah garis hijau. Nominal GUP = jumlah nominal SPJ-nya.
     */
    public function index(Request $request)
    {
        // SPJ perjalanan dinas + Rincian Non-Perjadin berjenis pembayaran GUP, dinormalisasi
        // jadi array yang sama (lihat juga MonitoringSpjController::index).
        $spjGup = PerjalananDinas::with(['pelaksana', 'kuitansi', 'rincianBiaya', 'pengeluaranRiil', 'monitoringSpj'])
            ->where('status_draft', 'selesai')
            ->whereHas('monitoringSpj', fn ($q) => $q->where('jenis_pembayaran', 'GUP'))
            ->get()
            ->map(fn (PerjalananDinas $pd) => [
                'tanggal' => optional($pd->kuitansi)->tanggal_kuitansi,
                'uraian' => $pd->perihal,
                'penerima' => $pd->pelaksana->nama ?? '-',
                'pembebanan' => $pd->pembebanan,
                'nomor_fp' => $pd->no_form_permintaan,
                'nominal' => (float) $pd->total_biaya,
                'spj' => $pd->monitoringSpj,
            ])
            ->concat(MonitoringSpj::rincianNonPerjadin()->where('jenis_pembayaran', 'GUP')->get()
                ->map(fn (MonitoringSpj $m) => [
                    'tanggal' => $m->tanggal_rincian,
                    'uraian' => $m->uraian_rincian,
                    'penerima' => $m->penerima_rincian ?: '-',
                    'pembebanan' => $m->pembebanan_rincian,
                    'nomor_fp' => $m->nomor_fp,
                    'nominal' => (float) $m->nominal_rincian,
                    'spj' => $m,
                ]));

        $semuaGup = Gup::denganNomor();
        $daftarTahun = $semuaGup->pluck('tahun')->unique()->sortDesc()->values();
        $tahun = (int) $request->query('tahun', $daftarTahun->first() ?? now()->year);

        // SPJ berjenis pembayaran GUP yang belum dikelompokkan ke GUP mana pun.
        $menunggu = $spjGup->filter(fn ($baris) => ! $baris['spj']->id_gup)->values();

        $spjPerGup = $spjGup->groupBy(fn ($baris) => $baris['spj']->id_gup);
        $daftarGup = $semuaGup
            ->filter(fn (Gup $gup) => $gup->tahun === $tahun && $spjPerGup->has($gup->id_gup))
            ->sortBy(fn (Gup $gup) => [$gup->bulan, $gup->nomor])
            ->values()
            ->map(function (Gup $gup) use ($spjPerGup) {
                $spj = $spjPerGup->get($gup->id_gup);

                return [
                    'id' => $gup->id_gup,
                    'nomor' => $gup->nomor,
                    'label' => $gup->label,
                    'tanggal' => $gup->tanggal_gup,
                    'tanggal_transfer' => $gup->tanggal_transfer,
                    'pajak' => $gup->pajak,
                    'spj' => $spj->sortBy(fn ($baris) => optional($baris['tanggal'])->format('Y-m-d'))->values(),
                    'jumlah' => $spj->count(),
                    'nominal' => $spj->sum('nominal'),
                    'sudah_dibayar' => $spj->filter(fn ($baris) => $baris['spj']->sudah_dibayar)->count(),
                ];
            });

        return view('monitoring-gup.index', [
            'daftarGup' => $daftarGup,
            'menunggu' => $menunggu,
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun->contains($tahun) ? $daftarTahun : $daftarTahun->push($tahun)->sortDesc()->values(),
        ]);
    }
}
