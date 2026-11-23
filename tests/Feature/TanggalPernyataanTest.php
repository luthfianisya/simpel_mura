<?php

use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\SuratPernyataan;
use App\Models\User;
use App\Services\DokumenWord;

function simpanPernyataanKeterlambatan($test, ?string $tanggal)
{
    $pegawai = PegawaiMitra::create(['nama' => 'Pelaksana Uji', 'status_kepegawaian' => 'pegawai']);
    $user = User::factory()->create(['id_pegawai_mitra' => $pegawai->id_pegawai_mitra]);

    $test->actingAs($user)->post(route('perjalanan-dinas.store'), array_filter([
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'tanggal_mulai' => '2026-11-11',
        'tanggal_selesai' => '2026-11-13',
        'pernyataan' => ['keterlambatan' => 1, 'tidak_menginap_hotel' => 1],
        'pernyataan_keterlambatan_keterangan' => 'kendala jaringan',
        'pernyataan_keterlambatan_tanggal' => $tanggal,
    ]))->assertSessionHasNoErrors();

    return PerjalananDinas::latest('id_perjalanan_dinas')->first();
}

test('tanggal surat pernyataan keterlambatan tersimpan dan dipakai di word', function () {
    $pd = simpanPernyataanKeterlambatan($this, '2026-12-02');

    expect(SuratPernyataan::where('jenis_kondisi', 'keterlambatan')->first()->tanggal->format('Y-m-d'))->toBe('2026-12-02')
        ->and(SuratPernyataan::where('jenis_kondisi', 'tidak_menginap_hotel')->first()->tanggal)->toBeNull();

    $zip = new ZipArchive();
    $zip->open((new DokumenWord())->buat($pd->fresh(), 'pernyataan'));
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    // Keterlambatan: tanggal pilihan; Tidak Menginap: hari terakhir perjalanan dinas.
    expect($xml)->toContain('Puruk Cahu, 2 Desember 2026')
        ->and($xml)->toContain('Puruk Cahu, 13 November 2026');
});

test('tanggal surat pernyataan keterlambatan kosong otomatis hari ini', function () {
    simpanPernyataanKeterlambatan($this, null);

    expect(SuratPernyataan::where('jenis_kondisi', 'keterlambatan')->first()->tanggal->format('Y-m-d'))->toBe(now()->toDateString());
});
