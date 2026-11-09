<?php

use App\Models\Gup;
use App\Models\MonitoringSpj;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function userKeuangan(): User
{
    return User::factory()->create(['id_pegawai_mitra' => buatPegawai('Bendahara Uji', 'bendahara')->id_pegawai_mitra]);
}

function templateRincian($test, User $user): Spreadsheet
{
    $isi = $test->actingAs($user)->get(route('monitoring-spj.rincian.import-template'))->assertOk()->streamedContent();
    $path = tempnam(sys_get_temp_dir(), 'rin') . '.xlsx';
    file_put_contents($path, $isi);

    return IOFactory::load($path);
}

function unggahRincian($test, User $user, Spreadsheet $spreadsheet)
{
    $path = tempnam(sys_get_temp_dir(), 'rup') . '.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return $test->actingAs($user)->post(route('monitoring-spj.rincian.import'), [
        'file' => new UploadedFile($path, 'rincian.xlsx', null, null, true),
    ]);
}

// Kolom: A Uraian, B Tanggal, C Nominal, D Penerima, E Pembebanan, F Nomor FP, G Tanggal GUP,
// H Jenis, I Posisi, ... P Sudah Dibayar, Q Tunai/Transfer, U Keterangan.
function isiRincian(Spreadsheet $s, int $baris, array $sel): void
{
    foreach ($sel as $kolom => $nilai) {
        $s->getSheet(0)->setCellValueExplicit($kolom . $baris, $nilai, is_int($nilai) ? 'n' : 's');
    }
}

test('template rincian punya baris contoh bertanda dan dropdown pilihan', function () {
    $sheet = templateRincian($this, userKeuangan())->getSheet(0);

    expect($sheet->getCell('A1')->getValue())->toBe('Uraian (wajib)')
        ->and($sheet->getCell('A2')->getValue())->toStartWith('[CONTOH]')
        ->and($sheet->getCell('H3')->getDataValidation()->getFormula1())->toContain('LS KPPN')
        ->and($sheet->getCell('I3')->getDataValidation()->getFormula1())->toContain('Dikembalikan');
});

test('import rincian: contoh dilewati, tanggal gup mengelompokkan ke gup, format rupiah & tanggal dibaca', function () {
    $user = userKeuangan();
    $gupLama = Gup::create(['tanggal_gup' => '2026-02-18']);
    $s = templateRincian($this, $user);
    isiRincian($s, 3, ['A' => 'Belanja ATK', 'B' => '10/02/2026', 'C' => 'Rp 250.000', 'D' => 'Toko A', 'G' => '2026-02-18', 'I' => 'bendahara', 'P' => 'Ya', 'Q' => 'transfer']);
    isiRincian($s, 4, ['A' => 'Honor narasumber', 'C' => 900000, 'G' => '2026-03-04']);
    isiRincian($s, 5, ['A' => 'Sewa tenda', 'C' => 400000, 'H' => 'LS KPPN']);

    unggahRincian($this, $user, $s)->assertSessionHas('success', 'Import selesai: 3 rincian non-perjadin berhasil ditambahkan.');

    $atk = MonitoringSpj::rincianNonPerjadin()->where('uraian_rincian', 'Belanja ATK')->sole();
    expect($atk->id_gup)->toBe($gupLama->id_gup)
        ->and($atk->jenis_pembayaran)->toBe('GUP')
        ->and($atk->tanggal_rincian->format('Y-m-d'))->toBe('2026-02-10')
        ->and((float) $atk->nominal_rincian)->toBe(250000.0)
        ->and($atk->posisi_spj)->toBe('Bendahara')
        ->and($atk->sudah_dibayar)->toBeTrue()
        ->and($atk->cara_bayar)->toBe('transfer');

    // Tanggal GUP yang belum ada → GUP baru dibuat.
    $honor = MonitoringSpj::rincianNonPerjadin()->where('uraian_rincian', 'Honor narasumber')->sole();
    expect($honor->gup->tanggal_gup->format('Y-m-d'))->toBe('2026-03-04')
        ->and(Gup::count())->toBe(2)
        ->and(MonitoringSpj::rincianNonPerjadin()->where('uraian_rincian', 'Sewa tenda')->sole()->id_gup)->toBeNull()
        ->and(MonitoringSpj::rincianNonPerjadin()->where('uraian_rincian', 'like', '[CONTOH]%')->count())->toBe(0);
});

test('import rincian melaporkan baris salah dan tetap memproses baris lain', function () {
    $user = userKeuangan();
    $s = templateRincian($this, $user);
    isiRincian($s, 3, ['A' => 'Valid', 'C' => 100000]);
    isiRincian($s, 4, ['A' => 'Tanpa nominal']);
    isiRincian($s, 5, ['A' => 'Jenis salah', 'C' => 1000, 'H' => 'Tunai Langsung']);
    isiRincian($s, 6, ['A' => 'GUP tapi LS', 'C' => 1000, 'G' => '2026-02-18', 'H' => 'LS KPPN']);
    isiRincian($s, 7, ['A' => 'Transfer belum bayar', 'C' => 1000, 'Q' => 'Transfer']);
    isiRincian($s, 8, ['A' => 'Ya tidak salah', 'C' => 1000, 'P' => 'Mungkin']);

    unggahRincian($this, $user, $s)->assertSessionHas('error', fn ($p) => str_contains($p, '1 rincian non-perjadin berhasil')
        && str_contains($p, 'Baris 4: kolom "Nominal Rp (wajib)" wajib diisi.')
        && str_contains($p, 'Baris 5: kolom "Jenis Pembayaran" tidak valid')
        && str_contains($p, 'Baris 6: Tanggal GUP diisi')
        && str_contains($p, 'Baris 7: kolom "Tunai/Transfer" hanya boleh diisi')
        && str_contains($p, 'Baris 8: kolom "Sudah Dibayar (Ya/Tidak)" harus diisi Ya atau Tidak.'));

    expect(MonitoringSpj::rincianNonPerjadin()->pluck('uraian_rincian')->all())->toBe(['Valid'])
        ->and(Gup::count())->toBe(0);
});

test('template rincian tanpa isian memberi peringatan', function () {
    $user = userKeuangan();
    unggahRincian($this, $user, templateRincian($this, $user))
        ->assertSessionHas('error', fn ($p) => str_starts_with($p, 'Tidak ada data yang diimport'));
});

test('selain tim keuangan tidak bisa mengunduh template atau mengimport rincian', function () {
    $pegawai = User::factory()->create(['id_pegawai_mitra' => buatPegawai('Pegawai Biasa')->id_pegawai_mitra]);

    $this->actingAs($pegawai)->get(route('monitoring-spj.rincian.import-template'))->assertForbidden();
    $this->actingAs($pegawai)->post(route('monitoring-spj.rincian.import'), [
        'file' => UploadedFile::fake()->create('a.xlsx', 5),
    ])->assertForbidden();
});
