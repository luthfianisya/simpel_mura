<?php

use App\Models\PerjalananDinas;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function unduhTemplateImport($test, $user): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
    $isi = $test->actingAs($user)->get(route('perjalanan-dinas.import-template'))->assertOk()->streamedContent();
    $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
    file_put_contents($path, $isi);

    return IOFactory::load($path);
}

function unggahSpreadsheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('template import tidak punya kolom no spd dan baris contohnya ditandai', function () {
    $sheet = unduhTemplateImport($this, buatUserDenganPegawai())->getSheet(0);
    $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];

    expect(collect($header)->filter(fn ($h) => str_starts_with((string) $h, 'No SPD')))->toBeEmpty()
        ->and($sheet->getCell('E2')->getValue())->toStartWith('[CONTOH]');
});

test('baris contoh tidak ikut diimport dan no spd diberikan otomatis', function () {
    $user = buatUserDenganPegawai();
    $spreadsheet = unduhTemplateImport($this, $user);
    $sheet = $spreadsheet->getSheet(0);

    // Baris 3 = data sungguhan (baris 2 contoh dibiarkan).
    $sheet->fromArray(['Biasa', 'B-0001/62130/KU.340/2026', '2026-08-13', '', 'Pendataan Susenas'], null, 'A3');
    $sheet->setCellValue('R3', '2026-08-13'); // Tanggal SPD
    $sheet->setCellValue('AN3', 'Draft');

    $this->actingAs($user)->post(route('perjalanan-dinas.import'), ['file' => unggahSpreadsheet($spreadsheet)])
        ->assertSessionHas('success', 'Import selesai: 1 perjalanan dinas berhasil dibuat.');

    $pd = PerjalananDinas::sole();
    expect($pd->perihal)->toBe('Pendataan Susenas')
        ->and($pd->no_spd)->not->toBeEmpty();
});

test('template lama dengan kolom no spd tetap terbaca dan contoh lamanya dilewati', function () {
    $user = buatUserDenganPegawai();
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    // Kolom A–Q sama, R = No SPD (lama), lalu kolom berikutnya bergeser satu.
    $sheet->fromArray(['Jenis Perjalanan Dinas', 'No Surat Tugas', 'Tgl ST', 'Jenis Kegiatan', 'Perihal'], null, 'A1');
    $sheet->setCellValue('K2', 'Desa Contoh');
    $sheet->setCellValue('R1', 'No SPD (wajib utk Biasa/>8 Jam)');
    $sheet->fromArray(['Biasa', 'B-1122/62130/KU.340/2026', '2026-08-13', '', 'Pendataan Sakernas Agustus 2026'], null, 'A2');
    $sheet->fromArray(['Biasa', 'B-0002/62130/KU.340/2026', '2026-08-13', '', 'Perjadin Lama'], null, 'A3');
    $sheet->setCellValue('R3', '99/62130/KU.340/2026');
    $sheet->setCellValue('S3', '2026-08-13'); // Tanggal SPD di template lama
    $sheet->setCellValue('AO3', 'Draft');

    $this->actingAs($user)->post(route('perjalanan-dinas.import'), ['file' => unggahSpreadsheet($spreadsheet)])
        ->assertSessionHas('success', 'Import selesai: 1 perjalanan dinas berhasil dibuat.');

    $pd = PerjalananDinas::sole();
    expect($pd->perihal)->toBe('Perjadin Lama')
        ->and($pd->tanggal_spd?->format('Y-m-d'))->toBe('2026-08-13')
        ->and($pd->no_spd)->not->toBe('99/62130/KU.340/2026');
});

test('import tahan terhadap jenis tak dikenal, tanggal dd/mm/yyyy, nominal "Rp 500.000", dan nip berspasi', function () {
    $user = buatUserDenganPegawai();
    \App\Models\PegawaiMitra::create(['nama' => 'Budi', 'nip' => '199001012020121001', 'status_kepegawaian' => 'pegawai']);
    $spreadsheet = unduhTemplateImport($this, $user);
    $sheet = $spreadsheet->getSheet(0);
    // E = Perihal, G = NIP Pelaksana, H/I = Tanggal Mulai/Selesai, U = Uang Harian, AN = Status
    $sheet->fromArray(['Luar Negeri', 'B-1', '2026-08-10', '', 'Jenis salah'], null, 'A3');
    $sheet->fromArray(['Biasa', 'B-2', '2026-08-10', '', 'Data benar', '', '19900101 202012 1 001', '12/08/2026', '14-08-2026'], null, 'A4');
    $sheet->setCellValueExplicit('U4', 'Rp 500.000', 's');
    $sheet->setCellValue('AN3', 'Draft');
    $sheet->setCellValue('AN4', 'Draft');

    $this->actingAs($user)->post(route('perjalanan-dinas.import'), ['file' => unggahSpreadsheet($spreadsheet)])
        ->assertSessionHas('error', fn ($p) => str_contains($p, '1 perjalanan dinas berhasil dibuat') && str_contains($p, '"Luar Negeri" tidak dikenal'));

    $pd = PerjalananDinas::with('rincianBiaya')->sole();
    expect($pd->tanggal_mulai->format('Y-m-d'))->toBe('2026-08-12')
        ->and($pd->tanggal_selesai->format('Y-m-d'))->toBe('2026-08-14')
        ->and($pd->id_pegawai_mitra_pelaksana)->not->toBeNull()
        ->and((int) $pd->rincianBiaya->firstWhere('jenis_komponen', 'uang_harian')->nominal)->toBe(500000);
});

test('unggah template yang hanya berisi baris contoh memberi peringatan', function () {
    $user = buatUserDenganPegawai();
    $this->actingAs($user)->post(route('perjalanan-dinas.import'), ['file' => unggahSpreadsheet(unduhTemplateImport($this, $user))])
        ->assertSessionHas('error', fn ($p) => str_starts_with($p, 'Tidak ada data yang diimport'));
    expect(PerjalananDinas::count())->toBe(0);
});
