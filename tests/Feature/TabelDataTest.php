<?php

use App\Models\User;

test('semua halaman daftar memakai datatable dan tidak lagi punya baris kosong ber-colspan', function () {
    $admin = User::factory()->create(['role' => 'administrator']);

    foreach (['user.index', 'pegawai-mitra.pegawai', 'pegawai-mitra.mitra', 'rate.index', 'wilayah.index'] as $route) {
        $this->actingAs($admin)->get(route($route))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertDontSee('colspan', false);
    }
});

test('pegawai, mitra, dan akun ada di submenu manajemen pengguna', function () {
    $admin = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($admin)->get(route('pegawai-mitra.mitra'))
        ->assertOk()
        ->assertSeeInOrder(['Manajemen Pengguna', 'Pegawai', 'Mitra', 'Akun'])
        ->assertSee('menu-item active open', false)
        ->assertDontSee('>Kelola Akun</div>', false);

    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $this->actingAs($pegawai)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('<div>Manajemen Pengguna</div>', false);
});
