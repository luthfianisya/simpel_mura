<?php

use App\Models\PegawaiMitra;
use App\Models\User;

function adminKelolaMitra(): User
{
    return User::factory()->create(['role' => 'administrator']);
}

test('halaman kelola mitra menampilkan kolom sobat id, alamat, dan sebagai', function () {
    PegawaiMitra::create([
        'nama' => 'Mitra Satu', 'status_kepegawaian' => 'mitra',
        'sobat_id' => '621300000001', 'alamat' => 'Jl. Pelita', 'jenis_mitra' => 'pendataan_pengolahan',
    ]);

    $this->actingAs(adminKelolaMitra())->get(route('pegawai-mitra.mitra'))
        ->assertOk()
        ->assertSee('Sobat ID')
        ->assertSee('621300000001')
        ->assertSee('Mitra Pendataan &amp; Pengolahan', false)
        ->assertDontSee('Peran Pejabat');
});

test('mitra disimpan dengan sobat id, alamat, sebagai dan jabatan mengikuti peran', function () {
    $this->actingAs(adminKelolaMitra())->post(route('pegawai-mitra.store'), [
        'status_kepegawaian' => 'mitra',
        'nama' => 'Mitra Baru',
        'sobat_id' => '621300000002',
        'alamat' => 'Jl. Merdeka No. 2',
        'jenis_mitra' => 'pendataan',
        'nip' => 'diabaikan',
    ])->assertSessionHasNoErrors();

    $mitra = PegawaiMitra::where('nama', 'Mitra Baru')->first();
    expect($mitra->sobat_id)->toBe('621300000002')
        ->and($mitra->alamat)->toBe('Jl. Merdeka No. 2')
        ->and($mitra->jabatan)->toBe('Mitra Pendataan')
        ->and($mitra->nip)->toBeNull();
});

test('mitra wajib punya sobat id unik, alamat, dan sebagai', function () {
    $admin = adminKelolaMitra();
    PegawaiMitra::create(['nama' => 'Lama', 'status_kepegawaian' => 'mitra', 'sobat_id' => '621300000003']);

    $this->actingAs($admin)->post(route('pegawai-mitra.store'), [
        'status_kepegawaian' => 'mitra',
        'nama' => 'Mitra Dobel',
        'sobat_id' => '621300000003',
    ])->assertSessionHasErrors(['sobat_id', 'alamat', 'jenis_mitra']);
});

test('pegawai tetap bisa disimpan tanpa data mitra', function () {
    $this->actingAs(adminKelolaMitra())->post(route('pegawai-mitra.store'), [
        'status_kepegawaian' => 'pegawai',
        'nama' => 'Pegawai Biasa',
        'nip' => '19990307 202104 1 001',
    ])->assertSessionHasNoErrors();

    expect(PegawaiMitra::where('nama', 'Pegawai Biasa')->value('nip'))->toBe('19990307 202104 1 001');
});
