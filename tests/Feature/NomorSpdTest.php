<?php

use App\Models\PegawaiMitra;
use App\Models\PenomoranSpd;
use App\Models\PerjalananDinas;
use App\Models\User;

function userPemohonSpd(): User
{
    $pegawai = PegawaiMitra::create(['nama' => 'Pemohon SPD', 'status_kepegawaian' => 'pegawai']);

    return User::factory()->create(['id_pegawai_mitra' => $pegawai->id_pegawai_mitra]);
}

function simpanDraftSpd($test, User $user, ?string $noSpd = '1/62130/KU.340/2026', string $jenis = 'biasa')
{
    return $test->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => $jenis,
        'status_draft' => 'draft_sesi1',
        'tanggal_spd' => '2026-11-02',
        'no_spd' => $noSpd,
    ]);
}

test('pratinjau nomor spd dimulai dari nomor terakhir di luar simpel + 1', function () {
    $this->actingAs(userPemohonSpd())
        ->getJson(route('perjalanan-dinas.nomor-spd-berikutnya', ['tahun' => 2026]))
        ->assertJson(['no_spd' => '272/62130/KU.340/2026']);
});

test('nomor spd ditetapkan server saat simpan dan selalu naik tanpa dobel', function () {
    $user = userPemohonSpd();

    // Dua orang sama-sama melihat pratinjau 272 lalu menyimpan — yang kedua dapat 273.
    simpanDraftSpd($this, $user, '272/62130/KU.340/2026');
    simpanDraftSpd($this, $user, '272/62130/KU.340/2026');
    // Nomor ketikan sembarang dari client diabaikan.
    simpanDraftSpd($this, $user, '999/62130/KU.340/2026');

    expect(PerjalananDinas::orderBy('id_perjalanan_dinas')->pluck('no_spd')->all())->toBe([
        '272/62130/KU.340/2026',
        '273/62130/KU.340/2026',
        '274/62130/KU.340/2026',
    ]);
});

test('draft yang belum ambil nomor tetap kosong dan jenis tanpa spd tidak dapat nomor', function () {
    $user = userPemohonSpd();

    simpanDraftSpd($this, $user, null);
    simpanDraftSpd($this, $user, '272/62130/KU.340/2026', 'dalam_kota_kurang_8_jam');

    expect(PerjalananDinas::pluck('no_spd')->filter()->all())->toBe([]);
});

test('nomor dari draft yang dihapus dipakai lagi supaya tidak bolong', function () {
    $user = userPemohonSpd();
    simpanDraftSpd($this, $user);
    simpanDraftSpd($this, $user);
    simpanDraftSpd($this, $user);

    $tengah = PerjalananDinas::where('no_spd', '273/62130/KU.340/2026')->first();
    $this->actingAs($user)->delete(route('perjalanan-dinas.destroy', $tengah));

    simpanDraftSpd($this, $user);

    expect(PerjalananDinas::latest('id_perjalanan_dinas')->first()->no_spd)->toBe('273/62130/KU.340/2026');
});

test('nomor spd dokumen tidak berubah saat dokumen diedit', function () {
    $user = userPemohonSpd();
    simpanDraftSpd($this, $user);
    $pd = PerjalananDinas::first();

    $this->actingAs($user)->put(route('perjalanan-dinas.update', $pd), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'tanggal_spd' => '2026-11-02',
        'no_spd' => '500/62130/KU.340/2026',
    ]);

    expect($pd->fresh()->no_spd)->toBe('272/62130/KU.340/2026');
});

test('copy dokumen tidak ikut menyalin nomor spd', function () {
    $user = userPemohonSpd();
    simpanDraftSpd($this, $user);
    $sumber = PerjalananDinas::first();

    $this->actingAs($user)->get(route('perjalanan-dinas.create', ['jenis' => 'biasa', 'from' => $sumber->id_perjalanan_dinas]))
        ->assertOk();

    expect(session()->getOldInput('no_spd'))->toBeNull();
});

test('tahun tanpa pengaturan nomor awal dimulai dari 1', function () {
    expect(PenomoranSpd::berikutnya(2027))->toBe('1/62130/KU.340/2027');
});

test('orang kedua yang menyimpan nomor pratinjau yang sama mendapat nomor berikutnya dan peringatan', function () {
    $user = userPemohonSpd();

    simpanDraftSpd($this, $user, '272/62130/KU.340/2026')->assertSessionMissing('warning');
    simpanDraftSpd($this, $user, '272/62130/KU.340/2026')
        ->assertSessionHas('warning', fn ($pesan) => str_contains($pesan, '272/62130/KU.340/2026') && str_contains($pesan, '273/62130/KU.340/2026'));
});
