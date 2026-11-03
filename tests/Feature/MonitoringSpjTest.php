<?php

use App\Models\Kuitansi;
use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\RincianBiaya;
use App\Models\User;

function buatPegawai(string $nama, ?string $peranPejabat = null): PegawaiMitra
{
    return PegawaiMitra::create(['nama' => $nama, 'status_kepegawaian' => 'pegawai', 'peran_pejabat' => $peranPejabat]);
}

function buatPerjadinSelesai(PegawaiMitra $pelaksana, PegawaiMitra $ppk, PegawaiMitra $bendahara): PerjalananDinas
{
    $pd = PerjalananDinas::create([
        'id_pemohon' => $pelaksana->id_pegawai_mitra,
        'id_pegawai_mitra_pelaksana' => $pelaksana->id_pegawai_mitra,
        'perihal' => 'Pencacahan SKTNP Jasa di Beriwit',
        'pembebanan' => '2899.BMA.006.005.A.524113',
        'jenis_perjadin' => 'biasa',
        'id_ppk' => $ppk->id_pegawai_mitra,
        'id_bendahara' => $bendahara->id_pegawai_mitra,
        'status_draft' => 'selesai',
    ]);
    RincianBiaya::create(['id_perjalanan_dinas' => $pd->id_perjalanan_dinas, 'jenis_komponen' => 'uang_harian', 'nominal' => 80000]);
    Kuitansi::create(['id_perjalanan_dinas' => $pd->id_perjalanan_dinas, 'tanggal_kuitansi' => '2026-01-08']);

    return $pd;
}

test('halaman monitoring spj menampilkan data otomatis dari perjalanan dinas', function () {
    $pelaksana = buatPegawai('Safitri Mandasari');
    buatPerjadinSelesai($pelaksana, buatPegawai('PPK'), buatPegawai('Bendahara'));
    $user = User::factory()->create(['id_pegawai_mitra' => $pelaksana->id_pegawai_mitra]);

    $this->actingAs($user)->get(route('monitoring-spj.index'))
        ->assertOk()
        ->assertSee('Safitri Mandasari')
        ->assertSee('2899.BMA.006.005.A.524113')
        ->assertSee('08/01/2026')
        ->assertSee('80.000');
});

test('ppk bisa mengisi kolom manual monitoring spj', function () {
    $ppk = buatPegawai('PPK', 'ppk');
    $pd = buatPerjadinSelesai(buatPegawai('Pelaksana'), $ppk, buatPegawai('Bendahara'));
    $user = User::factory()->create(['id_pegawai_mitra' => $ppk->id_pegawai_mitra]);

    $this->actingAs($user)->put(route('monitoring-spj.update', $pd), [
        'no_spp_spm' => '22',
        'tanggal_sp2d' => '2026-01-22',
        'posisi_spj' => 'Bendahara',
        'cara_bayar' => 'transfer',
        'jenis_pembayaran' => 'GUP',
        'sudah_dibayar' => '1',
    ])->assertRedirect(route('monitoring-spj.index'));

    $spj = $pd->fresh()->monitoringSpj;
    expect($spj->no_spp_spm)->toBe('22')
        ->and($spj->posisi_spj)->toBe('Bendahara')
        ->and($spj->sudah_dibayar)->toBeTrue()
        ->and($spj->sudah_drpp)->toBeFalse();
});

test('pegawai yang bukan ppk atau bendahara tidak bisa mengisi monitoring spj', function () {
    $pelaksana = buatPegawai('Pelaksana');
    $pd = buatPerjadinSelesai($pelaksana, buatPegawai('PPK'), buatPegawai('Bendahara'));
    $user = User::factory()->create(['id_pegawai_mitra' => $pelaksana->id_pegawai_mitra, 'role' => 'pegawai']);

    $this->actingAs($user)->put(route('monitoring-spj.update', $pd), ['no_spp_spm' => '22'])
        ->assertForbidden();

    expect($pd->fresh()->monitoringSpj)->toBeNull();
});

