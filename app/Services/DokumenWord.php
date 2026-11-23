<?php

namespace App\Services;

use App\Models\PerjalananDinas;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table as GayaTabel;

/**
 * Dokumen perjalanan dinas dalam format Word (.docx) — disusun lewat kode (PHPWord),
 * mengikuti tampilan PDF/preview di public/assets/js/dokumen-perjadin.js. Masih tahap
 * uji coba (menu "(dev) Semua Perjalanan Dinas", khusus administrator).
 *
 * Konvensi: lebar dalam twip (1 cm = 567 twip), ukuran huruf dalam pt.
 */
class DokumenWord
{
    // Jenis dokumen => label, urutan sama dengan menu Bantuan (Kelengkapan Dokumen).
    public const JENIS = [
        'surat-tugas' => 'Surat Tugas',
        'kuitansi' => 'Kuitansi',
        'rincian' => 'Rincian Biaya',
        'pengeluaran' => 'Pengeluaran Riil',
        'pernyataan' => 'Surat Pernyataan',
        'laporan' => 'Laporan',
        'spd' => 'SPD',
    ];

    private const SATKER = '668650 - Badan Pusat Statistik Kabupaten Murung Raya';

    private const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    private const KONDISI = ['tidak_pakai_kendaraan_dinas', 'tidak_menginap_hotel', 'keterlambatan'];

    private const SK_PENGELUARAN = [
        'transportasi' => 'Sesuai SK Kuasa Pengguna Anggaran BPS Kabupaten Murung Raya Nomor 14 Tahun 2026 Tanggal 7 Januari 2026 Tentang Besaran Biaya Transport Perjalanan Dinas Dalam Kota Tahun Anggaran',
        'akomodasi' => 'Sesuai SK Kuasa Pengguna Anggaran BPS Kabupaten Murung Raya Nomor 13 Tahun 2026 Tanggal 7 Januari 2026 Tentang Besaran Biaya Rate Akomodasi Dalam Kota Tahun Anggaran',
    ];

    private const BOOKMAN = 'Bookman Old Style';

    private PhpWord $word;

    private array $d;

    private PerjalananDinas $pd;

    // Lebar area isi section yang sedang ditulis (twip).
    private int $lebar = 9026;

    private ?Section $sectionAktif = null;

    /** Jenis dokumen yang tersedia untuk perjalanan dinas ini (SPD hanya kalau jenisnya butuh SPD). */
    public static function jenisTersedia(PerjalananDinas $pd): array
    {
        $jenis = self::JENIS;
        if ($pd->jenis_perjadin === 'dalam_kota_kurang_8_jam') {
            unset($jenis['spd']);
        }

        return $jenis;
    }

    /**
     * Buat file .docx untuk satu jenis dokumen, atau 'semua' (semua dokumen jadi 1 file,
     * tiap dokumen di section/halaman sendiri). Mengembalikan path file sementara.
     */
    public function buat(PerjalananDinas $pd, string $jenis): string
    {
        $this->pd = $pd->loadMissing(['pelaksana', 'penandatanganSt', 'ppk', 'bendahara', 'kuitansi', 'laporan', 'rincianBiaya', 'pengeluaranRiil', 'suratPernyataan', 'dokumentasi']);
        $this->d = $pd->toPreviewData();

        $this->word = new PhpWord();
        $this->word->setDefaultFontName('Arial');
        $this->word->setDefaultFontSize(11);
        $this->word->setDefaultParagraphStyle(['spaceBefore' => 0, 'spaceAfter' => 0, 'spacing' => 0, 'lineHeight' => 1.15]);
        $this->word->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('id-ID'));

        $daftar = $jenis === 'semua' ? array_keys(self::jenisTersedia($pd)) : [$jenis];
        foreach ($daftar as $j) {
            match ($j) {
                'surat-tugas' => $this->suratTugas(),
                'spd' => $this->spd(),
                'rincian' => $this->rincian(),
                'pengeluaran' => $this->pengeluaran(),
                'kuitansi' => $this->kuitansi(),
                'pernyataan' => $this->pernyataan(),
                'laporan' => $this->laporan(),
            };
        }
        $this->tutupSection();

        $path = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($this->word, 'Word2007')->save($path);

