<?php

it('menampilkan portal aplikasi di halaman utama', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Portal Aplikasi')
        ->assertSee(json_encode(url('simpel')), false);
});

it('mengarahkan /simpel ke halaman login simpel', function () {
    expect(route('login'))->toEndWith('/simpel/login');

    $this->get('/simpel')->assertRedirect(route('login'));
});
