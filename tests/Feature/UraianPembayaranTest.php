<?php

use App\Models\Kuitansi;
use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\User;
use App\Services\DokumenWord;

function simpanDenganUraianPembayaran($test, ?string $uraian)
{
    $pegawai = PegawaiMitra::create(['nama' => 'Pelaksana Uji', 'status_kepegawaian' => 'pegawai']);
    $user = User::factory()->create(['id_pegawai_mitra' => $pegawai->id_pegawai_mitra]);

    $test->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'perihal' => 'Pengecekan Perubahan Batas SLS Sensus Ekonomi 2026',
        'desa_tujuan' => 'Kelurahan Beriwit',
        'tanggal_mulai' => '2026-11-11',
        'tanggal_selesai' => '2026-11-13',
        'tanggal_kuitansi' => '2026-11-23',
        'uraian_pembayaran' => $uraian,
    ])->assertSessionHasNoErrors();

    return PerjalananDinas::latest('id_perjalanan_dinas')->first();
}

function xmlKuitansi(PerjalananDinas $pd): string
{
    $zip = new ZipArchive();
    $zip->open((new DokumenWord())->buat($pd->fresh(), 'kuitansi'));
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    return $xml;
}

test('uraian pembayaran sunting tersimpan dan dipakai di kuitansi word', function () {
    $pd = simpanDenganUraianPembayaran($this, 'Biaya perjalanan dinas pengecekan batas SLS, tanggal 11 – 13 November 2026.');

    expect(Kuitansi::first()->uraian_pembayaran)->toBe('Biaya perjalanan dinas pengecekan batas SLS, tanggal 11 – 13 November 2026.')
        ->and($pd->fresh()->toPreviewData()['uraian_pembayaran'])->not->toBeNull();

    // Titik di akhir dibuang karena disambung ", sesuai dengan:"; Perihal asli tidak dipakai.
    expect(xmlKuitansi($pd))->toContain('pengecekan batas SLS, tanggal 11 – 13 November 2026, sesuai dengan:')
        ->not->toContain('Pengecekan Perubahan Batas SLS Sensus Ekonomi');
});

test('uraian pembayaran kosong memakai narasi otomatis', function () {
    $pd = simpanDenganUraianPembayaran($this, null);

    expect(Kuitansi::first()->uraian_pembayaran)->toBeNull()
        ->and(xmlKuitansi($pd))->toContain('Biaya perjalanan dinas dalam rangka Pengecekan Perubahan Batas SLS Sensus Ekonomi 2026 di Kelurahan Beriwit');
});