test('bulk edit menerapkan nilai yang sama ke banyak baris tanpa menimpa field kosong', function () {
    $ppk = buatPegawai('PPK');
    $bendahara = buatPegawai('Bendahara', 'bendahara');
    $pd1 = buatPerjadinSelesai(buatPegawai('A'), $ppk, $bendahara);
    $pd2 = buatPerjadinSelesai(buatPegawai('B'), $ppk, $bendahara);
    $pd1->monitoringSpj()->create(['no_spp_spm' => '22', 'sudah_spby' => true]);
    $user = User::factory()->create(['id_pegawai_mitra' => $bendahara->id_pegawai_mitra]);

    $this->actingAs($user)->patch(route('monitoring-spj.bulk-update'), [
        'ids' => [$pd1->id_perjalanan_dinas, $pd2->id_perjalanan_dinas],
        'posisi_spj' => 'PPSPM',
        'sudah_dibayar' => '1',
        'no_spp_spm' => '',
        'sudah_spby' => '',
    ])->assertRedirect(route('monitoring-spj.index'));

    $spj1 = $pd1->fresh()->monitoringSpj;
    $spj2 = $pd2->fresh()->monitoringSpj;
    expect($spj1->posisi_spj)->toBe('PPSPM')
        ->and($spj2->posisi_spj)->toBe('PPSPM')
        ->and($spj1->sudah_dibayar)->toBeTrue()
        ->and($spj2->sudah_dibayar)->toBeTrue()
        ->and($spj1->no_spp_spm)->toBe('22')
        ->and($spj1->sudah_spby)->toBeTrue();
});

test('bendahara bisa mengisi perjalanan dinas yang tidak mencantumkan dirinya', function () {
    $bendahara = buatPegawai('Bendahara Lain', 'bendahara');
    $pd = buatPerjadinSelesai(buatPegawai('A'), buatPegawai('PPK'), buatPegawai('Bendahara'));
    $user = User::factory()->create(['id_pegawai_mitra' => $bendahara->id_pegawai_mitra]);

    $this->actingAs($user)->put(route('monitoring-spj.update', $pd), ['posisi_spj' => 'PPSPM'])
        ->assertRedirect(route('monitoring-spj.index'));

    expect($pd->fresh()->monitoringSpj->posisi_spj)->toBe('PPSPM');
});

test('administrator tanpa peran ppk atau bendahara tidak bisa mengisi monitoring spj', function () {
    $pd = buatPerjadinSelesai(buatPegawai('A'), buatPegawai('PPK'), buatPegawai('Bendahara'));
    $admin = User::factory()->create(['id_pegawai_mitra' => buatPegawai('Admin')->id_pegawai_mitra, 'role' => 'administrator']);

    $this->actingAs($admin)->put(route('monitoring-spj.update', $pd), ['posisi_spj' => 'PPSPM'])->assertForbidden();
    $this->actingAs($admin)->patch(route('monitoring-spj.bulk-update'), [
        'ids' => [$pd->id_perjalanan_dinas],
        'posisi_spj' => 'PPSPM',
    ])->assertForbidden();

    expect($pd->fresh()->monitoringSpj)->toBeNull();
});

test('tunai/transfer ditolak jika pembayaran belum dicentang dan dikosongkan saat centang dilepas', function () {
    $ppk = buatPegawai('PPK', 'ppk');
    $pd = buatPerjadinSelesai(buatPegawai('A'), $ppk, buatPegawai('Bendahara'));
    $user = User::factory()->create(['id_pegawai_mitra' => $ppk->id_pegawai_mitra]);

    $this->actingAs($user)->put(route('monitoring-spj.update', $pd), ['cara_bayar' => 'tunai'])
        ->assertSessionHasErrors('cara_bayar');

    $this->actingAs($user)->put(route('monitoring-spj.update', $pd), ['sudah_dibayar' => '1', 'cara_bayar' => 'tunai']);
    expect($pd->fresh()->monitoringSpj->cara_bayar)->toBe('tunai');

    $this->actingAs($user)->patch(route('monitoring-spj.bulk-update'), [
        'ids' => [$pd->id_perjalanan_dinas],
        'sudah_dibayar' => '0',
    ]);
    expect($pd->fresh()->monitoringSpj->cara_bayar)->toBeNull();
});

