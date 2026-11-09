<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BacaImportExcel;
use App\Models\Gup;
use App\Models\MonitoringSpj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Import Excel Rincian Non-Perjadin di Monitoring SPJ (hanya PPK / PPSPM / Bendahara).
 * Satu baris = satu rincian. Baris contoh (Uraian diawali "[CONTOH]") dilewati, baris yang
 * gagal validasi dilaporkan dan baris lainnya tetap diproses — sama dengan import
 * perjalanan dinas.
 */
class RincianNonPerjadinImportController extends Controller
{
    use BacaImportExcel;

    // field => judul kolom di template. Urutannya = urutan kolom.
    private const KOLOM = [
        'uraian_rincian' => 'Uraian (wajib)',
        'tanggal_rincian' => 'Tanggal (YYYY-MM-DD)',
        'nominal_rincian' => 'Nominal Rp (wajib)',
        'penerima_rincian' => 'Nama Penerima',
        'pembebanan_rincian' => 'Pembebanan',
        'nomor_fp' => 'Nomor FP',
        'tanggal_gup' => 'Tanggal GUP (YYYY-MM-DD, isi = masuk GUP)',
        'jenis_pembayaran' => 'Jenis Pembayaran',
        'posisi_spj' => 'Posisi SPJ',
        'no_spp_spm' => 'Nomor SPP/SPM',
        'tanggal_spp' => 'Tanggal SPP (YYYY-MM-DD)',
        'tanggal_spm' => 'Tanggal SPM (YYYY-MM-DD)',
        'no_sp2d' => 'Nomor SP2D',
        'tanggal_sp2d' => 'Tanggal SP2D (YYYY-MM-DD)',
        'tanggal_diperiksa_ppk' => 'Tanggal Diperiksa PPK (YYYY-MM-DD)',
        'sudah_dibayar' => 'Sudah Dibayar (Ya/Tidak)',
        'cara_bayar' => 'Tunai/Transfer',
        'sudah_spby' => 'SPBy Diterbitkan (Ya/Tidak)',
        'sudah_kuitansi' => 'Kuitansi Dibuat (Ya/Tidak)',
        'sudah_drpp' => 'DRPP Dibuat (Ya/Tidak)',
        'keterangan' => 'Keterangan',
    ];

    private const KOLOM_TANGGAL = ['tanggal_rincian', 'tanggal_gup', 'tanggal_spp', 'tanggal_spm', 'tanggal_sp2d', 'tanggal_diperiksa_ppk'];
    private const KOLOM_YA_TIDAK = ['sudah_dibayar', 'sudah_spby', 'sudah_kuitansi', 'sudah_drpp'];
    private const KOLOM_TEKS = ['nomor_fp', 'no_spp_spm', 'no_sp2d', 'pembebanan_rincian'];

