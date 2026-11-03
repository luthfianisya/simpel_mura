<?php

use App\Models\Feedback;
use App\Models\User;

test('semua akun bisa mengirim feedback', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->post(route('feedback.store'), [
        'isi' => 'Muncul pesan gagal saat export laporan.',
    ])->assertRedirect(route('feedback.index'));

    $feedback = Feedback::first();
    expect($feedback->id_user)->toBe($user->id)
        ->and($feedback->selesai)->toBeFalse();
});

test('pengguna biasa hanya melihat form, rekap feedback hanya untuk admin', function () {
    $a = User::factory()->create(['role' => 'pegawai', 'name' => 'Pegawai A']);
    $b = User::factory()->create(['role' => 'pegawai', 'name' => 'Pegawai B']);
    Feedback::create(['id_user' => $a->id, 'kategori' => 'saran', 'judul' => 'Saran dari A', 'isi' => 'isi']);
    Feedback::create(['id_user' => $b->id, 'kategori' => 'kendala', 'judul' => 'Kendala dari B', 'isi' => 'isi']);

    $this->actingAs($a)->get(route('feedback.index'))
        ->assertOk()
        ->assertSee('Kami menghargai')
        ->assertDontSee('Rekap Feedback')
        ->assertDontSee('table-feedback')
        ->assertDontSee('Saran dari A')
        ->assertDontSee('Kendala dari B');

    $admin = User::factory()->create(['role' => 'administrator']);
    $this->actingAs($admin)->get(route('feedback.index'))
        ->assertOk()
        ->assertSee('Rekap Feedback')
        ->assertSee('Saran dari A')
        ->assertSee('Kendala dari B')
        ->assertSee('Pegawai B');
});

test('administrator mengisi tindak lanjut dan menandai selesai', function () {
    $feedback = Feedback::create(['id_user' => User::factory()->create()->id, 'kategori' => 'kendala', 'judul' => 'J', 'isi' => 'I']);
    $admin = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($admin)->patch(route('feedback.tindak-lanjut', $feedback), [
        'tindak_lanjut' => 'Sudah diperbaiki di versi terbaru.',
        'selesai' => '1',
    ])->assertRedirect(route('feedback.index'));

    $feedback->refresh();
    expect($feedback->selesai)->toBeTrue()
        ->and($feedback->tindak_lanjut)->toBe('Sudah diperbaiki di versi terbaru.')
        ->and($feedback->ditindaklanjuti_oleh)->toBe($admin->id)
        ->and($feedback->ditindaklanjuti_pada)->not->toBeNull();
});

test('pengguna biasa tidak bisa mengisi tindak lanjut', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $feedback = Feedback::create(['id_user' => $user->id, 'kategori' => 'kendala', 'judul' => 'J', 'isi' => 'I']);

    $this->actingAs($user)->patch(route('feedback.tindak-lanjut', $feedback), ['selesai' => '1'])->assertForbidden();

    expect($feedback->fresh()->selesai)->toBeFalse();
});

test('uraian feedback wajib diisi', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->post(route('feedback.store'), ['isi' => ''])
        ->assertSessionHasErrors('isi');

    expect(Feedback::count())->toBe(0);
});
