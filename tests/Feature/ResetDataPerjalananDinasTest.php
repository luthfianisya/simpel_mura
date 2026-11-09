<?php

use App\Models\Dokumentasi;
use App\Models\Gup;
use App\Models\MonitoringSpj;
use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function siapkanDataUntukReset(): array
{
    Storage::fake('public');
    [$user, $spj] = siapkanPpkDanSpj(2);
    test()->actingAs($user)->post(route('gup.store'), ['ids' => idSpj($spj), 'tanggal_gup' => '2026-02-18']);
    test()->actingAs($user)->post(route('monitoring-spj.rincian.store'), rincianBaru());

    Storage::disk('public')->put('dokumentasi/foto.jpg', 'isi');
    Dokumentasi::create([
        'id_perjalanan_dinas' => $spj[0]->id_perjalanan_dinas,
        'nama_file' => 'foto.jpg',
        'path_file' => 'dokumentasi/foto.jpg',
    ]);
    Storage::disk('public')->put('dokumen-pendukung/sk.pdf', 'sk');

    return [$user, $spj];
}

test('reset menghapus data perjalanan dinas, monitoring, gup, dan foto tapi pegawai & user tetap', function () {
    siapkanDataUntukReset();
    $jumlahPegawai = PegawaiMitra::count();
    $jumlahUser = User::count();
    expect(PerjalananDinas::count())->toBe(2)
        ->and(Gup::count())->toBe(1)
        ->and(MonitoringSpj::rincianNonPerjadin()->count())->toBe(1);

    $this->artisan('simpel:reset-perjalanan-dinas', ['--force' => true])->assertSuccessful();

    expect(PerjalananDinas::count())->toBe(0)
        ->and(MonitoringSpj::count())->toBe(0)
        ->and(Gup::count())->toBe(0)
        ->and(Dokumentasi::count())->toBe(0)
        ->and(PegawaiMitra::count())->toBe($jumlahPegawai)
        ->and(User::count())->toBe($jumlahUser);
    Storage::disk('public')->assertMissing('dokumentasi/foto.jpg');
    Storage::disk('public')->assertExists('dokumen-pendukung/sk.pdf');
});

test('reset dibatalkan kalau konfirmasi bukan RESET', function () {
    siapkanDataUntukReset();

    $this->artisan('simpel:reset-perjalanan-dinas')
        ->expectsQuestion('Ketik RESET untuk melanjutkan', 'ya')
        ->expectsOutput('Dibatalkan. Tidak ada data yang dihapus.')
        ->assertFailed();

    expect(PerjalananDinas::count())->toBe(2)->and(Gup::count())->toBe(1);
    Storage::disk('public')->assertExists('dokumentasi/foto.jpg');
});

test('reset jalan dengan konfirmasi RESET', function () {
    siapkanDataUntukReset();

    $this->artisan('simpel:reset-perjalanan-dinas')
        ->expectsQuestion('Ketik RESET untuk melanjutkan', 'RESET')
        ->assertSuccessful();

    expect(PerjalananDinas::count())->toBe(0);
});
