<?php

namespace App\Http\Controllers;

use App\Models\PerjalananDinas;
use App\Services\DokumenWord;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Unduh dokumen perjalanan dinas dalam format Word (.docx) dari jendela Export.
 * ?dokumen=kuitansi            -> 1 dokumen
 * ?dokumen=kuitansi,rincian,...-> beberapa dokumen digabung jadi 1 file (urutan dipertahankan)
 * Dokumen dibuat dari data yang TERSIMPAN di database.
 */
class DokumenWordController extends Controller
{
    public function unduh(Request $request, PerjalananDinas $perjalananDinas, DokumenWord $dokumenWord): BinaryFileResponse
    {
        $tersedia = DokumenWord::jenisTersedia($perjalananDinas);
        $dokumen = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $request->query('dokumen'))))));
        abort_if($dokumen === [], 422, 'Pilih minimal satu dokumen.');
        foreach ($dokumen as $jenis) {
            abort_unless(isset($tersedia[$jenis]), 404);
        }

        // Digabung (lebih dari 1 dokumen): Surat Tugas & SPD tidak ikut (lihat DokumenWord::buat).
        if (count($dokumen) > 1) {
            $dokumen = array_values(array_diff($dokumen, DokumenWord::TIDAK_DIGABUNG));
            abort_if($dokumen === [], 422, 'Pilih minimal satu dokumen selain Surat Tugas dan SPD untuk digabung.');
        }

        $path = $dokumenWord->buat($perjalananDinas, $dokumen);
        $label = count($dokumen) === 1 ? $tersedia[$dokumen[0]] : 'Dokumen Lengkap';
        $nama = preg_replace('/[\\\\\/:*?"<>|]/', '-', $label . ' - ' . ($perjalananDinas->pelaksana->nama ?? 'Perjalanan Dinas')) . '.docx';

        return response()->download($path, $nama, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend();
    }
}
