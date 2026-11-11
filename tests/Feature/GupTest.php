<?php

use App\Models\Gup;
use App\Models\PerjalananDinas;
use App\Models\User;

function siapkanPpkDanSpj(int $jumlahSpj = 3): array
{
    $ppk = buatPegawai('PPK', 'ppk');
    $daftarSpj = collect(range(1, $jumlahSpj))
        ->map(fn ($i) => buatPerjadinSelesai(buatPegawai('Pelaksana ' . $i), $ppk, buatPegawai('Bendahara ' . $i)));

    return [User::factory()->create(['id_pegawai_mitra' => $ppk->id_pegawai_mitra]), $daftarSpj];
}

function idSpj($daftar): array
{
    return collect($daftar)->pluck('id_perjalanan_dinas')->all();
}

test('ppk bisa membuat gup baru dari spj terpilih', function () {
    [$user, $spj] = siapkanPpkDanSpj();

    $this->actingAs($user)->post(route('gup.store'), [
        'ids' => idSpj([$spj[0], $spj[1]]),
        'tanggal_gup' => '2026-02-18',
    ])->assertRedirect(route('monitoring-spj.index'))
        ->assertSessionHas('success', '2 SPJ berhasil dikelompokkan ke GUP ke-1 Bulan Februari.');

    $gup = Gup::sole();
    foreach ([$spj[0], $spj[1]] as $pd) {
        $m = $pd->fresh()->monitoringSpj;
        expect($m->id_gup)->toBe($gup->id_gup)
            ->and($m->jenis_pembayaran)->toBe('GUP')
            ->and($m->tanggal_gup->format('Y-m-d'))->toBe('2026-02-18');
    }
    expect($spj[2]->fresh()->monitoringSpj)->toBeNull();
});

test('spj bisa dimasukkan ke gup yang ada dan gup lama yang kosong ikut terhapus', function () {
    [$user, $spj] = siapkanPpkDanSpj();
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj([$spj[0]]), 'tanggal_gup' => '2026-01-21']);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj([$spj[1]]), 'tanggal_gup' => '2026-01-28']);
    [$gupA, $gupB] = Gup::orderBy('id_gup')->get();

    // SPJ ke-2 (satu-satunya isi GUP B) dipindah ke GUP A, SPJ ke-3 ditambahkan.
    $this->actingAs($user)->patch(route('gup.tambah', $gupA), ['ids' => idSpj([$spj[1], $spj[2]])])
        ->assertRedirect(route('monitoring-spj.index'));

    expect($gupA->monitoringSpj()->count())->toBe(3)
        ->and(Gup::find($gupB->id_gup))->toBeNull()
        ->and($spj[2]->fresh()->monitoringSpj->tanggal_gup->format('Y-m-d'))->toBe('2026-01-21');
});

test('spj bisa dikeluarkan dari gup', function () {
    [$user, $spj] = siapkanPpkDanSpj();
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj), 'tanggal_gup' => '2026-03-11']);

    $this->actingAs($user)->patch(route('gup.keluarkan'), ['ids' => idSpj([$spj[0]])])
        ->assertRedirect(route('monitoring-spj.index'));

    $m = $spj[0]->fresh()->monitoringSpj;
    expect($m->id_gup)->toBeNull()
        ->and($m->tanggal_gup)->toBeNull()
        ->and(Gup::sole()->monitoringSpj()->count())->toBe(2);
});

test('ubah tanggal gup ikut mengubah tanggal gup semua spj di dalamnya', function () {
    [$user, $spj] = siapkanPpkDanSpj(2);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj)]);
    $gup = Gup::sole();
    expect($gup->tanggal_gup)->toBeNull();

    $this->actingAs($user)->put(route('gup.update', $gup), ['tanggal_gup' => '2026-04-08'])
        ->assertRedirect(route('monitoring-spj.index'));

    foreach ($spj as $pd) {
        expect($pd->fresh()->monitoringSpj->tanggal_gup->format('Y-m-d'))->toBe('2026-04-08');
    }
});

test('hapus gup mengembalikan spj ke belum masuk gup', function () {
    [$user, $spj] = siapkanPpkDanSpj(2);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj), 'tanggal_gup' => '2026-04-17']);

    $this->actingAs($user)->delete(route('gup.destroy', Gup::sole()))->assertRedirect(route('monitoring-spj.index'));

    expect(Gup::count())->toBe(0)
        ->and($spj[0]->fresh()->monitoringSpj->id_gup)->toBeNull()
        ->and($spj[0]->fresh()->monitoringSpj->jenis_pembayaran)->toBe('GUP');
});

test('mengganti jenis pembayaran ke selain gup mengeluarkan spj dari gup-nya', function () {
    [$user, $spj] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj), 'tanggal_gup' => '2026-05-12']);

    $this->actingAs($user)->put(route('monitoring-spj.update', $spj[0]), ['jenis_pembayaran' => 'LS KPPN']);

    $m = $spj[0]->fresh()->monitoringSpj;
    expect($m->id_gup)->toBeNull()->and($m->tanggal_gup)->toBeNull()->and(Gup::count())->toBe(0);
});

