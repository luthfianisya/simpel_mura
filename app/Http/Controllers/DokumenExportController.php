<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DokumenExportController extends Controller
{
    // Konversi HTML dokumen (Surat Tugas, SPD, dst — sudah lengkap dengan CSS &
    // @page dari sisi client, lihat exportAsPdfHd di dokumen-perjadin.js) jadi PDF
    // pakai dompdf (renderer PHP murni, TANPA Node/Chrome) — dipilih supaya aplikasi
    // ini bisa jalan di hosting mana saja (termasuk shared hosting gratisan), tidak
    // seperti Browsershot yang wajib bisa exec proses Chrome headless di server.
    // Ukuran kertas (A4/F4) & page-break antar dokumen sudah diatur lewat CSS
    // @page + page-break-before di dalam $validated['html'] itu sendiri — dompdf
    // membacanya otomatis, tidak perlu di-set manual di sini.
    public function pdf(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'html' => 'required|string',
            'filename' => 'required|string|max:255',
        ]);

        try {
            $pdf = Pdf::loadHTML($this->inlineLocalImages($validated['html']))
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96,
                    // dompdf defaultnya media type "screen" — tanpa ini, semua aturan
                    // @media print di dokumen-perjadin.css (yang me-reset min-height
                    // preview-layar 297mm/330mm supaya dokumennya tidak nge-generate
                    // halaman ke-2 kosong) TIDAK PERNAH kepakai sama sekali.
                    'defaultMediaType' => 'print',
                ])
                ->output();
        } catch (Throwable $e) {
            Log::error('Gagal generate PDF dokumen: ' . $e->getMessage());

            return response()->json(['message' => 'Gagal membuat PDF di server. Coba lagi atau hubungi admin.'], 500);
        }

        $filename = preg_replace('/[\\\\\/:*?"<>|]/', '-', $validated['filename']) . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Ganti <img src="{...}/storage/{path}"> (foto Dokumentasi) ATAU
    // <img src="{...}/assets/{path}"> (mis. logo BPS di Surat Tugas/SPD) jadi data:
    // URI base64 dengan baca filenya LANGSUNG dari disk — bukan fetch HTTP. Sengaja
    // dilakukan di SERVER (bukan browser user) supaya tidak bergantung sama
    // origin/APP_URL — kalau beda dari domain yang dipakai user buka aplikasinya,
    // fetch dari BROWSER bisa kena CORS. Baca langsung dari disk juga selaras dengan
    // dompdf yang di-set isRemoteEnabled:false (tidak fetch HTTP apa pun).
    private function inlineLocalImages(string $html): string
    {
        return preg_replace_callback(
            '/(<img\b[^>]*\ssrc=")[^"]*\/(storage|assets)\/([^"]+)(")/i',
            function (array $m) {
                [, $prefix, $jenis, $relatifPath, $suffix] = $m;
                $relatifPath = urldecode($relatifPath);

                if ($jenis === 'storage') {
                    if (str_contains($relatifPath, '..') || ! Storage::disk('public')->exists($relatifPath)) {
                        return $prefix . $suffix;
                    }
                    $mime = Storage::disk('public')->mimeType($relatifPath) ?: 'image/jpeg';
                    $data = base64_encode(Storage::disk('public')->get($relatifPath));
                } else {
                    // Path traversal guard: pastikan hasil resolve-nya tetap di dalam
                    // public/assets, bukan kabur ke file lain di server (mis. "../.env")
                    // — beda dari jalur "storage" di atas, ini baca file publik APAPUN
                    // via path filesystem langsung, jadi jauh lebih penting dijaga.
                    $base = realpath(public_path('assets'));
                    $fullPath = realpath(public_path('assets/' . $relatifPath));
                    if (! $base || ! $fullPath || ! str_starts_with($fullPath, $base . DIRECTORY_SEPARATOR) || ! is_file($fullPath)) {
                        return $prefix . $suffix;
                    }
                    $mime = mime_content_type($fullPath) ?: 'image/png';
                    $data = base64_encode(file_get_contents($fullPath));
                }

                return $prefix . 'data:' . $mime . ';base64,' . $data . $suffix;
            },
            $html
        );
    }
}
