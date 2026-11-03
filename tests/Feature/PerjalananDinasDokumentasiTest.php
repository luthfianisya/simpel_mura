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

test('urutan dokumentasi mengikuti hasil drag saat simpan dan saat edit', function () {
    Storage::fake('public');
    $user = buatUserDenganPegawai();

    $this->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        'dokumentasi_urutan' => ['baru:1', 'baru:0'],
    ]);

    $pd = PerjalananDinas::latest('id_perjalanan_dinas')->first();
    expect($pd->dokumentasi->pluck('nama_file')->all())->toBe(['b.jpg', 'a.jpg']);

    // Edit: foto baru "c" ditaruh paling depan, di antara foto lama yang sudah tersimpan.
    [$b, $a] = $pd->dokumentasi->all();
    $this->actingAs($user)->put(route('perjalanan-dinas.update', $pd), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('c.jpg')],
        'dokumentasi_urutan' => ['baru:0', 'lama:' . $a->id_dokumentasi, 'lama:' . $b->id_dokumentasi],
    ]);

    expect($pd->fresh()->dokumentasi->pluck('nama_file')->all())->toBe(['c.jpg', 'a.jpg', 'b.jpg']);
});

test('tanpa urutan dari wizard, foto baru ditaruh di belakang foto lama', function () {
    Storage::fake('public');
    $user = buatUserDenganPegawai();

    $this->actingAs($user)->post(route('perjalanan-dinas.store'), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('a.jpg')],
    ]);
    $pd = PerjalananDinas::latest('id_perjalanan_dinas')->first();

    $this->actingAs($user)->put(route('perjalanan-dinas.update', $pd), [
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'draft_sesi1',
        'dokumentasi' => [UploadedFile::fake()->image('b.jpg')],
    ]);

    expect($pd->fresh()->dokumentasi->pluck('nama_file')->all())->toBe(['a.jpg', 'b.jpg']);
});
