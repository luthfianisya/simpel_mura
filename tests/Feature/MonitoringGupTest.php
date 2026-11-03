<?php

use App\Models\Kuitansi;
use App\Models\MonitoringSpj;
use App\Models\PegawaiMitra;
use App\Models\PerjalananDinas;
use App\Models\RincianBiaya;
use App\Models\User;

function buatSpjGup(string $perihal, int $nominal, ?string $jenisPembayaran, ?string $tanggalGup, bool $dibayar = false): PerjalananDinas
{
    $pelaksana = PegawaiMitra::create(['nama' => 'Pelaksana ' . $perihal, 'status_kepegawaian' => 'pegawai']);
    $pd = PerjalananDinas::create([
        'id_pemohon' => $pelaksana->id_pegawai_mitra,
        'id_pegawai_mitra_pelaksana' => $pelaksana->id_pegawai_mitra,
        'perihal' => $perihal,
        'pembebanan' => '2899.BMA.006.005.A.524113',
        'jenis_perjadin' => 'biasa',
        'status_draft' => 'selesai',
    ]);
    RincianBiaya::create(['id_perjalanan_dinas' => $pd->id_perjalanan_dinas, 'jenis_komponen' => 'uang_harian', 'nominal' => $nominal]);
    Kuitansi::create(['id_perjalanan_dinas' => $pd->id_perjalanan_dinas, 'tanggal_kuitansi' => '2026-01-08']);
    MonitoringSpj::create([
        'id_perjalanan_dinas' => $pd->id_perjalanan_dinas,
        'jenis_pembayaran' => $jenisPembayaran,
        'tanggal_gup' => $tanggalGup,
        'sudah_dibayar' => $dibayar,
    ]);

    return $pd;
}

test('monitoring gup menjumlahkan spj per tanggal gup', function () {
    buatSpjGup('Perjadin A', 100000, 'GUP', '2026-01-21', true);
    buatSpjGup('Perjadin B', 250000, 'GUP', '2026-01-21');
    buatSpjGup('Perjadin C', 70000, 'GUP', '2026-01-28');
    buatSpjGup('Perjadin LS', 999000, 'LS KPPN', null);
    buatSpjGup('Perjadin Menunggu', 40000, 'GUP', null);

    $response = $this->actingAs(User::factory()->create(['role' => 'pegawai']))
        ->get(route('monitoring-gup.index', ['tahun' => 2026]))
        ->assertOk()
        ->assertSeeInOrder(['21 Januari 2026', '2 SPJ', 'Rp 350.000', '1/2 dibayar', '28 Januari 2026', '1 SPJ', 'Rp 70.000'])
        ->assertSee('Rp 420.000')
        ->assertSee('Perjadin Menunggu')
        ->assertDontSee('Perjadin LS')
        ->assertDontSee('Rp 999.000');

    $daftarGup = $response->viewData('daftarGup');
    expect($daftarGup)->toHaveCount(2)
        ->and($daftarGup[0]['nominal'])->toEqual(350000)
        ->and($response->viewData('menunggu'))->toHaveCount(1);
});

test('monitoring gup hanya menampilkan gup tahun yang dipilih', function () {
    buatSpjGup('Perjadin 2025', 50000, 'GUP', '2025-12-05');
    buatSpjGup('Perjadin 2026', 60000, 'GUP', '2026-02-10');

    $response = $this->actingAs(User::factory()->create(['role' => 'pegawai']))
        ->get(route('monitoring-gup.index', ['tahun' => 2025]))
        ->assertOk()
        ->assertSee('5 Desember 2025')
        ->assertDontSee('10 Februari 2026');

    expect($response->viewData('daftarTahun')->all())->toBe([2026, 2025]);
});
