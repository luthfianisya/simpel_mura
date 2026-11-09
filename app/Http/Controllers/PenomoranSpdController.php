<?php

namespace App\Http\Controllers;

use App\Models\PenomoranSpd;
use App\Models\PerjalananDinas;
use Illuminate\Http\Request;

/**
 * Pengaturan nomor awal SPD per tahun (khusus administrator).
 *
 * Di layar admin mengisi "SPD dimulai dari nomor X"; yang disimpan nomor_awal = X - 1,
 * karena PenomoranSpd selalu memberi nomor terkecil yang belum terpakai SETELAH nomor_awal.
 */
class PenomoranSpdController extends Controller
{
    public function index()
    {
        $tahunIni = (int) now()->year;
        $daftar = PenomoranSpd::orderByDesc('tahun')->get()->keyBy('tahun');

        // Tahun berjalan selalu tampil walau belum diatur (artinya mulai dari nomor 1).
        if (! $daftar->has($tahunIni)) {
            $daftar->put($tahunIni, new PenomoranSpd(['tahun' => $tahunIni, 'nomor_awal' => 0]));
        }

        $baris = $daftar->sortKeysDesc()->map(function (PenomoranSpd $penomoran) {
            $tahun = (int) $penomoran->tahun;
            $urutTerpakai = PerjalananDinas::where('no_spd', 'like', "%/62130/KU.340/{$tahun}")
                ->pluck('no_spd')
                ->map(fn ($noSpd) => PenomoranSpd::nomorUrut($noSpd, $tahun))
                ->filter();

            return [
                'model' => $penomoran,
                'tahun' => $tahun,
                'mulai' => (int) $penomoran->nomor_awal + 1,
                'jumlah_terpakai' => $urutTerpakai->count(),
                'terakhir' => $urutTerpakai->max(),
                'berikutnya' => PenomoranSpd::berikutnya($tahun),
            ];
        })->values();

        return view('penomoran-spd.index', [
            'daftarPenomoran' => $baris,
            'contohFormat' => PenomoranSpd::format(1, $tahunIni),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100|unique:penomoran_spd,tahun',
            'nomor_mulai' => 'required|integer|min:1|max:999999',
        ], $this->pesan(), $this->atribut());

        PenomoranSpd::create([
            'tahun' => $validated['tahun'],
            'nomor_awal' => $validated['nomor_mulai'] - 1,
        ]);

        return back()->with('success', "Penomoran SPD tahun {$validated['tahun']} diatur mulai dari nomor {$validated['nomor_mulai']}.");
    }

    public function update(Request $request, PenomoranSpd $penomoranSpd)
    {
        $validated = $request->validate([
            'nomor_mulai' => 'required|integer|min:1|max:999999',
        ], $this->pesan(), $this->atribut());

        $penomoranSpd->update(['nomor_awal' => $validated['nomor_mulai'] - 1]);

        return back()->with('success', "Penomoran SPD tahun {$penomoranSpd->tahun} sekarang mulai dari nomor {$validated['nomor_mulai']}. "
            . 'Nomor berikutnya: ' . PenomoranSpd::berikutnya((int) $penomoranSpd->tahun) . '.');
    }

    private function pesan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'tahun.unique' => 'Penomoran untuk tahun ini sudah ada, silakan ubah lewat tombol Edit.',
        ];
    }

    private function atribut(): array
    {
        return ['tahun' => 'Tahun', 'nomor_mulai' => 'Nomor SPD pertama'];
    }
}
