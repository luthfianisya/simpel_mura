<?php

use App\Models\Gup;
use App\Models\MonitoringSpj;
use App\Models\User;

function rincianBaru(array $timpa = []): array
{
    return array_merge([
        'uraian_rincian' => 'Belanja ATK kegiatan Susenas',
        'tanggal_rincian' => '2026-02-10',
        'penerima_rincian' => 'Toko Sumber Rejeki',
        'pembebanan_rincian' => '2899.BMA.006.005.A.521211',
        'nomor_fp' => 'FP-050/2026',
        'nominal_rincian' => 250000,
    ], $timpa);
}

test('tim keuangan bisa menambah rincian non-perjadin langsung ke sebuah gup', function () {
    [$user, $spj] = siapkanPpkDanSpj(2);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj), 'tanggal_gup' => '2026-02-18']);
    $gup = Gup::sole();

    $this->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru(['id_gup' => $gup->id_gup]))
        ->assertRedirect(route('monitoring-spj.index'))
        ->assertSessionHas('success');

    $rincian = MonitoringSpj::rincianNonPerjadin()->sole();
    expect($rincian->id_gup)->toBe($gup->id_gup)
        ->and($rincian->jenis_pembayaran)->toBe('GUP')
        ->and($rincian->tanggal_gup->format('Y-m-d'))->toBe('2026-02-18')
        ->and($rincian->uraian_rincian)->toBe('Belanja ATK kegiatan Susenas');

    $response = $this->actingAs($user)->get(route('monitoring-spj.index'))->assertOk()
        ->assertSee('Belanja ATK kegiatan Susenas')
        ->assertSee('Toko Sumber Rejeki')
        ->assertSee('FP-050/2026')
        ->assertSee('Non-Perjadin');
    // 2 SPJ perjadin @ Rp 80.000 + rincian Rp 250.000.
    expect($response->viewData('kelompokGup')['0001'])->toMatchArray(['jumlah' => 3, 'total' => 410000]);
});

test('rincian tanpa gup masuk kelompok belum masuk gup', function () {
    [$user] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru(['jenis_pembayaran' => 'LS KPPN']));

    $kelompok = $this->actingAs($user)->get(route('monitoring-spj.index'))->viewData('kelompokGup');
    expect($kelompok['0000'])->toMatchArray(['jumlah' => 2, 'total' => 330000])
        ->and(MonitoringSpj::rincianNonPerjadin()->sole()->jenis_pembayaran)->toBe('LS KPPN');
});

test('rincian bisa diedit beserta isian monitoringnya dan dihapus', function () {
    [$user] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru());
    $rincian = MonitoringSpj::rincianNonPerjadin()->sole();

    $this->actingAs($user)->put(route('monitoring-spj.rincian.update', $rincian), rincianBaru([
        'uraian_rincian' => 'Belanja ATK (revisi)',
        'nominal_rincian' => 275000,
        'posisi_spj' => 'Bendahara',
        'sudah_dibayar' => '1',
        'cara_bayar' => 'transfer',
    ]))->assertRedirect(route('monitoring-spj.index'));

    $rincian->refresh();
    expect($rincian->uraian_rincian)->toBe('Belanja ATK (revisi)')
        ->and((float) $rincian->nominal_rincian)->toBe(275000.0)
        ->and($rincian->posisi_spj)->toBe('Bendahara')
        ->and($rincian->sudah_dibayar)->toBeTrue()
        ->and($rincian->cara_bayar)->toBe('transfer');

    $this->actingAs($user)->delete(route('monitoring-spj.rincian.destroy', $rincian))->assertRedirect(route('monitoring-spj.index'));
    expect(MonitoringSpj::rincianNonPerjadin()->count())->toBe(0);
});

test('menghapus rincian terakhir di sebuah gup ikut menghapus gup kosongnya', function () {
    [$user] = siapkanPpkDanSpj(1);
    $gup = Gup::create(['tanggal_gup' => '2026-03-02']);
    $this->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru(['id_gup' => $gup->id_gup]));

    $this->actingAs($user)->delete(route('monitoring-spj.rincian.destroy', MonitoringSpj::rincianNonPerjadin()->sole()));
    expect(Gup::find($gup->id_gup))->toBeNull();
});

