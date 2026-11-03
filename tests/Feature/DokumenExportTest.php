<?php

use App\Http\Controllers\DokumenExportController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function inlineGambar(string $html): string
{
    $method = new ReflectionMethod(DokumenExportController::class, 'inlineLocalImages');

    return $method->invoke(new DokumenExportController(), $html);
}

test('foto yang belum disimpan (data uri besar) tidak membuat export pdf gagal', function () {
    Storage::fake('public');
    Storage::disk('public')->put('dokumentasi/a.jpg', 'isi-foto');

    // ~2 juta karakter base64 berisi banyak "/", pemicu "Backtrack limit exhausted" di pola lama.
    $dataUri = 'data:image/jpeg;base64,' . substr(str_repeat(base64_encode(random_bytes(3000)), 500), 0, 2_000_000);
    $html = '<img src="' . $dataUri . '" alt="baru.jpg"><img class="x" src="http://simpel.test/storage/dokumentasi/a.jpg">';

    $hasil = inlineGambar($html);

    expect($hasil)->toContain($dataUri)
        ->and($hasil)->toContain('src="data:image/jpeg;base64,' . base64_encode('isi-foto') . '"')
        ->and($hasil)->not->toContain('http://simpel.test/storage');
});

test('gambar storage yang tidak ada dikosongkan dan path traversal assets ditolak', function () {
    Storage::fake('public');

    $hasil = inlineGambar('<img src="/storage/tidak-ada.jpg"><img src="/assets/../../.env">');

    expect($hasil)->toBe('<img src=""><img src="">');
});

test('export pdf berhasil dengan foto data uri besar', function () {
    $user = App\Models\User::factory()->create();
    $foto = base64_encode(UploadedFile::fake()->image('besar.jpg', 1600, 1200)->getContent());
    $html = '<!DOCTYPE html><html><body><p>Laporan</p><img src="data:image/jpeg;base64,' . $foto . '"></body></html>';

    $this->actingAs($user)->postJson(route('dokumen.export-pdf'), ['html' => $html, 'filename' => 'uji'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
