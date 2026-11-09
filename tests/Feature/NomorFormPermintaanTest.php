<?php

use App\Models\Gup;
use App\Models\MonitoringSpj;
use App\Models\PerjalananDinas;
use App\Models\RincianBiaya;

test('nomor form permintaan tersimpan dari data umum perjalanan dinas', function () {
    $user = buatUserDenganPegawai();

    $this->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'no_form_permintaan' => 'FP-031/2026',
    ])->assertRedirect(route('dashboard'));

    $pd = PerjalananDinas::latest('id_perjalanan_dinas')->first();
    expect($pd->no_form_permintaan)->toBe('FP-031/2026');

    // Tampil kembali di form ubah.
    $this->actingAs($user)->get(route('perjalanan-dinas.edit', $pd))
        ->assertOk()
        ->assertSee('value="FP-031/2026"', false);
});

test('nomor form permintaan tidak ikut tersalin saat copy perjalanan dinas', function () {
    $user = buatUserDenganPegawai();
    $sumber = PerjalananDinas::create([
        'id_pemohon' => $user->id_pegawai_mitra,
        'jenis_perjadin' => 'biasa',
        'perihal' => 'Perjadin sumber',
        'no_form_permintaan' => 'FP-099/2026',
        'status_draft' => 'draft_sesi1',
    ]);

    $this->actingAs($user)->get(route('perjalanan-dinas.create', ['jenis' => 'biasa', 'from' => $sumber->id_perjalanan_dinas]))
        ->assertOk()
        ->assertSee('>Perjadin sumber</textarea>', false)
        ->assertDontSee('value="FP-099/2026"', false);
});

test('nomor form permintaan tampil di rekap, monitoring spj, dan rincian monitoring gup', function () {
    $user = buatUserDenganPegawai();
    $pd = PerjalananDinas::create([
        'id_pemohon' => $user->id_pegawai_mitra,
        'id_pegawai_mitra_pelaksana' => $user->id_pegawai_mitra,
        'jenis_perjadin' => 'biasa',
        'perihal' => 'Pencacahan Susenas',
        'no_form_permintaan' => 'FP-007/2026',
        'status_draft' => 'selesai',
    ]);
    RincianBiaya::create(['id_perjalanan_dinas' => $pd->id_perjalanan_dinas, 'jenis_komponen' => 'uang_harian', 'nominal' => 100000]);
    $gup = Gup::create(['tanggal_gup' => '2026-03-11']);
    MonitoringSpj::create(['id_perjalanan_dinas' => $pd->id_perjalanan_dinas, 'id_gup' => $gup->id_gup, 'jenis_pembayaran' => 'GUP', 'tanggal_gup' => '2026-03-11']);

    $this->actingAs($user)->get(route('perjalanan-dinas.index'))->assertOk()->assertSee('FP-007/2026');
    $this->actingAs($user)->get(route('monitoring-spj.index'))->assertOk()->assertSee('FP-007/2026');
    $this->actingAs($user)->get(route('monitoring-gup.index', ['tahun' => 2026]))->assertOk()->assertSee('FP-007/2026');
});