test('nomor gup dihitung per bulan menurut urutan tanggal dan mulai lagi tiap bulan', function () {
    Gup::create(['tanggal_gup' => '2026-11-20']);
    Gup::create(['tanggal_gup' => '2026-11-03']);
    Gup::create(['tanggal_gup' => '2026-12-01']);
    Gup::create(['tanggal_gup' => '2026-11-12']);
    Gup::create(['tanggal_gup' => '2025-11-05']);

    $label = Gup::denganNomor()->mapWithKeys(fn ($g) => [$g->tanggal_gup->format('Y-m-d') => $g->label]);
    expect($label->all())->toBe([
        '2026-11-20' => 'GUP ke-3 Bulan November',
        '2026-11-03' => 'GUP ke-1 Bulan November',
        '2026-12-01' => 'GUP ke-1 Bulan Desember',
        '2026-11-12' => 'GUP ke-2 Bulan November',
        // November tahun lain dihitung terpisah.
        '2025-11-05' => 'GUP ke-1 Bulan November',
    ]);
});

test('tanggal transfer dan pajak gup bisa diisi dan tampil di kepala gup', function () {
    [$user, $spj] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj), 'tanggal_gup' => '2026-11-03']);
    $gup = Gup::sole();

    $this->actingAs($user)->put(route('gup.update', $gup), [
        'tanggal_gup' => '2026-11-03',
        'tanggal_transfer' => '2026-11-10',
        'pajak' => 'Rp 1.250.000',
    ])->assertRedirect(route('monitoring-spj.index'))
        ->assertSessionHas('success', 'Data GUP ke-1 Bulan November berhasil disimpan.');

    $gup->refresh();
    expect($gup->tanggal_transfer->format('Y-m-d'))->toBe('2026-11-10')
        ->and((float) $gup->pajak)->toBe(1250000.0);

    $kelompok = $this->actingAs($user)->get(route('monitoring-spj.index'))->viewData('kelompokGup');
    expect($kelompok['0001'])->toMatchArray([
        'label' => 'GUP ke-1 Bulan November',
        'tanggal_transfer' => '2026-11-10',
        'pajak' => 1250000.0,
    ]);

    // Dikosongkan lagi.
    $this->actingAs($user)->put(route('gup.update', $gup), ['tanggal_gup' => '2026-11-03', 'tanggal_transfer' => '', 'pajak' => '']);
    expect($gup->fresh()->tanggal_transfer)->toBeNull()->and($gup->fresh()->pajak)->toBeNull();
});

test('pajak gup harus angka dan tidak negatif', function () {
    [$user, $spj] = siapkanPpkDanSpj(1);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj), 'tanggal_gup' => '2026-11-03']);

    $this->actingAs($user)->put(route('gup.update', Gup::sole()), ['tanggal_gup' => '2026-11-03', 'pajak' => '-5000'])
        ->assertSessionHasErrors('pajak');
});

test('tabel monitoring spj dikelompokkan per gup dengan total nominalnya', function () {
    [$user, $spj] = siapkanPpkDanSpj(3);
    $this->actingAs($user)->post(route('gup.store'), ['ids' => idSpj([$spj[0], $spj[1]]), 'tanggal_gup' => '2026-02-18']);

    $kelompok = $this->actingAs($user)->get(route('monitoring-spj.index'))->assertOk()->viewData('kelompokGup');

    expect($kelompok['0000'])->toMatchArray(['label' => 'Belum Masuk GUP', 'jumlah' => 1, 'total' => 80000])
        ->and($kelompok['0001'])->toMatchArray(['label' => 'GUP ke-1 Bulan Februari', 'jumlah' => 2, 'total' => 160000]);
});

test('pengguna selain ppk ppspm bendahara tidak bisa mengelompokkan gup', function () {
    [, $spj] = siapkanPpkDanSpj(1);
    $pegawai = User::factory()->create(['id_pegawai_mitra' => buatPegawai('Pegawai Biasa')->id_pegawai_mitra]);

    $this->actingAs($pegawai)->post(route('gup.store'), ['ids' => idSpj($spj)])->assertForbidden();
    expect(Gup::count())->toBe(0);
});

test('spj yang masih draft tidak bisa dikelompokkan ke gup', function () {
    [$user] = siapkanPpkDanSpj(1);
    $draft = PerjalananDinas::create([
        'id_pemohon' => buatPegawai('Pemohon Draft')->id_pegawai_mitra,
        'jenis_perjadin' => 'biasa',
        'perihal' => 'Draft',
        'status_draft' => 'draft_sesi1',
    ]);

    $this->actingAs($user)->post(route('gup.store'), ['ids' => [$draft->id_perjalanan_dinas]])
        ->assertSessionHasErrors('ids.0');
    expect(Gup::count())->toBe(0);
});