    public function template(Request $request)
    {
        $this->cekAkses($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rincian Non-Perjadin');
        $kol = fn (string $field) => self::kolom($field);
        $lastCol = $kol(array_key_last(self::KOLOM));

        $sheet->fromArray(array_values(self::KOLOM), null, 'A1');
        foreach (array_keys(self::KOLOM) as $field) {
            $sheet->getColumnDimension($kol($field))->setWidth($field === 'uraian_rincian' ? 40 : 20);
        }
        $header = $sheet->getStyle('A1:' . $lastCol . '1');
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1B449C');
        $header->getAlignment()->setWrapText(true)->setVertical('center');
        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->freezePane('A3');

        // Tanggal & nomor diformat teks supaya tidak berubah jadi serial number/notasi ilmiah.
        foreach (array_merge(self::KOLOM_TANGGAL, self::KOLOM_TEKS) as $field) {
            $sheet->getStyle($kol($field) . '2:' . $kol($field) . '500')->getNumberFormat()->setFormatCode('@');
        }

        $contoh = [
            'uraian_rincian' => self::PENANDA_CONTOH . ' Belanja ATK kegiatan Susenas',
            'tanggal_rincian' => '2026-02-10',
            'nominal_rincian' => 250000,
            'penerima_rincian' => 'Toko Sumber Rejeki',
            'pembebanan_rincian' => '2899.BMA.006.005.A.521211',
            'nomor_fp' => 'FP-050/2026',
            'tanggal_gup' => '2026-02-18',
            'jenis_pembayaran' => 'GUP',
            'posisi_spj' => 'Bendahara',
            'sudah_dibayar' => 'Ya',
            'cara_bayar' => 'Transfer',
            'sudah_spby' => 'Ya',
            'sudah_kuitansi' => 'Ya',
            'sudah_drpp' => 'Tidak',
        ];
        foreach ($contoh as $field => $nilai) {
            $sheet->setCellValueExplicit($kol($field) . '2', $nilai, is_int($nilai) ? 'n' : 's');
        }
        $gayaContoh = $sheet->getStyle('A2:' . $lastCol . '2');
        $gayaContoh->getFont()->setItalic(true)->getColor()->setRGB('8A6D00');
        $gayaContoh->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF4CC');
        $sheet->getComment('A2')->setAuthor('SIMPEL')->setWidth('260pt')->setHeight('70pt')
            ->getText()->createTextRun('Baris CONTOH: tidak ikut diimport selama Uraian diawali "' . self::PENANDA_CONTOH
                . '". Mulai isi data Anda di baris 3.');

        $dropdown = function (string $field, array $pilihan, string $judul) use ($sheet, $kol) {
            for ($baris = 2; $baris <= 300; $baris++) {
                $v = $sheet->getCell($kol($field) . $baris)->getDataValidation();
                $v->setType(DataValidation::TYPE_LIST);
                $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $v->setAllowBlank(true);
                $v->setShowInputMessage(true);
                $v->setShowErrorMessage(true);
                $v->setShowDropDown(true);
                $v->setPromptTitle($judul);
                $v->setFormula1('"' . implode(',', $pilihan) . '"');
            }
        };
        $dropdown('jenis_pembayaran', MonitoringSpj::JENIS_PEMBAYARAN, 'Jenis Pembayaran');
        $dropdown('posisi_spj', MonitoringSpj::POSISI_SPJ, 'Posisi SPJ');
        $dropdown('cara_bayar', array_values(MonitoringSpj::CARA_BAYAR), 'Tunai/Transfer');
        foreach (self::KOLOM_YA_TIDAK as $field) {
            $dropdown($field, ['Ya', 'Tidak'], self::KOLOM[$field]);
        }

        $petunjuk = $spreadsheet->createSheet();
        $petunjuk->setTitle('Petunjuk');
        $petunjuk->fromArray(array_map(fn ($t) => [$t], [
            'Petunjuk Pengisian Template Import Rincian Non-Perjadin',
            '',
            '1. Isi data di sheet "Rincian Non-Perjadin", mulai dari baris 3. Satu baris = satu rincian SPJ di luar perjalanan dinas.',
            '2. Baris 2 (kuning) adalah CONTOH. Baris yang Uraian-nya diawali "' . self::PENANDA_CONTOH . '" tidak ikut diimport, jadi boleh dibiarkan.',
            '3. Uraian dan Nominal wajib diisi. Nominal boleh ditulis 250000, 250.000, atau Rp 250.000.',
            '4. Tanggal diketik dengan format YYYY-MM-DD, contoh 2026-02-10 (DD/MM/YYYY juga diterima).',
            '5. Isi Tanggal GUP untuk langsung memasukkan rincian ke GUP bertanggal itu (GUP dibuat otomatis bila belum ada).',
            '   Jenis Pembayaran otomatis "GUP". Kosongkan Tanggal GUP bila belum dikelompokkan.',
            '6. Tunai/Transfer hanya boleh diisi bila Sudah Dibayar = Ya. Posisi "Dikembalikan" selalu dianggap belum dibayar.',
            '7. Rincian yang terimport bisa diubah, dihapus, atau dikelompokkan ulang di halaman Monitoring SPJ.',
        ]), null, 'A1');
        $petunjuk->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $petunjuk->getColumnDimension('A')->setWidth(130);

        $spreadsheet->setActiveSheetIndex(0);
        $sheet->setSelectedCell('A3');
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), 'template_rincian_non_perjadin.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $this->cekAkses($request);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $rows = IOFactory::load($request->file('file')->getRealPath())->getSheet(0)->toArray(null, true, false, false);
        $fields = array_keys(self::KOLOM);
        $gupPerTanggal = [];
        $berhasil = 0;
        $gagal = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue; // header
            }
            $nomorBaris = $i + 1;
            $data = array_combine($fields, array_pad(array_slice($row, 0, count($fields)), count($fields), null));
            $data = array_map(fn ($v) => $v !== null ? trim((string) $v) : '', $data);

