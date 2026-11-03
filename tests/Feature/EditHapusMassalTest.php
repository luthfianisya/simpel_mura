<?php

use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\User;

function adminMassal(): User
{
    return User::factory()->create(['role' => 'administrator']);
}

test('halaman kelola akun, pegawai, dan mitra punya checkbox pilih massal', function () {
    $admin = adminMassal();

    foreach (['user.index', 'pegawai-mitra.pegawai', 'pegawai-mitra.mitra'] as $route) {
        $this->actingAs($admin)->get(route($route))
            ->assertOk()
            ->assertSee('data-pilih-massal', false)
            ->assertSee('Hapus Terpilih');
    }
});

test('edit massal akun mengubah peran dan status tapi melewati pencabutan admin diri sendiri', function () {
    $admin = adminMassal();
    $a = User::factory()->create(['role' => 'administrator']);
    $b = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($admin)->patch(route('user.bulk-update'), [
        'ids' => [$admin->id, $a->id, $b->id],
        'role' => 'pegawai',
        'is_active' => '',
    ])->assertSessionHas('success');

    expect($a->fresh()->role)->toBe('pegawai')
        ->and($b->fresh()->role)->toBe('pegawai')
        ->and($admin->fresh()->role)->toBe('administrator');
});

test('hapus massal akun tidak menghapus akun sendiri', function () {
    $admin = adminMassal();
    $lain = User::factory()->count(2)->create();

    $this->actingAs($admin)->delete(route('user.bulk-destroy'), [
        'ids' => [$admin->id, ...$lain->pluck('id')],
    ]);

    expect(User::find($admin->id))->not->toBeNull()
        ->and(User::whereIn('id', $lain->pluck('id'))->count())->toBe(0);
});

test('edit massal pegawai mengubah field yang diisi saja dan bisa mengosongkan peran pejabat', function () {
    $p1 = PegawaiMitra::create(['nama' => 'P1', 'status_kepegawaian' => 'pegawai', 'jabatan' => 'Statistisi', 'peran_pejabat' => 'ppk']);
    $p2 = PegawaiMitra::create(['nama' => 'P2', 'status_kepegawaian' => 'pegawai', 'jabatan' => 'Pranata Komputer']);

    $this->actingAs(adminMassal())->patch(route('pegawai-mitra.bulk-update'), [
        'status_kepegawaian' => 'pegawai',
        'ids' => [$p1->id_pegawai_mitra, $p2->id_pegawai_mitra],
        'golongan' => 'III/b',
        'jabatan' => '',
        'peran_pejabat' => '-',
    ])->assertSessionHas('success');

    expect($p1->fresh()->golongan)->toBe('III/b')
        ->and($p1->fresh()->jabatan)->toBe('Statistisi')
        ->and($p1->fresh()->peran_pejabat)->toBeNull()
        ->and($p2->fresh()->jabatan)->toBe('Pranata Komputer');
});

test('edit massal mitra mengubah sebagai beserta jabatannya', function () {
    $m = PegawaiMitra::create(['nama' => 'M1', 'status_kepegawaian' => 'mitra', 'jenis_mitra' => 'pendataan']);

    $this->actingAs(adminMassal())->patch(route('pegawai-mitra.bulk-update'), [
        'status_kepegawaian' => 'mitra',
        'ids' => [$m->id_pegawai_mitra],
        'jenis_mitra' => 'pendataan_pengolahan',
    ]);

    expect($m->fresh()->jenis_mitra)->toBe('pendataan_pengolahan')
        ->and($m->fresh()->jabatan)->toBe('Mitra Pendataan & Pengolahan');
});

test('hapus massal pegawai melewati yang masih dipakai perjalanan dinas', function () {
    $dipakai = PegawaiMitra::create(['nama' => 'Dipakai', 'status_kepegawaian' => 'pegawai']);
    $bebas = PegawaiMitra::create(['nama' => 'Bebas', 'status_kepegawaian' => 'pegawai']);
    PerjalananDinas::create(['id_pemohon' => $dipakai->id_pegawai_mitra, 'jenis_perjadin' => 'biasa']);

    $this->actingAs(adminMassal())->delete(route('pegawai-mitra.bulk-destroy'), [
        'status_kepegawaian' => 'pegawai',
        'ids' => [$dipakai->id_pegawai_mitra, $bebas->id_pegawai_mitra],
    ])->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'Dipakai'));

    expect(PegawaiMitra::find($bebas->id_pegawai_mitra))->toBeNull()
        ->and(PegawaiMitra::find($dipakai->id_pegawai_mitra))->not->toBeNull();
});
