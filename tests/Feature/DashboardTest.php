<?php

use App\Models\MonitoringSpj;
use App\Models\PerjalananDinas;
use App\Models\User;

test('dashboard menampilkan jumlah perjadin dan posisi SPJ tanpa tabel rekap', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $html = $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Jumlah Perjalanan Dinas')
        ->assertSeeInOrder(['Posisi SPJ', 'Belum Diproses', 'PPK', 'PPSPM', 'Bendahara', 'Dikembalikan'])
        ->assertDontSee('table-rekap-perjadin')
        ->getContent();

    expect($html)->toContain('Lihat Monitoring SPJ');
});