            if ($data['uraian_rincian'] === '' && $data['nominal_rincian'] === '') {
                continue; // baris kosong
            }
            if ($this->diawaliPenandaContoh($data['uraian_rincian'])) {
                continue; // baris contoh
            }

            [$isian, $galat] = $this->olahBaris($data);
            if ($galat === null) {
                $validator = Validator::make($isian, $this->aturan(), $this->pesan(), self::KOLOM);
                $galat = $validator->fails() ? $validator->errors()->first() : null;
            }
            if ($galat !== null) {
                $gagal[] = "Baris {$nomorBaris}: {$galat}";
                continue;
            }

            DB::transaction(function () use (&$isian, &$gupPerTanggal, $request) {
                if ($isian['tanggal_gup']) {
                    $gup = $gupPerTanggal[$isian['tanggal_gup']] ??= Gup::whereDate('tanggal_gup', $isian['tanggal_gup'])->orderBy('id_gup')->first()
                        ?? Gup::create(['tanggal_gup' => $isian['tanggal_gup'], 'dibuat_oleh' => $request->user()->id]);
                    $isian['id_gup'] = $gup->id_gup;
                }
                MonitoringSpj::create($isian + ['diubah_oleh' => $request->user()->id]);
            });
            $berhasil++;
        }

        if ($berhasil === 0 && !$gagal) {
            return back()->with('error', 'Tidak ada data yang diimport. Isi data mulai baris 3 — baris contoh (Uraian diawali '
                . self::PENANDA_CONTOH . ') tidak ikut diimport.');
        }

        $pesan = "Import selesai: {$berhasil} rincian non-perjadin berhasil ditambahkan.";
        if ($gagal) {
            $pesan .= ' ' . count($gagal) . ' baris gagal: ' . implode(' | ', array_slice($gagal, 0, 10))
                . (count($gagal) > 10 ? ' (dan ' . (count($gagal) - 10) . ' lainnya)' : '');

            return back()->with('error', $pesan);
        }

        return back()->with('success', $pesan);
    }

    /**
     * Ubah teks sel jadi isian monitoring_spj. Pilihan (jenis, posisi, tunai/transfer,
     * Ya/Tidak) dicocokkan tanpa peduli huruf besar/kecil. Mengembalikan [isian, pesanGalat].
     */
    private function olahBaris(array $data): array
    {
        foreach (self::KOLOM_TANGGAL as $field) {
            $data[$field] = $this->normalisasiTanggalImport($data[$field]);
        }

        $cocokkan = function (string $nilai, array $pilihan) {
            foreach ($pilihan as $kunci => $label) {
                if (strcasecmp($nilai, $label) === 0 || (is_string($kunci) && strcasecmp($nilai, $kunci) === 0)) {
                    return is_string($kunci) ? $kunci : $label;
                }
            }

            return $nilai; // tidak cocok → biar validasi Rule::in yang melaporkan
        };

        $isian = [
            'uraian_rincian' => $data['uraian_rincian'],
            'tanggal_rincian' => $data['tanggal_rincian'] ?: null,
            'nominal_rincian' => $this->angkaImport($data['nominal_rincian']),
            'penerima_rincian' => $data['penerima_rincian'] ?: null,
            'pembebanan_rincian' => $data['pembebanan_rincian'] ?: null,
            'nomor_fp' => $data['nomor_fp'] ?: null,
            'tanggal_gup' => $data['tanggal_gup'] ?: null,
            'jenis_pembayaran' => $data['jenis_pembayaran'] !== '' ? $cocokkan($data['jenis_pembayaran'], MonitoringSpj::JENIS_PEMBAYARAN) : null,
            'posisi_spj' => $data['posisi_spj'] !== '' ? $cocokkan($data['posisi_spj'], MonitoringSpj::POSISI_SPJ) : null,
            'no_spp_spm' => $data['no_spp_spm'] ?: null,
            'tanggal_spp' => $data['tanggal_spp'] ?: null,
            'tanggal_spm' => $data['tanggal_spm'] ?: null,
            'no_sp2d' => $data['no_sp2d'] ?: null,
            'tanggal_sp2d' => $data['tanggal_sp2d'] ?: null,
            'tanggal_diperiksa_ppk' => $data['tanggal_diperiksa_ppk'] ?: null,
            'cara_bayar' => $data['cara_bayar'] !== '' ? $cocokkan($data['cara_bayar'], MonitoringSpj::CARA_BAYAR) : null,
            'keterangan' => $data['keterangan'] ?: null,
        ];

        foreach (self::KOLOM_YA_TIDAK as $field) {
            $nilai = strtolower($data[$field]);
            if (!in_array($nilai, ['', 'ya', 'tidak'], true)) {
                return [$isian, 'kolom "' . self::KOLOM[$field] . '" harus diisi Ya atau Tidak.'];
            }
            $isian[$field] = $nilai === 'ya';
        }

        // Tanggal GUP diisi = masuk GUP, jadi jenis pembayarannya pasti GUP.
        if ($isian['tanggal_gup']) {
            if ($isian['jenis_pembayaran'] && $isian['jenis_pembayaran'] !== 'GUP') {
                return [$isian, 'Tanggal GUP diisi, tetapi Jenis Pembayaran "' . $isian['jenis_pembayaran'] . '" (harus GUP atau dikosongkan).'];
            }
            $isian['jenis_pembayaran'] = 'GUP';
        }
        if ($isian['cara_bayar'] && !$isian['sudah_dibayar']) {
            return [$isian, 'kolom "Tunai/Transfer" hanya boleh diisi jika Sudah Dibayar = Ya.'];
        }
        if ($isian['posisi_spj'] === MonitoringSpj::POSISI_DIKEMBALIKAN) {
            $isian['sudah_dibayar'] = false;
            $isian['cara_bayar'] = null;
        }

        return [$isian, null];
    }

    private function aturan(): array
    {
        return [
            'uraian_rincian' => ['required', 'string', 'max:2000'],
            'tanggal_rincian' => ['nullable', 'date'],
            'nominal_rincian' => ['required', 'numeric', 'min:0'],
            'penerima_rincian' => ['nullable', 'string', 'max:255'],
            'pembebanan_rincian' => ['nullable', 'string', 'max:255'],
            'nomor_fp' => ['nullable', 'string', 'max:255'],
            'tanggal_gup' => ['nullable', 'date'],
            'jenis_pembayaran' => ['nullable', Rule::in(MonitoringSpj::JENIS_PEMBAYARAN)],
            'posisi_spj' => ['nullable', Rule::in(MonitoringSpj::POSISI_SPJ)],
            'no_spp_spm' => ['nullable', 'string', 'max:255'],
            'tanggal_spp' => ['nullable', 'date'],
            'tanggal_spm' => ['nullable', 'date'],
            'no_sp2d' => ['nullable', 'string', 'max:255'],
            'tanggal_sp2d' => ['nullable', 'date'],
            'tanggal_diperiksa_ppk' => ['nullable', 'date'],
            'cara_bayar' => ['nullable', Rule::in(array_keys(MonitoringSpj::CARA_BAYAR))],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function pesan(): array
    {
        return [
            'required' => 'kolom ":attribute" wajib diisi.',
            'date' => 'kolom ":attribute" harus tanggal dengan format YYYY-MM-DD.',
            'numeric' => 'kolom ":attribute" harus berupa angka.',
            'min' => 'kolom ":attribute" tidak boleh negatif.',
            'max' => 'kolom ":attribute" terlalu panjang (maks. :max karakter).',
            'in' => 'kolom ":attribute" tidak valid (pilih dari dropdown).',
            'string' => 'kolom ":attribute" harus berupa teks.',
        ];
    }

    /** Huruf kolom Excel untuk sebuah field (mis. 'nominal_rincian' → 'C'). */
    private static function kolom(string $field): string
    {
        return Coordinate::stringFromColumnIndex(array_search($field, array_keys(self::KOLOM), true) + 1);
    }

    private function cekAkses(Request $request): void
    {
        abort_unless($request->user()->bisaIsiMonitoringSpj(), 403, 'Rincian non-perjadin hanya bisa diimport oleh PPK / PPSPM / Bendahara.');
    }
}