        return $path;
    }

    // ================================================================== Dokumen

    private function suratTugas(): void
    {
        $d = $this->d;
        // Margin atas-bawah 2 cm (kiri-kanan tetap 2,54 cm) supaya muat 1 lembar dengan huruf 12 pt.
        $s = $this->section('A4', 2.0, 2.54);
        $f = ['name' => self::BOOKMAN, 'size' => 12];
        $rapat = ['lineHeight' => 1.0];
        $tahun = substr((string) $d['tanggal_surat_tugas'], 0, 4) ?: '.....';

        $logo = public_path('assets/img/branding/logo-bps.png');
        if (is_file($logo)) {
            $s->addImage($logo, ['width' => 48, 'height' => $this->tinggiProporsional($logo, 48), 'unit' => 'pt', 'alignment' => Jc::CENTER]);
        }
        $s->addText('BADAN PUSAT STATISTIK', ['bold' => true, 'italic' => true, 'size' => 12], ['alignment' => Jc::CENTER] + $rapat);
        $s->addText('KABUPATEN MURUNG RAYA', ['bold' => true, 'italic' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 160] + $rapat);
        $s->addText('SURAT TUGAS', $f + ['bold' => true], ['alignment' => Jc::CENTER] + $rapat);
        $s->addText('NOMOR ' . ($d['no_surat_tugas'] ?: '...........................'), $f, ['alignment' => Jc::CENTER, 'spaceAfter' => 160] + $rapat);

        $menimbang = [
            'Bahwa dalam rangka sebagai wujud komitmen Badan Pusat Statistik sebagai penyedia data yang berkualitas;',
            'Bahwa untuk memenuhi ketentuan butir a, maka perlu menugaskan pegawai tersebut dalam Surat Tugas ini untuk melakukan kegiatan tersebut;',
        ];
        $mengingat = [
            'Undang-Undang No.16 Tahun 1997 tentang Statistik;',
            'Peraturan Pemerintah No. 51 Tahun 1999 tentang Pedoman Penyelenggaraan Statistik;',
            'Keputusan Presiden RI No. 72 Tahun 2004 tentang Pedoman Pelaksanaan APBN;',
            'Keputusan Presiden Nomor 1 Tahun 2025 tentang Badan Pusat Statistik;',
            'Keputusan Presiden RI No. 103 Tahun 2001 tentang Kedudukan, Tugas, Fungsi, Kewenangan, Susunan Organisasi dan Tata Cara Kerja Pemerintah Non Departemen;',
            'Peraturan Menteri Keuangan No. 2110/PMK.05/2022 tentang Perjalanan Dinas Dalam Negeri Bagi Pejabat Negara, Pegawai Negeri, dan Pegawai Tidak Tetap (Berita Negara Republik Indonesia Tahun 2011 Nomor 678);',
            'Peraturan Kepala Badan Pusat Statistik No. 103 tahun 2014 tentang pelaksanaan Perjalanan dinas jabatan di lingkungan Badan Pusat Statistik;',
            "Semua biaya yang timbul dengan diterbitkannya Surat Tugas ini dibebankan kepada DIPA BPS Kabupaten Murung Raya TA {$tahun}.",
        ];

        $t = $this->tabel($s);
        $w = $this->kolom([21, 2, 77]);
        foreach ([['Menimbang', $menimbang, 'a'], ['Mengingat', $mengingat, '1']] as [$label, $isi, $gaya]) {
            $t->addRow();
            $t->addCell($w[0])->addText($label, $f, $rapat);
            $t->addCell($w[1])->addText(':', $f, $rapat);
            $c = $t->addCell($w[2]);
            foreach ($isi as $i => $teks) {
                $nomor = $gaya === 'a' ? chr(97 + $i) . '.' : ($i + 1) . '.';
                $c->addText($nomor . "\t" . $teks, $f, $rapat + ['alignment' => Jc::BOTH, 'indentation' => ['left' => 400, 'hanging' => 400], 'spaceAfter' => 20]);
            }
        }

        $s->addText('Memberi Perintah', $f + ['bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 160, 'spaceAfter' => 160]);

        $pelaksana = $d['pelaksana'];
        $t = $this->tabel($s);
        $this->barisLabel($t, $w, 'Kepada', ($pelaksana['nama'] ?: '...........................') . ', ' . ($pelaksana['jabatan'] ?: '-'), $f, false, $rapat);
        $this->barisLabel($t, $w, 'Untuk', 'Perjalanan dinas dalam rangka ' . ($this->hurufAwalKecil($this->perihalTanpaAwalan($d['perihal'])) ?: '...........................')
            . $this->diTujuan() . ', tanggal ' . $this->rentangTanggal($d['tanggal_mulai'], $d['tanggal_selesai']), $f, true, $rapat);
        $this->barisLabel($t, $w, 'Pembebanan', $d['pembebanan'] ?: '...........................', $f, false, $rapat);

        $this->ttdKanan($s, ['Puruk Cahu, ' . $this->tanggal($d['tanggal_surat_tugas']), 'Kepala Badan Pusat Statistik', 'Kabupaten Murung Raya'],
            $d['penandatangan']['nama'] ?? '', null, $f, false, $rapat + ['spaceBefore' => 0]);
    }

    private function spd(): void
    {
        $d = $this->d;
        // F4/Folio, margin atas-bawah 2 cm, kiri-kanan 1,5 cm.
        $s = $this->section('Folio', 2, 1.5);
        $f = ['name' => self::BOOKMAN, 'size' => 11];
        $pelaksana = $d['pelaksana'];
        $ppk = $d['ppk'];
        $lama = $this->lamaHari($d['tanggal_mulai'], $d['tanggal_selesai']);

        // Kop: logo | nama instansi | Nomor/Lembar.
        $kop = $this->tabel($s);
        $kop->addRow();
        $logo = public_path('assets/img/branding/logo-bps.png');
        $c = $kop->addCell(1000, ['valign' => 'center']);
        if (is_file($logo)) {
            $c->addImage($logo, ['width' => 45, 'height' => $this->tinggiProporsional($logo, 45), 'unit' => 'pt']);
        }
        $c = $kop->addCell($this->lebar - 1000 - 4800, ['valign' => 'center']);
        $c->addText('BADAN PUSAT STATISTIK', ['bold' => true, 'italic' => true, 'size' => 12]);
        $c->addText('KABUPATEN MURUNG RAYA', ['bold' => true, 'italic' => true, 'size' => 12]);
        $c = $kop->addCell(4800);
        $c->addText('Nomor  : ' . ($d['no_spd'] ?: '...........................'), $f + ['size' => 9.5]);
        $c->addText('Lembar : 1', $f + ['size' => 9.5]);
        $this->spasi($s, 1);

        $w = $this->kolom([5, 40, 16, 39]);
        $t = $this->tabel($s);
        $penuh = 'TBLR';
        $t->addRow();
        $this->sel($t, $this->lebar, $penuh, ['gridSpan' => 4])->addText('SURAT PERJALANAN DINAS', ['name' => self::BOOKMAN, 'size' => 18], ['alignment' => Jc::CENTER]);

        // Baris bernomor: [nomor, [[label, nilai], ...]] — sub-baris tanpa garis di antaranya.
        $baris = [
            ['1', [['Pejabat Pembuat Komitmen', $ppk['nama'] ?: '...']]],
            ['2', [['Nama/NIP Pegawai yang melaksanakan perjalanan dinas', ($pelaksana['nama'] ?: '...') . " /\nNIP. " . $this->nip($pelaksana['nip'])]]],
            ['3', [
                ['a. Pangkat/Golongan', $this->pangkatGol($pelaksana)],
                ['b. Jabatan', $pelaksana['jabatan'] ?: '-'],
                ['c. Tingkat Biaya Perjalanan Dinas', $d['tingkat_biaya'] ?: '-'],
            ]],
            ['4', [['Maksud Perjalanan Dinas', $d['perihal'] ?: '...']]],
            ['5', [['Alat angkutan yang dipergunakan', $d['angkutan'] ?: '...']]],
            ['6', [['a. Tempat Berangkat', $this->wilayahAsal() ?: '...'], ['b. Tempat Tujuan', $this->wilayahTujuan() ?: '...']]],
            ['7', [
                ['a. Lamanya Perjalanan Dinas', $this->terbilangHari($lama)],
                ['b. Tanggal Berangkat', $this->tanggal($d['tanggal_mulai'])],
                ['c. Tanggal harus kembali', $this->tanggal($d['tanggal_selesai'])],
            ]],
        ];
        foreach ($baris as [$no, $sub]) {
            $this->barisSpd($t, $w, $no, $sub, $f);
        }

        // 8. Pengikut
        $t->addRow();
        $this->sel($t, $w[0], 'TLR', ['vMerge' => 'restart'])->addText('8', $f);
        $this->sel($t, $w[1], $penuh)->addText('Pengikut : Nama', $f);
        $this->sel($t, $w[2], $penuh)->addText('Tanggal Lahir', $f, ['alignment' => Jc::CENTER]);
        $this->sel($t, $w[3], $penuh)->addText('Keterangan', $f, ['alignment' => Jc::CENTER]);
        $t->addRow();
        $this->sel($t, $w[0], 'BLR', ['vMerge' => 'continue']);
        $this->teksBaris($this->sel($t, $w[1], $penuh), "1. -\n2. -", $f);
        $this->teksBaris($this->sel($t, $w[2], $penuh), "-\n-", $f);
        $this->teksBaris($this->sel($t, $w[3], $penuh), "-\n-", $f);

        $this->barisSpd($t, $w, '9', [
            ['Pembebanan anggaran :', ''],
            ['a. Instansi', 'Badan Pusat Statistik Kabupaten Murung Raya'],
            ['b. Program/Kegiatan/Output/MAK', $d['pembebanan'] ?: '...'],
        ], $f);
        $this->barisSpd($t, $w, '10', [['Keterangan Lain-Lain', 'Surat Tugas Nomor ' . ($d['no_surat_tugas'] ?: '...') . "\ntanggal " . $this->tanggal($d['tanggal_surat_tugas'])]], $f);

        $this->spasi($s, 1);
        $this->ttdKanan($s, ["Dikeluarkan di : Puruk Cahu\nPada Tanggal   : " . $this->tanggal($d['tanggal_spd']), '', 'Pejabat Pembuat Komitmen,', ''],
            $ppk['nama'] ?? '', $ppk['nip'] ?? '', $f, true);
    }

    private function rincian(): void
    {
        $d = $this->d;
        // Margin "Narrow" 1,27 cm, huruf 9,5 pt — supaya muat 1 lembar.
        $s = $this->section('A4', 1.27, 1.27);
        $f = ['size' => 9.5];
        $p = ['spaceBefore' => 30, 'spaceAfter' => 30];
        $pelaksana = $d['pelaksana'];
        $k = $this->komponenRincian();
        $pengeluaran = $this->jumlahPengeluaran();
        $total = $k['uangHarian'] + $k['transport'] + $k['penginapan'] + $pengeluaran;
        $ref = $this->spdAtauSt();

        $s->addText('PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS', ['size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);
        $t = $this->tabel($s);
        $wl = $this->kolom([28, 72]);
        foreach ([["Lampiran {$ref['label']} Nomor", ': ' . $ref['nomor']], ['Tanggal', ': ' . $this->tanggal($ref['tanggal'])]] as [$a, $b]) {
            $t->addRow();
            $t->addCell($wl[0])->addText($a, $f);
            $t->addCell($wl[1])->addText($b, $f);
        }
        $this->spasi($s, 1, 6);

        // No | label | : | nilai | Jumlah | Keterangan
        $w = $this->kolom([4, 27, 2, 36, 18, 13]);
        $t = $this->tabel($s);
        $t->addRow();
        $this->sel($t, $w[0], 'TBLR')->addText('No', $f + ['bold' => true], ['alignment' => Jc::CENTER] + $p);
        $this->sel($t, $w[1] + $w[2] + $w[3], 'TBLR', ['gridSpan' => 3])->addText('Perincian Biaya', $f + ['bold' => true], ['alignment' => Jc::CENTER] + $p);
        $this->sel($t, $w[4], 'TBLR')->addText('Jumlah', $f + ['bold' => true], ['alignment' => Jc::CENTER] + $p);
        $this->sel($t, $w[5], 'TBLR')->addText('Keterangan', $f + ['bold' => true], ['alignment' => Jc::CENTER] + $p);

        $barisIsi = [
            ['1', 'a. Nama yang bertugas', $pelaksana['nama'] ?: '...', null],
            ['', 'b. Pangkat/Gol', $this->pangkatGol($pelaksana), null],
            ['', 'c. Tujuan tugas', $d['kabupaten_tujuan'] ?: '...', null],
            ['', 'd. Lamanya tugas', $this->terbilangHari($k['lama']), null],
            ['2', 'Transport', $this->terbilangHari($k['hariTransport']) . ', dari ' . ($this->wilayahAsal() ?: '...') . ' ke ' . $this->tujuanTransport($this->wilayahTujuan()), $k['transport']],
        ];
        if (! $k['tanpaHarian']) {
            $barisIsi[] = ['3', 'Uang harian perjadin', $this->terbilangHari($k['hariUangHarian']) . ' x ' . $this->rupiah($k['rateHarian']) . ',-', $k['uangHarian']];
            $barisIsi[] = ['4', 'Penginapan', $k['malamPenginapan'] . ' (' . $this->terbilang($k['malamPenginapan']) . ') malam x ' . $this->rupiah($k['rateMalam']) . ',-', $k['penginapan']];
        }
        $barisIsi[] = [$k['tanpaHarian'] ? '3' : '5', 'Pengeluaran Rill', '', $pengeluaran];
        $barisIsi[] = ['', '', 'JUMLAH', null];

        foreach ($barisIsi as $i => [$no, $label, $nilai, $jumlah]) {
            $terakhir = $i === count($barisIsi) - 1;
            $bawah = $terakhir ? 'B' : '';
            $t->addRow();
            $this->sel($t, $w[0], 'LR' . $bawah)->addText($no, $f, $p);
            $this->sel($t, $w[1], 'L' . $bawah)->addText($label, $f, $p);
            $this->sel($t, $w[2], $bawah)->addText($label !== '' ? ':' : '', $f, $p);
            $this->sel($t, $w[3], 'R' . $bawah)->addText($nilai, $f + ($terakhir ? ['bold' => true] : []), $p);
            $teksJumlah = $terakhir ? $this->rupiah($total) : ($jumlah === null ? '' : ($jumlah > 0 ? $this->rupiah($jumlah) : 'Rp. -'));
            $this->sel($t, $w[4], 'LR' . ($terakhir ? 'TB' : ''))->addText($teksJumlah, $f + ($terakhir ? ['bold' => true] : []), ['alignment' => Jc::END] + $p);
            $this->sel($t, $w[5], 'LR' . $bawah, ['vMerge' => $i === 0 ? 'restart' : 'continue']);
        }

        $t->addRow();
        $this->sel($t, $w[0], 'TBLR');
        $this->sel($t, $this->lebar - $w[0], 'TBLR', ['gridSpan' => 5])->addText('** ' . $this->terbilangRupiah($total) . ' **', $f + ['bold' => true, 'italic' => true], ['alignment' => Jc::CENTER] + $p);

        // Bagian tanda tangan masih di dalam bingkai tabel (garis luar kiri-kanan-bawah).
        $kiri = $w[0] + $w[1] + $w[2];
        $kanan = $this->lebar - $kiri;
        $t->addRow();
        $this->sel($t, $kiri, 'L', ['gridSpan' => 3]);
        $this->sel($t, $kanan, 'R', ['gridSpan' => 3])->addText('Puruk Cahu, ' . $this->tanggal($d['tanggal_kuitansi']), $f, ['alignment' => Jc::END, 'spaceBefore' => 60]);
        $t->addRow();
        $this->teksBaris($this->sel($t, $kiri, 'L', ['gridSpan' => 3]), "Telah dibayar sejumlah:\n_" . $this->rupiah($total) . '_', $f);
        $this->teksBaris($this->sel($t, $kanan, 'R', ['gridSpan' => 3]), "Telah menerima jumlah uang sebesar:\n_" . $this->rupiah($total) . '_', $f, ['indentation' => ['left' => (int) ($kanan * 0.4)]]);
        $t->addRow(null, ['cantSplit' => true]);
        $c = $this->sel($t, $kiri, 'L', ['gridSpan' => 3]);
        $this->blokTtd($c, ['Lunas pada tanggal:', '', 'Bendahara Pengeluaran/PUMC', 'BPS Kabupaten Murung Raya'], $d['bendahara']['nama'] ?? '', $d['bendahara']['nip'] ?? '', $f);
        $c = $this->sel($t, $kanan, 'R', ['gridSpan' => 3]);
        $this->blokTtd($c, ['', '', 'Yang Menerima,', ''], $pelaksana['nama'] ?? '', $pelaksana['nip'] ?? '', $f, ['indentation' => ['left' => (int) ($kanan * 0.4)]]);
        $t->addRow();
        $this->sel($t, $this->lebar, 'LR', ['gridSpan' => 6])->addText('PERHITUNGAN SPD RAMPUNG', $f + ['bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 120, 'spaceAfter' => 60]);
        foreach ([['Ditetapkan sejumlah', $this->rupiah($total), false], ['Yang telah dibayar semula', 'Rp. 0,-', false], ['Sisa kurang / lebih', $this->rupiah($total), true]] as [$label, $nilai, $garis]) {
            $t->addRow();
            $this->sel($t, $w[0], 'L');
            $this->sel($t, $w[1] + $w[2], '', ['gridSpan' => 2])->addText($label . "\t:", $f, ['tabs' => [new \PhpOffice\PhpWord\Style\Tab('left', $w[1] + $w[2] - 200)]]);
            $this->sel($t, $w[3], $garis ? 'T' : '')->addText($nilai, $f, ['alignment' => Jc::END]);
            $this->sel($t, $w[4] + $w[5], 'R', ['gridSpan' => 2]);
        }
        $t->addRow(null, ['cantSplit' => true]);
        $this->sel($t, $kiri, 'LB', ['gridSpan' => 3]);
        $c = $this->sel($t, $kanan, 'RB', ['gridSpan' => 3]);
        $this->blokTtd($c, ['', 'Pejabat Pembuat Komitmen', 'BPS Kabupaten Murung Raya'], $d['ppk']['nama'] ?? '', $d['ppk']['nip'] ?? '', $f, ['indentation' => ['left' => (int) ($kanan * 0.4)]]);
        $c->addText('', $f);
    }

    private function pengeluaran(): void
    {
        $d = $this->d;
        $s = $this->sectionA4();
        $pelaksana = $d['pelaksana'];
        $tahun = substr((string) $d['tanggal_mulai'], 0, 4) ?: '.....';

        $this->judul($s, 'DAFTAR PENGELUARAN RIIL');
        $s->addText('Yang bertanda tangan dibawah ini :', [], ['spaceBefore' => 200, 'spaceAfter' => 120]);
        $this->identitas($s, [['Nama', $pelaksana['nama'] ?: '...........................'], ['NIP', $this->nip($pelaksana['nip'])], ['Jabatan', $pelaksana['jabatan'] ?: '-']], 20);
        $dasar = $d['no_spd']
            ? 'SPD Nomor: ' . $d['no_spd'] . ', tanggal ' . $this->tanggal($d['tanggal_spd'])
            : 'Surat Tugas Nomor: ' . ($d['no_surat_tugas'] ?: '...........................') . ', tanggal ' . $this->tanggal($d['tanggal_surat_tugas']);
        $s->addText("Berdasarkan {$dasar}. Dengan ini kami menyatakan dengan sesungguhnya bahwa:", [], ['alignment' => Jc::BOTH, 'spaceBefore' => 120, 'spaceAfter' => 120]);

        $gayaButir = ['alignment' => Jc::BOTH, 'indentation' => ['left' => 360, 'hanging' => 360]];
        $s->addText("1.\tBiaya transport pegawai dan/atau biaya penginapan dibawah ini yang tidak dapat diperoleh bukti-buktinya, meliputi:", [], $gayaButir + ['spaceAfter' => 80]);

        $w = $this->kolom([7, 73, 20], $this->lebar - 360);
        $t = $this->tabel($s, ['indent' => new \PhpOffice\PhpWord\ComplexType\TblWidth(360)]);
        $p = ['spaceBefore' => 40, 'spaceAfter' => 40];
        $t->addRow(null, ['tblHeader' => true]);
        foreach (['No', 'Uraian', 'Jumlah'] as $i => $h) {
            $this->sel($t, $w[$i], 'TBLR')->addText($h, ['bold' => true], ['alignment' => Jc::CENTER] + $p);
        }
        $total = 0;
        foreach ($d['pengeluaran'] as $i => $row) {
            $nominal = (float) $row['nominal'];
            $total += $nominal;
            $t->addRow(null, ['cantSplit' => true]);
            $this->sel($t, $w[0], 'TBLR')->addText((string) ($i + 1), [], ['alignment' => Jc::CENTER] + $p);
            $c = $this->sel($t, $w[1], 'TBLR');
            $this->teksBaris($c, $row['uraian'] ?: '-', [], ['alignment' => Jc::BOTH] + $p);
            $c->addText((self::SK_PENGELUARAN[$row['jenis']] ?? self::SK_PENGELUARAN['transportasi']) . ' ' . $tahun, ['italic' => true], ['alignment' => Jc::BOTH, 'spaceAfter' => 40]);
            $this->sel($t, $w[2], 'TBLR')->addText($this->rupiah($nominal), [], ['alignment' => Jc::END] + $p);
        }
        if (! $d['pengeluaran']) {
            $t->addRow();
            $this->sel($t, $this->lebar - 360, 'TBLR', ['gridSpan' => 3])->addText('Belum ada pengeluaran riil', ['color' => '808080'], ['alignment' => Jc::CENTER] + $p);
        }
        $t->addRow(null, ['cantSplit' => true]);
        $this->sel($t, $w[0] + $w[1], 'TBLR', ['gridSpan' => 2])->addText('Jumlah', ['bold' => true], ['alignment' => Jc::CENTER] + $p);
        $this->sel($t, $w[2], 'TBLR')->addText($this->rupiah($total), ['bold' => true], ['alignment' => Jc::END] + $p);

        $s->addText("2.\tJumlah uang tersebut pada angka 1 di atas benar-benar dikeluarkan untuk pelaksanaan perjalanan dinas dimaksud dan apabila dikemudian hari terdapat kelebihan atas pembayaran, kami bersedia untuk menyetorkan kelebihan tersebut ke Kas Negara.", [], $gayaButir + ['spaceBefore' => 120]);

        $s->addText('Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.', [], ['alignment' => Jc::BOTH, 'spaceBefore' => 200, 'spaceAfter' => 120, 'keepNext' => true]);
        $s->addText('Puruk Cahu, ' . $this->tanggal($d['tanggal_kuitansi']), [], ['alignment' => Jc::END, 'spaceAfter' => 200, 'keepNext' => true]);
        $this->ttdDua($s,
            ['Mengetahui/Menyetujui', 'An. Kuasa Pengguna Anggaran', 'Pejabat Pembuat Komitmen', 'BPS Kabupaten Murung Raya'], $d['ppk'],
            ['', '', 'Pejabat Negara/ Pegawai Negeri', 'Yang melakukan Perjalanan Dinas'], $pelaksana);
    }

    private function kuitansi(): void
    {
        $d = $this->d;
        $s = $this->sectionA4();
        $pelaksana = $d['pelaksana'];
        $k = $this->komponenRincian();
        $total = $k['uangHarian'] + $k['transport'] + $k['penginapan'] + $this->jumlahPengeluaran();
        $tahun = substr((string) $d['tanggal_mulai'], 0, 4) ?: '.....';

        // Kotak kanan atas (45% lebar, rata kanan).
        $lebarKotak = (int) ($this->lebar * 0.64);
        $w = [2000, 220, $lebarKotak - 2220];
        $t = $this->tabel($s, ['alignment' => Jc::END]);
        foreach ([['Tahun Anggaran', $tahun], ['Nomor Bukti', ''], ['Akun', $d['pembebanan'] ?: '...........................'], ['Kepada', $pelaksana['nama'] ?: '...........................'], ['Satker', self::SATKER]] as [$a, $b]) {
            $t->addRow();
            $t->addCell($w[0])->addText($a);
            $t->addCell($w[1])->addText(':');
            $t->addCell($w[2])->addText($b);
        }

        $s->addText('Kuitansi/Bukti Pembayaran', ['bold' => true, 'underline' => 'single', 'size' => 12], ['alignment' => Jc::CENTER, 'spaceBefore' => 200, 'spaceAfter' => 160]);

        $w = $this->kolom([22, 2, 76]);
        $t = $this->tabel($s);
        $this->barisLabel($t, $w, 'Sudah terima dari', 'Pejabat Pembuat Komitmen BPS Kabupaten Murung Raya');
        $t->addRow();
        $t->addCell($w[0])->addText('Jumlah Uang');
        $t->addCell($w[1])->addText(':');
        $t->addCell($w[2])->addText($this->rupiah($total) . ',-', ['bold' => true]);
        $t->addRow();
        $t->addCell($w[0])->addText('Terbilang');
        $t->addCell($w[1])->addText(':');
        $t->addCell($w[2])->addText('** ' . $this->terbilangRupiah($total) . ' **', ['bold' => true, 'italic' => true]);
        $untuk = 'Biaya perjalanan dinas dalam rangka ' . ($this->perihalTanpaAwalan($d['perihal']) ?: '...........................') . $this->diTujuan()
            . ', tanggal ' . $this->rentangTanggal($d['tanggal_mulai'], $d['tanggal_selesai']) . ", sesuai dengan:\n\n"
            . 'Surat Tugas Nomor: ' . ($d['no_surat_tugas'] ?: '...........................') . ' tanggal ' . $this->tanggal($d['tanggal_surat_tugas'])
            . ($d['no_spd'] ? "\nSPD Nomor: " . $d['no_spd'] . ' tanggal ' . $this->tanggal($d['tanggal_spd']) : '');
        $this->barisLabel($t, $w, 'Untuk pembayaran', $untuk, [], true);

        $s->addText('', ['size' => 6]);
        $this->ttdKanan($s, ['Puruk Cahu, ' . $this->tanggal($d['tanggal_kuitansi']), 'Yang menerima'], $pelaksana['nama'] ?? '', $pelaksana['nip'] ?? '');
        $this->garis($s);
        $this->ttdDua($s, ['An. Kuasa Pengguna Anggaran', 'Pejabat Pembuat Komitmen'], $d['ppk'], ['Lunas dibayar,', 'Bendahara Pengeluaran'], $d['bendahara']);
        $this->garis($s);
        $s->addText('Barang/pekerjaan tersebut telah diterima/diselesaikan dengan lengkap dan baik', [], ['spaceAfter' => 200]);
        $t = $this->tabel($s);
        $t->addRow(null, ['cantSplit' => true]);
        $this->blokTtd($t->addCell((int) ($this->lebar / 2)), ['Pejabat yang bertanggungjawab', 'Pejabat Pembuat Komitmen'], $d['ppk']['nama'] ?? '', $d['ppk']['nip'] ?? '');
        $t->addCell((int) ($this->lebar / 2));
    }

    private function pernyataan(): void
    {
        $d = $this->d;
        $pelaksana = $d['pelaksana'];
        $blok = [];
        if ($d['jenis_perjadin'] === 'dalam_kota_lebih_8_jam') {
            $blok[] = ['lebih8jam', null];
        }
        $rows = $d['pernyataan'];
        usort($rows, fn ($a, $b) => array_search($a['jenis_kondisi'], self::KONDISI) <=> array_search($b['jenis_kondisi'], self::KONDISI));
        foreach ($rows as $r) {
            $blok[] = [$r['jenis_kondisi'], $r['keterangan']];
        }
        if (! $blok) {
            $s = $this->sectionA4();
            $this->judul($s, 'SURAT PERNYATAAN');
            $s->addText('Tidak ada surat pernyataan tambahan.', ['color' => '808080'], ['alignment' => Jc::CENTER, 'spaceBefore' => 200]);

            return;
        }

        $noSt = $d['no_surat_tugas'] ?: '...........................';
        $tglSt = $this->tanggal($d['tanggal_surat_tugas']);
        $noSpd = $d['no_spd'] ?: '...........................';
        $tglSpd = $this->tanggal($d['tanggal_spd']);
        $tglTtd = $this->tanggal($d['tanggal_kuitansi']);
        $para = ['alignment' => Jc::BOTH, 'spaceBefore' => 120, 'spaceAfter' => 120];

        foreach ($blok as [$kondisi, $keterangan]) {
            $s = $this->sectionA4();
            $judul = [
                'lebih8jam' => 'PERJALANAN DINAS DALAM KOTA LEBIH DARI 8 JAM',
                'tidak_pakai_kendaraan_dinas' => 'TIDAK MENGGUNAKAN KENDARAAN DINAS',
                'tidak_menginap_hotel' => 'TIDAK MENGINAP DI HOTEL ATAU DI PENYEDIA JASA AKOMODASI LAINNYA',
                'keterlambatan' => 'KETERLAMBATAN PENGAJUAN TAGIHAN',
            ][$kondisi] ?? '';
            $s->addText('SURAT PERNYATAAN', ['bold' => true], ['alignment' => Jc::CENTER]);
            $s->addText($judul, ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
            $s->addText('Yang bertanda tangan di bawah ini' . ($kondisi === 'lebih8jam' ? ' :' : ':'), [], ['spaceAfter' => 120]);

            $identitas = [['Nama', $pelaksana['nama'] ?: '...........................'], ['NIP', $this->nip($pelaksana['nip'])]];
            if ($kondisi === 'tidak_menginap_hotel') {
                $identitas[] = ['Pangkat', $this->pangkatGol($pelaksana)];
            }
            if (in_array($kondisi, ['tidak_pakai_kendaraan_dinas', 'tidak_menginap_hotel'], true)) {
                $identitas[] = ['Satuan Kerja', 'BPS Kabupaten Murung Raya'];
            }
            $identitas[] = ['Jabatan', $pelaksana['jabatan'] ?: '-'];
            $this->identitas($s, $identitas, 22);

            if ($kondisi === 'lebih8jam') {
                $s->addText('Dengan ini menyatakan bahwa perjalanan dinas dalam kota yang saya laksanakan sebagai berikut :', [], $para);
                $w = $this->kolom([26, 2, 72]);
                $t = $this->tabel($s);
                $this->barisLabel($t, $w, 'a. Nomor Surat Tugas', $noSt . ', tanggal ' . $tglSt);
                $this->barisLabel($t, $w, 'b. Nomor SPD', $d['no_spd'] ? $noSpd . ', tanggal ' . $tglSpd : '-');
                $this->barisLabel($t, $w, 'c. Tujuan', ($d['perihal'] ?: '...........................') . $this->diTujuan() . ' selama '
                    . $this->terbilangHari($this->lamaHari($d['tanggal_mulai'], $d['tanggal_selesai'])) . ' pada tanggal ' . $this->rentangTanggal($d['tanggal_mulai'], $d['tanggal_selesai']), [], true);
                $s->addText('Adalah benar dilaksanakan lebih dari 8 jam.', [], $para);
                $s->addText('Demikian pernyataan ini dibuat dengan sebenar-benarnya, dan saya sanggup menerima konsekuensi jika pernyataan ini tidak benar.', [], $para);
                $s->addText('Puruk Cahu, ' . $tglTtd, [], ['alignment' => Jc::END, 'spaceBefore' => 120, 'spaceAfter' => 200, 'keepNext' => true]);
                $this->ttdDua($s, ['Mengetahui,', 'BPS Kabupaten Murung Raya', 'Pejabat Pembuat Komitmen,'], $d['ppk'], ['', '', 'Pelaksana Perjalanan Dinas'], $pelaksana);

                continue;
            }

            if ($kondisi === 'tidak_pakai_kendaraan_dinas') {
                $this->teksBaris($s, "Menerangkan bahwa dalam rangka melaksanakan perjalanan dinas untuk melaksanakan tugas kedinasan sesuai surat tugas nomor: {$noSt} tanggal {$tglSt}"
                    . ($d['no_spd'] ? " dan SPD nomor: {$noSpd} tanggal {$tglSpd}" : '') . ' saya benar-benar tidak menggunakan kendaraan dinas.', [], $para);
                $this->teksBaris($s, 'Demikian pernyataan ini kami buat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya. Apabila terdapat kekeliruan dalam pertanggungjawaban dan mengakibatkan kerugian negara, saya bersedia dituntut sesuai peraturan yang berlaku dan mengembalikan biaya transport atau biaya transport lokal yang sudah terlanjur saya terima ke kas negara.', [], $para);
                $penutup = 'Pelaksana Perjalanan Dinas,';
            } elseif ($kondisi === 'tidak_menginap_hotel') {
                $this->teksBaris($s, 'Menerangkan bahwa selama melaksanakan perjalanan dinas dalam rangka ' . ($this->perihalTanpaAwalan($d['perihal']) ?: '...........................')
                    . $this->diTujuan() . ' pada tanggal ' . $this->rentangTanggal($d['tanggal_mulai'], $d['tanggal_selesai']) . ', saya benar-benar tidak menginap di hotel atau jasa akomodasi komersial lainnya.', [], $para);
                $this->teksBaris($s, 'Demikian pernyataan ini saya buat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya. Apabila terdapat kekeliruan dalam pertanggung jawaban SPD dan mengakibatkan kerugian negara, saya bersedia dituntut sesuai peraturan yang berlaku dan mengembalikan biaya kompensasi menginap di hotel atau jasa akomodasi komersial lainnya yang sudah terlanjur saya terima ke kas negara.', [], $para);
                $penutup = 'Pelaksana Perjalanan Dinas,';
            } else {
                $s->addText('Dengan ini menyatakan sebagai berikut:', [], $para);
                $butir = ['alignment' => Jc::BOTH, 'indentation' => ['left' => 360, 'hanging' => 360], 'spaceAfter' => 80];
                $this->teksBaris($s, "1.\tTelah terjadi keterlambatan mengajukan hak tagihan kepada Pejabat Pembuat Komitmen (PPK) BPS Kabupaten Murung Raya atas pelaksanaan perjalanan dinas "
                    . ($d['perihal'] ?: '...........................') . ' yang telah selesai dilaksanakan pada tanggal ' . $this->rentangTanggal($d['tanggal_mulai'], $d['tanggal_selesai'])
                    . ".\nBerdasarkan Surat Tugas Nomor: {$noSt} tanggal {$tglSt}" . ($d['no_spd'] ? "\nBerdasarkan SPD Nomor: {$noSpd} tanggal {$tglSpd}" : ''), [], $butir);
                $this->teksBaris($s, "2.\tKeterlambatan mengajukan hak tagihan disebabkan oleh " . ($keterangan ?: '...........................'), [], $butir);
                $this->teksBaris($s, "3.\tSelanjutnya saya tidak akan mengalami keterlambatan kembali dalam pengajuan hak tagihan sesuai dengan batas waktu pengajuan hak tagihan kepada Negara yang diatur dalam PMK Nomor 190/PMK.05/2012 tentang Tata Cara Pembayaran dalam Rangka Pelaksanaan Anggaran Pendapatan dan Belanja Negara.", [], $butir);
                $s->addText('Demikian pernyataan ini dibuat dengan sebenar-benarnya.', [], $para);
                $penutup = 'Yang menyatakan,';
            }
            $this->spasi($s, 1);
            $this->ttdKanan($s, ['Puruk Cahu, ' . $tglTtd, $penutup], $pelaksana['nama'] ?? '', $pelaksana['nip'] ?? '');
        }
    }

    private function laporan(): void
    {
        $d = $this->d;
        $s = $this->sectionA4();
        $pelaksana = $d['pelaksana'];

        $this->judul($s, 'LAPORAN HASIL PERJALANAN DINAS');
        $this->spasi($s, 1);
        $w = $this->kolom([16, 2, 82]);
        $t = $this->tabel($s);
        $this->barisLabel($t, $w, 'Kepada Yth', 'Kepala Badan Pusat Statistik Kabupaten Murung Raya');
        $this->barisLabel($t, $w, 'Perihal', 'Laporan Perjalanan Dinas dalam rangka ' . ($this->perihalTanpaAwalan($d['perihal']) ?: '...........................'), [], true);
        $this->spasi($s, 1);

        // I. - V.: nomor | judul | : | isi
        $w = $this->kolom([7, 28, 2, 63]);
        $p = ['spaceAfter' => 100];
        $t = $this->tabel($s);
        $t->addRow();
        $t->addCell($w[0])->addText('I.', [], $p);
        $t->addCell($w[1])->addText('Pelaksana Perjalanan', [], $p);
        $t->addCell($w[2])->addText(':', [], $p);
        $c = $t->addCell($w[3]);
        $wi = [(int) ($w[3] * 0.25), $w[3] - (int) ($w[3] * 0.25)];
        $ti = $c->addTable(['cellMargin' => 0]);
        foreach ([['Nama', $pelaksana['nama'] ?: '...........................'], ['NIP', $this->nip($pelaksana['nip'])], ['Jabatan', $pelaksana['jabatan'] ?: '-']] as [$a, $b]) {
            $ti->addRow();
            $ti->addCell($wi[0])->addText($a);
            $ti->addCell($wi[1])->addText(': ' . $b);
        }
        $c->addText('', [], ['spaceAfter' => 60]);

        $dasar = ['Surat Tugas Nomor ' . ($d['no_surat_tugas'] ?: '...........................') . "\ntanggal " . $this->tanggal($d['tanggal_surat_tugas'])];
        if ($d['no_spd']) {
            $dasar[] = 'SPD Nomor ' . $d['no_spd'] . "\ntanggal " . $this->tanggal($d['tanggal_spd']);
        }
        $t->addRow();
        $t->addCell($w[0])->addText('II.', [], $p);
        $t->addCell($w[1])->addText('Dasar Pelaksanaan', [], $p);
        $t->addCell($w[2])->addText(':', [], $p);
        $c = $t->addCell($w[3]);
        foreach ($dasar as $i => $isi) {
            $this->teksBaris($c, ($i + 1) . ".\t" . $isi, [], ['indentation' => ['left' => 300, 'hanging' => 300], 'spaceAfter' => $i === count($dasar) - 1 ? 100 : 0]);
        }

        $tujuan = $d['desa_tujuan'] ? $d['desa_tujuan'] . ($d['kabupaten_tujuan'] ? ', ' . $d['kabupaten_tujuan'] : '') : ($d['kabupaten_tujuan'] ?: '...........................');
        foreach ([['III.', 'Tujuan Perjalanan Dinas', $d['perihal'] ?: '...........................', true], ['IV.', 'Daerah Tujuan/Instansi', $tujuan, false], ['V.', 'Waktu Pelaksanaan', $this->rentangTanggal($d['tanggal_mulai'], $d['tanggal_selesai']), false]] as [$no, $label, $isi, $rata]) {
            $t->addRow();
            $t->addCell($w[0])->addText($no, [], $p);
            $t->addCell($w[1])->addText($label, [], $p);
            $t->addCell($w[2])->addText(':', [], $p);
            $this->teksBaris($t->addCell($w[3]), $isi, [], $p + ($rata ? ['alignment' => Jc::BOTH] : []));
        }

        // VI. Kesimpulan
        $t->addRow();
        $t->addCell($w[0])->addText('VI.', [], ['spaceBefore' => 100]);
        $c = $t->addCell($w[1] + $w[2] + $w[3], ['gridSpan' => 3]);
        $c->addText('Kesimpulan Hasil Kegiatan :', [], ['spaceBefore' => 100]);
        $this->teksBaris($c, trim((string) $d['kesimpulan_hasil_kegiatan']) ?: '-', [], ['alignment' => Jc::BOTH, 'spaceAfter' => 100]);

        // VII. Tindak Lanjut
        $baris = array_map('trim', preg_split('/\r\n|\r|\n/', (string) $d['tindak_lanjut']));
        while ($baris && $baris[0] === '') {
            array_shift($baris);
        }
        while ($baris && end($baris) === '') {
            array_pop($baris);
        }
        $t->addRow();
        $t->addCell($w[0])->addText('VII.', [], ['spaceBefore' => 100]);
        $t->addCell($w[1])->addText('Tindak Lanjut', [], ['spaceBefore' => 100]);
        $c = $t->addCell($w[2] + $w[3], ['gridSpan' => 2]);
        $c->addText(': Hal-hal yang perlu diperhatikan antara lain :', [], ['spaceBefore' => 100]);
        if (! $baris) {
            $c->addText('-');
        }
        $nomor = 0;
        foreach ($baris as $item) {
            $item === ''
                ? $c->addText('')
                : $this->teksBaris($c, (++$nomor) . ".\t" . $item, [], ['alignment' => Jc::BOTH, 'indentation' => ['left' => 300, 'hanging' => 300]]);
        }

        $s->addText('Demikian Laporan Perjalanan Dinas ini dibuat sebagai bahan Laporan.', [], ['spaceBefore' => 360, 'spaceAfter' => 120, 'keepNext' => true]);
        $tglLaporan = $d['tanggal_laporan'] ?: $this->tambahHariKerja($d['tanggal_selesai'], 1);
        $s->addText('Puruk Cahu, ' . $this->tanggal($tglLaporan), [], ['alignment' => Jc::END, 'spaceAfter' => 200, 'keepNext' => true]);
        $this->ttdDua($s, ['Mengetahui :', 'Kepala BPS Kabupaten Murung Raya,'], $d['penandatangan'], ['', 'Yang Melakukan Perjalanan Dinas,'], $pelaksana);

        // Dokumentasi: halaman baru, 2 kolom; tiap pasangan = baris foto + baris keterangan.
        $s->addPageBreak();
        $s->addText('Dokumentasi', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);
        $foto = $this->pd->dokumentasi->values();
        if ($foto->isEmpty()) {
            $s->addText('Belum ada dokumentasi.', ['color' => '808080'], ['alignment' => Jc::CENTER]);

            return;
        }
        $setengah = (int) ($this->lebar / 2);
        $t = $this->tabel($s);
        foreach ($foto->chunk(2) as $pasangan) {
            $pasangan = $pasangan->values();
            $t->addRow(null, ['cantSplit' => true]);
            for ($i = 0; $i < 2; $i++) {
                $c = $this->sel($t, $setengah, 'TLR', ['valign' => 'center']);
                $item = $pasangan[$i] ?? null;
                $path = $item ? Storage::disk('public')->path($item->path_file) : null;
                if ($path && is_file($path) && @getimagesize($path)) {
                    [$lw, $lh] = $this->ukuranMuat($path, ($setengah - 240) / 20, Converter::cmToPoint(6.5));
                    $c->addImage($path, ['width' => $lw, 'height' => $lh, 'unit' => 'pt', 'alignment' => Jc::CENTER]);
                } elseif ($item) {
                    $c->addText($item->nama_file, [], ['alignment' => Jc::CENTER]);
                }
            }
            $t->addRow(null, ['cantSplit' => true]);
            for ($i = 0; $i < 2; $i++) {
                $item = $pasangan[$i] ?? null;
                $this->sel($t, $setengah, 'BLR')->addText($item->caption ?? '', ['size' => 9], ['alignment' => Jc::CENTER, 'spaceBefore' => 60, 'spaceAfter' => 60]);
            }
        }
    }

    // ================================================================== Penyusun

    private function sectionA4(): Section
    {
        return $this->section('A4', 2.54, 2.54);
    }

    private function section(string $kertas, float $marginAtasBawah, float $marginKiriKanan): Section
    {
        $this->tutupSection();
        $lebarKertas = $kertas === 'Folio' ? 21.59 : 21.0;
        $this->lebar = (int) Converter::cmToTwip($lebarKertas - 2 * $marginKiriKanan);

        return $this->sectionAktif = $this->word->addSection([
            'paperSize' => $kertas,
            'marginTop' => Converter::cmToTwip($marginAtasBawah),
            'marginBottom' => Converter::cmToTwip($marginAtasBawah),
            'marginLeft' => Converter::cmToTwip($marginKiriKanan),
            'marginRight' => Converter::cmToTwip($marginKiriKanan),
        ]);
    }

    /**
     * Word selalu butuh paragraf setelah tabel di akhir section; kalau dibiarkan, paragraf
     * kosong berukuran normal itu bisa terlempar sendirian ke halaman baru (halaman kosong).
     * Jadi ditutup dengan paragraf sangat kecil.
     */
    private function tutupSection(): void
    {
        if ($this->sectionAktif) {
            $this->sectionAktif->addText('', ['size' => 1], ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0]);
            $this->sectionAktif = null;
        }
    }

    /** Lebar kolom (twip) dari persentase. */
    private function kolom(array $persen, ?int $lebar = null): array
    {
        $lebar ??= $this->lebar;
        $hasil = array_map(fn ($p) => (int) round($lebar * $p / 100), $persen);
        $hasil[count($hasil) - 1] += $lebar - array_sum($hasil);

        return $hasil;
    }

    /** Tabel tanpa garis, lebar penuh, susunan kolom tetap. */
    private function tabel(AbstractContainer $wadah, array $gaya = []): Table
    {
        return $wadah->addTable($gaya + [
            'layout' => GayaTabel::LAYOUT_FIXED,
            'cellMarginLeft' => 70,
            'cellMarginRight' => 70,
        ]);
    }

    /** Sel dengan garis hanya di sisi yang disebut: T(op) B(ottom) L(eft) R(ight). */
    private function sel(Table $t, int $lebar, string $sisi, array $gaya = []): Cell
    {
        foreach (['T' => 'Top', 'B' => 'Bottom', 'L' => 'Left', 'R' => 'Right'] as $kode => $nama) {
            if (str_contains($sisi, $kode)) {
                $gaya["border{$nama}Size"] = 6;
                $gaya["border{$nama}Color"] = '000000';
                $gaya["border{$nama}Style"] = 'single';
            }
        }

        return $t->addCell($lebar, $gaya + ['valign' => 'top']);
    }

    private function barisLabel(Table $t, array $w, string $label, string $isi, array $font = [], bool $rataKiriKanan = false, array $para = []): void
    {
        $t->addRow();
        $t->addCell($w[0])->addText($label, $font, $para);
        $t->addCell($w[1])->addText(':', $font, $para);
        $this->teksBaris($t->addCell($w[2]), $isi, $font, $para + ($rataKiriKanan ? ['alignment' => Jc::BOTH] : []));
    }

    /** Satu nomor SPD dengan sub-baris (a, b, c) tanpa garis pemisah di antaranya. */
    private function barisSpd(Table $t, array $w, string $no, array $sub, array $f): void
    {
        $n = count($sub);
        foreach ($sub as $i => [$label, $nilai]) {
            $atas = $i === 0 ? 'T' : '';
            $bawah = $i === $n - 1 ? 'B' : '';
            $t->addRow();
            $gayaNo = $n > 1 ? ['vMerge' => $i === 0 ? 'restart' : 'continue'] : [];
            $c = $this->sel($t, $w[0], 'LR' . $atas . $bawah, $gayaNo);
            if ($i === 0) {
                $c->addText($no, $f);
            }
            $this->teksBaris($this->sel($t, $w[1], 'LR' . $atas . $bawah), $label, $f);
            $this->teksBaris($this->sel($t, $w[2] + $w[3], 'LR' . $atas . $bawah, ['gridSpan' => 2]), $nilai, $f);
        }
    }

    /** Tabel identitas "Nama : ...". */
    private function identitas(Section $s, array $baris, int $persenLabel): void
    {
        $w = $this->kolom([$persenLabel, 2, 98 - $persenLabel]);
        $t = $this->tabel($s);
        foreach ($baris as [$a, $b]) {
            $this->barisLabel($t, $w, $a, $b);
        }
    }

    private function judul(Section $s, string $teks): void
    {
        $s->addText($teks, ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
    }

    private function spasi(AbstractContainer $s, int $jumlah, ?float $ukuran = null): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            $s->addText('', $ukuran ? ['size' => $ukuran] : []);
        }
    }

    private function garis(Section $s): void
    {
        $s->addText('', ['size' => 4], ['borderBottomSize' => 6, 'borderBottomColor' => '000000', 'spaceAfter' => 100]);
    }

    /** Blok tanda tangan rata tengah di separuh kanan halaman. */
    private function ttdKanan(Section $s, array $baris, string $nama, ?string $nip, array $f = [], bool $barisPertamaKiri = false, array $para = []): void
    {
        // Paragraf yang digeser ke separuh kanan (bukan tabel): dokumen yang berakhir dengan
        // tabel memaksa Word menambah paragraf kosong yang bisa jadi halaman kosong.
        $para += ['indentation' => ['left' => (int) ($this->lebar * 0.5)]];
        if ($barisPertamaKiri) {
            $this->teksBaris($s, array_shift($baris), $f, $para + ['keepNext' => true, 'keepLines' => true]);
        }
        $this->blokTtd($s, $baris, $nama, $nip, $f, $para);
    }

    /** Dua tanda tangan berdampingan (kiri & kanan). */
    private function ttdDua(Section $s, array $kiri, array $orangKiri, array $kanan, array $orangKanan): void
    {
        $t = $this->tabel($s);
        $t->addRow(null, ['cantSplit' => true]);
        $setengah = (int) ($this->lebar / 2);
        $this->blokTtd($t->addCell($setengah), $kiri, $orangKiri['nama'] ?? '', $orangKiri['nip'] ?? '');
        $this->blokTtd($t->addCell($this->lebar - $setengah), $kanan, $orangKanan['nama'] ?? '', $orangKanan['nip'] ?? '');
    }

    /** Isi blok tanda tangan: baris jabatan, ruang tanda tangan, nama (tebal bergaris bawah), NIP. */
    private function blokTtd(AbstractContainer $c, array $baris, string $nama, ?string $nip, array $f = [], array $para = []): void
    {
        $p = $para + ['alignment' => Jc::CENTER, 'keepNext' => true, 'keepLines' => true];
        foreach ($baris as $b) {
            $c->addText($b, $f, $p);
        }
        for ($i = 0; $i < 3; $i++) {
            $c->addText('', $f, $p);
        }
        $c->addText($nama !== '' ? $nama : '(...........................)', $f + ['bold' => true, 'underline' => 'single'], $p);
        if ($nip !== null) {
            $c->addText('NIP. ' . $this->nip($nip), $f, ['alignment' => Jc::CENTER] + $para);
        }
    }

    /**
     * Tambah paragraf berisi teks isian user: tiap baris baru (Enter) jadi baris baru di
     * Word, dan teks yang diapit garis bawah (_teks_) ditulis miring.
     */
    private function teksBaris(AbstractContainer $wadah, string $teks, array $font = [], array $para = []): void
    {
        // Tiap baris jadi paragraf sendiri (bukan line break): Word meregangkan baris yang
        // diakhiri line break pada paragraf rata kiri-kanan.
        $baris = preg_split('/\r\n|\r|\n/', $teks);
        $n = count($baris);
        foreach ($baris as $i => $b) {
            $p = $para;
            if ($i > 0) {
                $p['spaceBefore'] = 0;
                if (isset($p['indentation']['hanging'])) {
                    $p['indentation'] = ['left' => $p['indentation']['left'] ?? 0, 'hanging' => 0];
                }
            }
            if ($i < $n - 1) {
                $p['spaceAfter'] = 0;
            }
            $this->tulisMiring($wadah->addTextRun($p), $b, $font);
        }
    }

    private function tulisMiring(TextRun $run, string $teks, array $font): void
    {
        $bagian = preg_split('/(?<![0-9A-Za-z_])_([^_\n]+?)_(?![0-9A-Za-z_])/', $teks, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($bagian as $i => $b) {
            if ($b !== '') {
                $run->addText($b, $i % 2 === 1 ? $font + ['italic' => true] : $font);
            }
        }
    }

    private function tinggiProporsional(string $path, float $lebar): float
    {
        [$w, $h] = getimagesize($path) ?: [1, 1];

        return round($lebar * $h / max($w, 1), 1);
    }

    /** Ukuran foto (pt) yang muat dalam kotak lebar x tinggi tanpa mengubah rasionya. */
    private function ukuranMuat(string $path, float $maksLebar, float $maksTinggi): array
    {
        [$w, $h] = getimagesize($path) ?: [1, 1];
        $skala = min($maksLebar / max($w, 1), $maksTinggi / max($h, 1));

        return [round($w * $skala, 1), round($h * $skala, 1)];
    }

    // ================================================================== Format & hitungan
    // (padanan fungsi di dokumen-perjadin.js)

    private function nip(?string $nip): string
    {
        return $nip ?: '-';
    }

    private function pangkatGol(array $p): string
    {
        return ($p['pangkat'] ?: '-') . (! empty($p['golongan']) ? ' / ' . $p['golongan'] : '');
    }

    private function wilayahAsal(): string
    {
        return (string) ($this->d['desa_asal'] ?: $this->d['kabupaten_asal']);
    }

    private function wilayahTujuan(): string
    {
        return (string) ($this->d['desa_tujuan'] ?: $this->d['kabupaten_tujuan']);
    }

    private function diTujuan(): string
    {
        return $this->wilayahTujuan() !== '' ? ' di ' . $this->wilayahTujuan() : '';
    }

    private function perihalTanpaAwalan(?string $s): string
    {
        return (string) preg_replace('/^\s*(biaya\s+)?perjalanan\s+dinas\s+dalam\s+rangka\s+/i', '', (string) $s);
    }

    private function hurufAwalKecil(string $s): string
    {
        return preg_match('/^[A-Z][a-z]/', $s) ? lcfirst($s) : $s;
    }

    private function tanggal(?string $ymd): string
    {
        if (! $ymd) {
            return '.......................';
        }
        $t = strtotime($ymd);

        return $t ? (int) date('j', $t) . ' ' . self::BULAN[(int) date('n', $t) - 1] . ' ' . date('Y', $t) : $ymd;
    }

    private function rentangTanggal(?string $mulai, ?string $selesai): string
    {
        if (! $mulai) {
            return '...........................';
        }
        if (! $selesai || $selesai === $mulai) {
            return $this->tanggal($mulai);
        }
        $a = strtotime($mulai);
        $b = strtotime($selesai);
        if (date('Y-m', $a) === date('Y-m', $b)) {
            return date('j', $a) . ' – ' . date('j', $b) . ' ' . self::BULAN[(int) date('n', $b) - 1] . ' ' . date('Y', $b);
        }

        return $this->tanggal($mulai) . ' s.d. ' . $this->tanggal($selesai);
    }

    private function lamaHari(?string $mulai, ?string $selesai): int
    {
        if (! $mulai || ! $selesai) {
            return 0;
        }

        return max(1, (int) round((strtotime($selesai) - strtotime($mulai)) / 86400) + 1);
    }

    private function tambahHariKerja(?string $ymd, int $jumlah): ?string
    {
        if (! $ymd) {
            return null;
        }
        $t = strtotime($ymd);
        while ($jumlah > 0) {
            $t = strtotime('+1 day', $t);
            if ((int) date('N', $t) <= 5) {
                $jumlah--;
            }
        }

        return date('Y-m-d', $t);
    }

    private function rupiah(float|int|null $n): string
    {
        return 'Rp. ' . number_format((float) $n, 0, ',', '.');
    }

    private function terbilang(float|int $n): string
    {
        $n = (int) floor(abs($n));
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $ubah = function (int $x) use (&$ubah, $satuan): string {
            return match (true) {
                $x < 12 => $satuan[$x],
                $x < 20 => $ubah($x - 10) . ' belas',
                $x < 100 => $ubah(intdiv($x, 10)) . ' puluh' . ($x % 10 ? ' ' . $ubah($x % 10) : ''),
                $x < 200 => 'seratus' . ($x % 100 ? ' ' . $ubah($x % 100) : ''),
                $x < 1000 => $ubah(intdiv($x, 100)) . ' ratus' . ($x % 100 ? ' ' . $ubah($x % 100) : ''),
                $x < 2000 => 'seribu' . ($x % 1000 ? ' ' . $ubah($x % 1000) : ''),
                $x < 1000000 => $ubah(intdiv($x, 1000)) . ' ribu' . ($x % 1000 ? ' ' . $ubah($x % 1000) : ''),
                $x < 1000000000 => $ubah(intdiv($x, 1000000)) . ' juta' . ($x % 1000000 ? ' ' . $ubah($x % 1000000) : ''),
                default => $ubah(intdiv($x, 1000000000)) . ' miliar' . ($x % 1000000000 ? ' ' . $ubah($x % 1000000000) : ''),
            };
        };

        return $n === 0 ? 'nol' : trim($ubah($n));
    }

    private function terbilangHari(int $n): string
    {
        return $n . ' (' . $this->terbilang($n) . ') hari';
    }

    private function terbilangRupiah(float|int $n): string
    {
        return ucfirst($this->terbilang($n)) . ' rupiah';
    }

    private function jumlahPengeluaran(): float
    {
        return array_sum(array_map(fn ($p) => (float) $p['nominal'], $this->d['pengeluaran']));
    }

    private function spdAtauSt(): array
    {
        $st = $this->d['jenis_perjadin'] === 'dalam_kota_kurang_8_jam';

        return [
            'label' => $st ? 'ST' : 'SPD',
            'nomor' => ($st ? $this->d['no_surat_tugas'] : $this->d['no_spd']) ?: '...........................',
            'tanggal' => $st ? $this->d['tanggal_surat_tugas'] : $this->d['tanggal_spd'],
        ];
    }

    private function komponenRincian(): array
    {
        $d = $this->d;
        $tanpaHarian = $d['jenis_perjadin'] === 'dalam_kota_kurang_8_jam';
        $lama = $this->lamaHari($d['tanggal_mulai'], $d['tanggal_selesai']);
        $malam = max($lama - 1, 0);
        $rate = fn ($j) => array_sum(array_map(fn ($r) => (float) $r['nominal'], array_filter($d['rincian'], fn ($r) => $r['jenis_komponen'] === $j)));
        $hari = function ($j) use ($d) {
            foreach ($d['rincian'] as $r) {
                if ($r['jenis_komponen'] === $j && $r['jumlah_hari'] !== null && $r['jumlah_hari'] !== '') {
                    return (int) $r['jumlah_hari'];
                }
            }

            return null;
        };
        $k = [
            'lama' => $lama,
            'tanpaHarian' => $tanpaHarian,
            'hariUangHarian' => $hari('uang_harian') ?? $lama,
            'hariTransport' => $hari('transport') ?? $lama,
            'malamPenginapan' => $hari('penginapan') ?? $malam,
            'rateHarian' => $rate('uang_harian'),
            'rateTransport' => $rate('transport'),
            'rateMalam' => $rate('penginapan'),
        ];
        $k['uangHarian'] = $tanpaHarian ? 0 : $k['rateHarian'] * $k['hariUangHarian'];
        $k['transport'] = $k['rateTransport'] * $k['hariTransport'];
        $k['penginapan'] = $tanpaHarian ? 0 : $k['rateMalam'] * $k['malamPenginapan'];

        return $k;
    }

    // Tujuan Transport: lebih dari satu desa/kel. -> "kecamatan X".
    private function tujuanTransport(string $desaTujuan): string
    {
        $daftar = array_values(array_filter(array_map('trim', explode(',', (string) preg_replace('/,?\s*dan\s+/i', ', ', $desaTujuan, 1)))));
        if (count($daftar) > 1) {
            $nama = trim((string) preg_replace('/^(Desa|Kelurahan)\s+/i', '', $daftar[0]));
            foreach (config('wilayah.murung_raya', []) as $kecamatan => $isi) {
                $semua = is_array($isi) ? array_merge(...array_values(array_filter($isi, 'is_array')) ?: [[]]) : [];
                if (in_array($nama, $semua, true)) {
                    return 'kecamatan ' . $kecamatan;
                }
            }
        }

        return $desaTujuan ?: '...';
    }
}
