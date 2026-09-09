<?php

use App\Models\Dokumentasi;
use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function buatUserDenganPegawai(): User
{
    $pegawai = PegawaiMitra::create([
        'nama' => 'Pegawai Uji Coba',
        'status_kepegawaian' => 'pegawai',
    ]);

    return User::factory()->create(['id_pegawai_mitra' => $pegawai->id_pegawai_mitra]);
}

test('dokumentasi bisa diunggah saat simpan draft', function () {
    Storage::fake('public');
    $user = buatUserDenganPegawai();

    $this->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('foto.jpg')],
    ])->assertRedirect(route('dashboard'));

    $pd = PerjalananDinas::latest('id_perjalanan_dinas')->first();
    expect($pd->dokumentasi)->toHaveCount(1);
    Storage::disk('public')->assertExists($pd->dokumentasi->first()->path_file);
});

test('dokumentasi yang sudah tersimpan bisa dihapus lewat tombol x', function () {
    Storage::fake('public');
    $user = buatUserDenganPegawai();

    $this->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('foto.jpg')],
    ]);

    $pd = PerjalananDinas::latest('id_perjalanan_dinas')->first();
    $dokumentasi = $pd->dokumentasi->first();
    $path = $dokumentasi->path_file;

    $this->actingAs($user)
        ->delete(route('perjalanan-dinas.dokumentasi.destroy', [$pd, $dokumentasi]))
        ->assertNoContent();

    expect(Dokumentasi::find($dokumentasi->id_dokumentasi))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('dokumentasi milik user lain tidak bisa dihapus', function () {
    Storage::fake('public');
    $pemilik = buatUserDenganPegawai();
    $bukanPemilik = buatUserDenganPegawai();

    $this->actingAs($pemilik)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('foto.jpg')],
    ]);

    $pd = PerjalananDinas::latest('id_perjalanan_dinas')->first();
    $dokumentasi = $pd->dokumentasi->first();

    $this->actingAs($bukanPemilik)
        ->delete(route('perjalanan-dinas.dokumentasi.destroy', [$pd, $dokumentasi]))
        ->assertForbidden();

    expect(Dokumentasi::find($dokumentasi->id_dokumentasi))->not->toBeNull();
});