test('spj dengan posisi dikembalikan tidak bisa ditandai sudah dibayar', function () {
    $ppk = buatPegawai('PPK', 'ppk');
    $pd = buatPerjadinSelesai(buatPegawai('Pelaksana'), $ppk, buatPegawai('Bendahara'));
    $user = User::factory()->create(['id_pegawai_mitra' => $ppk->id_pegawai_mitra]);

    $this->actingAs($user)->put(route('monitoring-spj.update', $pd), [
        'posisi_spj' => 'Dikembalikan',
        'sudah_dibayar' => '1',
        'sudah_spby' => '1',
    ])->assertRedirect(route('monitoring-spj.index'));

    $spj = $pd->fresh()->monitoringSpj;
    expect($spj->sudah_dibayar)->toBeFalse()
        ->and($spj->sudah_spby)->toBeTrue();
});

test('edit terpilih tidak bisa menandai dibayar baris yang posisinya dikembalikan', function () {
    $ppk = buatPegawai('PPK', 'ppk');
    $dikembalikan = buatPerjadinSelesai(buatPegawai('Pelaksana A'), $ppk, buatPegawai('Bendahara'));
    $biasa = buatPerjadinSelesai(buatPegawai('Pelaksana B'), $ppk, buatPegawai('Bendahara'));
    $dikembalikan->monitoringSpj()->create(['posisi_spj' => 'Dikembalikan']);
    $user = User::factory()->create(['id_pegawai_mitra' => $ppk->id_pegawai_mitra]);

    $this->actingAs($user)->patch(route('monitoring-spj.bulk-update'), [
        'ids' => [$dikembalikan->id_perjalanan_dinas, $biasa->id_perjalanan_dinas],
        'sudah_dibayar' => '1',
        'cara_bayar' => 'transfer',
    ])->assertRedirect(route('monitoring-spj.index'));

    expect($dikembalikan->fresh()->monitoringSpj->sudah_dibayar)->toBeFalse()
        ->and($dikembalikan->fresh()->monitoringSpj->cara_bayar)->toBeNull()
        ->and($biasa->fresh()->monitoringSpj->sudah_dibayar)->toBeTrue()
        ->and($biasa->fresh()->monitoringSpj->cara_bayar)->toBe('transfer');
});

test('ppspm setara ppk dan bendahara: bisa mengisi monitoring spj', function () {
    $ppspm = buatPegawai('PPSPM', 'ppspm');
    $pd = buatPerjadinSelesai(buatPegawai('Pelaksana'), buatPegawai('PPK'), buatPegawai('Bendahara'));
    $user = User::factory()->create(['id_pegawai_mitra' => $ppspm->id_pegawai_mitra]);

    expect($user->bisaIsiMonitoringSpj())->toBeTrue();

    $this->actingAs($user)->put(route('monitoring-spj.update', $pd), ['posisi_spj' => 'PPSPM'])
        ->assertRedirect(route('monitoring-spj.index'));
    expect($pd->fresh()->monitoringSpj->posisi_spj)->toBe('PPSPM');

    $this->actingAs($user)->patch(route('monitoring-spj.bulk-update'), [
        'ids' => [$pd->id_perjalanan_dinas],
        'posisi_spj' => 'Bendahara',
    ])->assertRedirect(route('monitoring-spj.index'));
    expect($pd->fresh()->monitoringSpj->posisi_spj)->toBe('Bendahara');
});
