<?php

use App\Models\PegawaiMitra;
use App\Models\PengeluaranRiil;
use App\Models\PerjalananDinas;
use App\Models\User;

test('susunan uraian pengeluaran riil tersimpan dan ikut prefill form edit', function () {
    $pegawai = PegawaiMitra::create(['nama' => 'Pelaksana Uji', 'status_kepegawaian' => 'pegawai']);
    $user = User::factory()->create(['id_pegawai_mitra' => $pegawai->id_pegawai_mitra]);
    $susunan = ['tipe' => 'antar', 'moda' => 'darat', 'dari' => 'Kelurahan Beriwit', 'ke' => 'Desa Juking Supan', 'di' => '', 'pp' => true, 'tanggal' => '2026-11-14'];

    $this->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'dalam_kota_lebih_8_jam',
        'status_draft' => 'draft_sesi1',
        'pengeluaran' => [
            ['jenis' => 'transportasi', 'nominal' => 140000, 'susunan' => json_encode($susunan),
                'uraian' => 'Biaya transportasi darat dari Kelurahan Beriwit ke Desa Juking Supan (PP) tanggal 14 November 2026'],
            // Ditulis bebas: tanpa susunan.
            ['jenis' => 'akomodasi', 'nominal' => 100000, 'susunan' => '', 'uraian' => 'Menginap di rumah warga'],
        ],
    ])->assertSessionHasNoErrors();

    $baris = PengeluaranRiil::orderBy('id_pengeluaran')->get();
    expect($baris[0]->susunan)->toBe($susunan)
        ->and($baris[0]->uraian)->toContain('(PP) tanggal 14 November 2026')
        ->and($baris[1]->susunan)->toBeNull();

    $prefill = PerjalananDinas::latest('id_perjalanan_dinas')->first()->toFormPrefill()['pengeluaran'];
    expect($prefill[0]['susunan'])->toBe($susunan)
        ->and($prefill[1]['susunan'])->toBeNull();
});
