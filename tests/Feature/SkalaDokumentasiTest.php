<?php

use App\Models\Dokumentasi;
use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\User;
use App\Services\DokumenWord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function simpanFotoDenganSkala($test, array $skala): PerjalananDinas
{
    $pegawai = PegawaiMitra::create(['nama' => 'Pelaksana Uji', 'status_kepegawaian' => 'pegawai']);
    $user = User::factory()->create(['id_pegawai_mitra' => $pegawai->id_pegawai_mitra]);
    $test->actingAs($user);

    $test->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.jpg', 600, 800)],
        'dokumentasi_caption' => ['A', 'B'],
        'dokumentasi_skala' => $skala,
    ])->assertSessionHasNoErrors();

    return PerjalananDinas::latest('id_perjalanan_dinas')->first();
}

test('ukuran foto dokumentasi tersimpan saat unggah dan ikut data dokumen beserta ukuran asli', function () {
    Storage::fake('public');
    $pd = simpanFotoDenganSkala($this, [60, null]);

    expect($pd->dokumentasi->pluck('skala')->all())->toBe([60, 100]);

    $data = $pd->fresh()->toPreviewData()['dokumentasi'];
    expect($data[0])->toMatchArray(['skala' => 60, 'lebar' => 800, 'tinggi' => 600])
        ->and($data[1])->toMatchArray(['skala' => 100, 'lebar' => 600, 'tinggi' => 800]);
});

test('ukuran foto tersimpan bisa diubah tanpa mengubah caption', function () {
    Storage::fake('public');
    $pd = simpanFotoDenganSkala($this, [100, 100]);
    $foto = $pd->dokumentasi->first();

    $this->patchJson(route('perjalanan-dinas.dokumentasi.caption', [$pd, $foto]), ['skala' => 50])->assertNoContent();
    expect($foto->fresh())->skala->toBe(50)->caption->toBe('A');

    // Caption saja juga tidak mengubah ukuran.
    $this->patchJson(route('perjalanan-dinas.dokumentasi.caption', [$pd, $foto]), ['caption' => 'Baru'])->assertNoContent();
    expect($foto->fresh())->skala->toBe(50)->caption->toBe('Baru');

    // Di luar batas 30–100 ditolak.
    $this->patchJson(route('perjalanan-dinas.dokumentasi.caption', [$pd, $foto]), ['skala' => 10])->assertUnprocessable();
});

test('ukuran foto mengecilkan gambar di word dengan rasio tetap', function () {
    Storage::fake('public');
    $pd = simpanFotoDenganSkala($this, [100, 100]);
    $ukuranGambar = function (PerjalananDinas $pd) {
        $zip = new ZipArchive();
        $zip->open((new DokumenWord())->buat($pd->fresh(), 'laporan'));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        preg_match('/width:([\d.]+)pt;\s*height:([\d.]+)pt/', $xml, $m);

        return [(float) $m[1], (float) $m[2]];
    };

    [$w1, $h1] = $ukuranGambar($pd);
    Dokumentasi::where('id_dokumentasi', $pd->dokumentasi->first()->id_dokumentasi)->update(['skala' => 50]);
    [$w2, $h2] = $ukuranGambar($pd);

    expect(round($w2 / $w1, 2))->toBe(0.5)
        ->and(round($w2 / $h2, 2))->toBe(round($w1 / $h1, 2));
});
