<?php

use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\User;

test('mitra yang diketik sendiri ditambahkan ke data mitra dan dipakai sebagai pelaksana', function () {
    $pegawai = PegawaiMitra::create(['nama' => 'Pemohon', 'status_kepegawaian' => 'pegawai']);
    $user = User::factory()->create(['id_pegawai_mitra' => $pegawai->id_pegawai_mitra]);
    $simpan = fn (string $nilai) => $this->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'dalam_kota_kurang_8_jam',
        'status_draft' => 'draft_sesi1',
        'id_pegawai_mitra_pelaksana' => $nilai,
    ])->assertSessionHasNoErrors();

    $simpan('baru:Budi   Santoso');
    // Nama sama (beda huruf besar/kecil) tidak membuat mitra dobel.
    $simpan('baru:budi santoso');

    $mitra = PegawaiMitra::where('status_kepegawaian', 'mitra')->get();
    expect($mitra)->toHaveCount(1)
        ->and($mitra->first()->nama)->toBe('Budi Santoso')
        ->and(PerjalananDinas::pluck('id_pegawai_mitra_pelaksana')->unique()->all())->toBe([$mitra->first()->id_pegawai_mitra]);
});
