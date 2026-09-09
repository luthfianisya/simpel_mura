<?php

namespace App\Http\Controllers;

use App\Models\RateAkomodasi;
use App\Models\RateTransport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RateController extends Controller
{
    private const LEVEL = ['A', 'B', 'C'];

    public function index()
    {
        $daftarTransport = RateTransport::orderBy('kecamatan_induk')->orderBy('nama_wilayah')->orderBy('moda_transportasi')->get();

        return view('rate.index', [
            'daftarAkomodasi' => RateAkomodasi::orderBy('nama_kecamatan')->get(),
            'daftarTransportA' => $daftarTransport->where('level', 'A')->values(),
            'daftarTransportB' => $daftarTransport->where('level', 'B')->groupBy('kecamatan_induk'),
            'daftarTransportC' => $daftarTransport->where('level', 'C')->groupBy('kecamatan_induk'),
            'daftarKecamatan' => RateAkomodasi::orderBy('nama_kecamatan')->pluck('nama_kecamatan'),
            'daftarDokumenPendukung' => config('dokumen_pendukung'),
        ]);
    }

    public function downloadDokumenPendukung(string $key): StreamedResponse
    {
        $dokumen = config("dokumen_pendukung.{$key}");
        abort_if(! $dokumen, 404);

        $path = 'dokumen-pendukung/' . $dokumen['file'];
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, $dokumen['file']);
    }

    public function storeAkomodasi(Request $request)
    {
        $validated = $this->validasiAkomodasi($request);

        RateAkomodasi::create($validated);

        return back()->with('success', 'Rate akomodasi berhasil ditambahkan.');
    }

    public function updateAkomodasi(Request $request, RateAkomodasi $akomodasi)
    {
        $validated = $this->validasiAkomodasi($request, $akomodasi->id_rate_akomodasi);

        $akomodasi->update($validated);

        return back()->with('success', 'Rate akomodasi berhasil diperbarui.');
    }

    public function destroyAkomodasi(RateAkomodasi $akomodasi)
    {
        $akomodasi->delete();

        return back()->with('success', 'Rate akomodasi berhasil dihapus.');
    }

    public function storeTransport(Request $request)
    {
        $validated = $this->validasiTransport($request);

        RateTransport::create($validated);

        return back()->with('success', 'Rate transport berhasil ditambahkan.');
    }

    public function updateTransport(Request $request, RateTransport $transport)
    {
        $validated = $this->validasiTransport($request);

        $transport->update($validated);

        return back()->with('success', 'Rate transport berhasil diperbarui.');
    }

    public function destroyTransport(RateTransport $transport)
    {
        $transport->delete();

        return back()->with('success', 'Rate transport berhasil dihapus.');
    }

    private function validasiAkomodasi(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nama_kecamatan' => [
                'required', 'string', 'max:255',
                Rule::unique('rate_akomodasi', 'nama_kecamatan')->ignore($ignoreId, 'id_rate_akomodasi'),
            ],
            'tarif_per_malam' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);
    }

    private function validasiTransport(Request $request): array
    {
        $validated = $request->validate([
            'level' => ['required', Rule::in(self::LEVEL)],
            'nama_wilayah' => 'required|string|max:255',
            'kecamatan_induk' => 'nullable|string|max:255',
            'moda_transportasi' => 'required|string|max:255',
            'nilai_pp' => 'required|numeric|min:0',
        ]);

        // Level A = Kabupaten -> Kecamatan, jadi memang tidak punya kecamatan induk.
        if ($validated['level'] === 'A') {
            $validated['kecamatan_induk'] = null;
        }

        return $validated;
    }
}