test('rincian ikut aksi massal: edit terpilih, buat gup, dan keluarkan dari gup', function () {
    [$user, $spj] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru());
    $rincian = MonitoringSpj::rincianNonPerjadin()->sole();

    $this->actingAs($user)->patch(route('monitoring-spj.bulk-update'), [
        'ids' => idSpj($spj), 'rincian_ids' => [$rincian->id_monitoring_spj], 'posisi_spj' => 'PPSPM',
    ])->assertSessionHas('success', '2 baris monitoring SPJ berhasil diperbarui.');
    expect($rincian->fresh()->posisi_spj)->toBe('PPSPM')
        ->and($spj[0]->fresh()->monitoringSpj->posisi_spj)->toBe('PPSPM');

    $this->actingAs($user)->post(route('gup.store'), [
        'ids' => idSpj($spj), 'rincian_ids' => [$rincian->id_monitoring_spj], 'tanggal_gup' => '2026-04-01',
    ])->assertSessionHas('success', '2 SPJ berhasil dikelompokkan ke GUP ke-1 Tahun 2026.');
    expect($rincian->fresh()->id_gup)->toBe(Gup::sole()->id_gup)
        ->and($rincian->fresh()->tanggal_gup->format('Y-m-d'))->toBe('2026-04-01');

    $this->actingAs($user)->patch(route('gup.keluarkan'), ['rincian_ids' => [$rincian->id_monitoring_spj]])
        ->assertSessionHas('success', '1 SPJ berhasil dikeluarkan dari GUP.');
    expect($rincian->fresh()->id_gup)->toBeNull()
        ->and($spj[0]->fresh()->monitoringSpj->id_gup)->not->toBeNull();
});

test('aksi massal tanpa baris terpilih ditolak', function () {
    [$user] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('gup.store'), ['tanggal_gup' => '2026-04-01'])->assertSessionHasErrors('ids');
    expect(Gup::count())->toBe(0);
});

test('uraian dan nominal rincian wajib diisi', function () {
    [$user] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru(['uraian_rincian' => '', 'nominal_rincian' => '']))
        ->assertSessionHasErrors(['uraian_rincian', 'nominal_rincian']);
    expect(MonitoringSpj::rincianNonPerjadin()->count())->toBe(0);
});

test('pengguna selain tim keuangan tidak bisa menambah rincian', function () {
    siapkanPpkDanSpj(1);
    $pegawai = User::factory()->create(['id_pegawai_mitra' => buatPegawai('Pegawai Biasa')->id_pegawai_mitra]);

    $this->actingAs($pegawai)->post(route('monitoring-spj.rincian.store'), rincianBaru())->assertForbidden();
    expect(MonitoringSpj::rincianNonPerjadin()->count())->toBe(0);
});

test('rute rincian tidak bisa dipakai untuk baris monitoring milik perjalanan dinas', function () {
    [$user, $spj] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->put(route('monitoring-spj.update', $spj[0]), ['posisi_spj' => 'PPK']);
    $milikPerjadin = $spj[0]->fresh()->monitoringSpj;

    $this->actingAs($user)->delete(route('monitoring-spj.rincian.destroy', $milikPerjadin))->assertNotFound();
    expect($milikPerjadin->fresh())->not->toBeNull();
});

test('rincian ikut dihitung di monitoring gup dan posisi spj dashboard', function () {
    [$user] = siapkanPpkDanSpj(1);
    $gup = Gup::create(['tanggal_gup' => '2026-05-05']);
    $this->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru(['id_gup' => $gup->id_gup, 'posisi_spj' => 'Bendahara']));

    $this->actingAs($user)->get(route('monitoring-gup.index', ['tahun' => 2026]))->assertOk()
        ->assertSee('Rp 250.000')
        ->assertSee('Belanja ATK kegiatan Susenas');

    $statistik = $this->actingAs($user)->get(route('dashboard'))->viewData('statistikPosisiSpj');
    expect($statistik['Bendahara'])->toBe(1)->and($statistik['Belum Diproses'])->toBe(1);
});
