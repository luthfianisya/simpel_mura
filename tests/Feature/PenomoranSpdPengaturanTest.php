<?php

use App\Models\PenomoranSpd;
use App\Models\User;

function adminPenomoran(): User
{
    return User::factory()->create(['role' => 'administrator']);
}

test('admin bisa melihat halaman penomoran spd beserta nomor berikutnya', function () {
    PenomoranSpd::updateOrCreate(['tahun' => 2026], ['nomor_awal' => 271]);

    $this->actingAs(adminPenomoran())->get(route('penomoran-spd.index'))
        ->assertOk()
        ->assertSee('Penomoran SPD')
        ->assertSee('272/62130/KU.340/2026');
});

test('mengubah nomor pertama jadi 276 membuat spd berikutnya bernomor 276', function () {
    $penomoran = PenomoranSpd::updateOrCreate(['tahun' => 2026], ['nomor_awal' => 271]);

    $this->actingAs(adminPenomoran())
        ->put(route('penomoran-spd.update', $penomoran), ['nomor_mulai' => 276])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($penomoran->fresh()->nomor_awal)->toBe(275)
        ->and(PenomoranSpd::berikutnya(2026))->toBe('276/62130/KU.340/2026');
});

test('admin bisa menambah pengaturan untuk tahun baru', function () {
    $this->actingAs(adminPenomoran())
        ->post(route('penomoran-spd.store'), ['tahun' => 2027, 'nomor_mulai' => 10])
        ->assertSessionHas('success');

    expect(PenomoranSpd::where('tahun', 2027)->value('nomor_awal'))->toBe(9)
        ->and(PenomoranSpd::berikutnya(2027))->toBe('10/62130/KU.340/2027');
});

test('tahun yang sudah ada tidak bisa ditambah dua kali dan nomor harus minimal 1', function () {
    PenomoranSpd::updateOrCreate(['tahun' => 2026], ['nomor_awal' => 271]);
    $admin = adminPenomoran();

    $this->actingAs($admin)->post(route('penomoran-spd.store'), ['tahun' => 2026, 'nomor_mulai' => 5])
        ->assertSessionHasErrors('tahun');
    $this->actingAs($admin)->put(route('penomoran-spd.update', PenomoranSpd::where('tahun', 2026)->first()), ['nomor_mulai' => 0])
        ->assertSessionHasErrors('nomor_mulai');

    expect(PenomoranSpd::where('tahun', 2026)->value('nomor_awal'))->toBe(271);
});

test('selain administrator tidak bisa membuka atau mengubah penomoran spd', function () {
    $penomoran = PenomoranSpd::updateOrCreate(['tahun' => 2026], ['nomor_awal' => 271]);
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('penomoran-spd.index'))->assertForbidden();
    $this->actingAs($pegawai)->put(route('penomoran-spd.update', $penomoran), ['nomor_mulai' => 1])->assertForbidden();

    expect($penomoran->fresh()->nomor_awal)->toBe(271);
});
