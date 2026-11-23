<?php

namespace App\Http\Controllers;

use App\Models\PerjalananDinas;
use App\Services\DokumenWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Halaman uji coba khusus administrator ("(dev) Semua Perjalanan Dinas"): tempat mencoba
 * fitur yang belum dirilis ke pengguna biasa — saat ini export dokumen ke Word (.docx).
 */
class DevPerjalananDinasController extends Controller
{
    public function index()
    {
        $daftarPerjalananDinas = PerjalananDinas::with(['pelaksana'])->latest()->get();

        return view('dev.perjalanan-dinas', compact('daftarPerjalananDinas'));
    }

    public function word(PerjalananDinas $perjalananDinas, string $jenis, DokumenWord $dokumenWord): BinaryFileResponse
    {
        $tersedia = DokumenWord::jenisTersedia($perjalananDinas);
        abort_unless($jenis === 'semua' || isset($tersedia[$jenis]), 404);

        $path = $dokumenWord->buat($perjalananDinas, $jenis);
        $label = $jenis === 'semua' ? 'Semua Dokumen' : $tersedia[$jenis];
        $nama = preg_replace('/[\\\\\/:*?"<>|]/', '-', $label . ' - ' . ($perjalananDinas->pelaksana->nama ?? 'Perjalanan Dinas')) . '.docx';

        return response()->download($path, $nama, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend();
    }
}
