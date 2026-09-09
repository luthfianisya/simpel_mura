<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Throwable;

class DokumenExportController extends Controller
{
    // Konversi HTML dokumen (Surat Tugas, SPD, dst — sudah lengkap dengan CSS &
    // @page dari sisi client, lihat exportAsPdfHd di dokumen-perjadin.js) jadi PDF
    // sungguhan lewat Chrome headless di server (Browsershot) — BUKAN screenshot,
    // jadi teksnya tetap vector/HD persis seperti hasil "Save as PDF" di browser,
    // tapi langsung ke-download tanpa dialog print.
    public function pdf(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'html' => 'required|string',
            'filename' => 'required|string|max:255',
        ]);

        try {
            $pdf = Browsershot::html($this->inlineLocalImages($validated['html']))
                ->setOption('preferCSSPageSize', true)
                ->showBackground()
                ->waitUntilNetworkIdle()
                // Timeout digenerosin — dokumen dengan beberapa foto Dokumentasi butuh
                // waktu lebih buat Chrome decode & layout-nya.
                ->timeout(120)
                ->pdf();
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
    // URI base64 dengan baca filenya LANGSUNG dari disk — bukan fetch HTTP. Ini
    // sengaja dilakukan di SERVER (bukan browser user) karena dua alasan: (1) supaya
    // tidak bergantung sama origin/APP_URL — kalau beda dari domain yang dipakai user
    // buka aplikasinya, fetch dari BROWSER bisa kena CORS; (2) fetch balik ke server
    // Laravel yang SAMA dari dalam request yang lagi diproses server itu sendiri bisa
    // DEADLOCK kalau server dev-nya single-threaded (mis. `php artisan serve`) — request
    // PDF ini nunggu Chrome, Chrome nunggu server yang lagi sibuk nungguin dia sendiri.
    // Baca langsung dari disk sama sekali tidak butuh HTTP, jadi tidak kena dua-duanya.
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
