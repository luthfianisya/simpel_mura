<?php

namespace App\Http\Controllers;

use App\Models\WilayahDesa;
use App\Models\WilayahKabupatenKota;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WilayahController extends Controller
{
    public function index()
    {
        $daftarProvinsi = array_keys(config('wilayah.kabupaten_kota', []));

        return view('wilayah.index', [
            'daftarKabupatenKota' => WilayahKabupatenKota::orderBy('nama')->get()
                ->groupBy(fn ($item) => $item->provinsi ?? 'Lainnya')
                ->sortBy(function ($items, $provinsi) use ($daftarProvinsi) {
                    // Kalimantan Tengah (provinsi BPS Kab. Murung Raya) paling atas,
                    // sisanya alfabetis — sama seperti urutan dropdown di form perjalanan dinas.
                    if ($provinsi === 'Kalimantan Tengah') return '0';
                    return $provinsi;
                }),
            'daftarProvinsi' => $daftarProvinsi,
            'daftarDesa' => WilayahDesa::orderBy('kecamatan')->orderByDesc('jumlah_dipilih')->orderBy('nama')->get()->groupBy('kecamatan'),
        ]);
    }

    public function storeKabupatenKota(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:wilayah_kabupaten_kota,nama',
            'provinsi' => 'nullable|string|max:255',
        ]);

        WilayahKabupatenKota::create($validated);

        return back()->with('success', 'Kabupaten/Kota berhasil ditambahkan.');
    }

    public function updateKabupatenKota(Request $request, WilayahKabupatenKota $kabupatenKota)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('wilayah_kabupaten_kota', 'nama')->ignore($kabupatenKota->id)],
            'provinsi' => 'nullable|string|max:255',
        ]);

        $kabupatenKota->update($validated);

        return back()->with('success', 'Kabupaten/Kota berhasil diperbarui.');
    }

    public function destroyKabupatenKota(WilayahKabupatenKota $kabupatenKota)
    {
        $kabupatenKota->delete();

        return back()->with('success', 'Kabupaten/Kota berhasil dihapus.');
    }

    public function storeDesa(Request $request)
    {
        $validated = $request->validate([
            'kecamatan' => 'required|string|max:255',
            'jenis' => ['required', Rule::in(['desa', 'kelurahan'])],
            'nama' => 'required|string|max:255',
        ]);

        $duplikat = WilayahDesa::where('kecamatan', $validated['kecamatan'])
            ->where('jenis', $validated['jenis'])
            ->where('nama', $validated['nama'])
            ->exists();

        if ($duplikat) {
            return back()->with('error', 'Desa/kelurahan tersebut sudah ada di kecamatan ini.');
        }

        WilayahDesa::create($validated);

        return back()->with('success', 'Desa/Kelurahan berhasil ditambahkan.');
    }

    public function updateDesa(Request $request, WilayahDesa $desa)
    {
        $validated = $request->validate([
            'kecamatan' => 'required|string|max:255',
            'jenis' => ['required', Rule::in(['desa', 'kelurahan'])],
            'nama' => 'required|string|max:255',
        ]);

        $duplikat = WilayahDesa::where('kecamatan', $validated['kecamatan'])
            ->where('jenis', $validated['jenis'])
            ->where('nama', $validated['nama'])
            ->where('id', '!=', $desa->id)
            ->exists();

        if ($duplikat) {
            return back()->with('error', 'Desa/kelurahan tersebut sudah ada di kecamatan ini.');
        }

        $desa->update($validated);

        return back()->with('success', 'Desa/Kelurahan berhasil diperbarui.');
    }

    public function destroyDesa(WilayahDesa $desa)
    {
        $desa->delete();

        return back()->with('success', 'Desa/Kelurahan berhasil dihapus.');
    }
}
