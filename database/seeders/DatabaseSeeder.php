<?php

namespace Database\Seeders;

use App\Models\JenisKegiatan;
use App\Models\PegawaiMitra;
use App\Models\User;
use App\Models\WilayahDesa;
use App\Models\WilayahKabupatenKota;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Username akun default per pegawai, dipetakan dari NIP pada
     * database/seeders/data/template_pegawai.xlsx. Peran administrator
     * hanya diberikan ke akun Luthfiani (fia), sisanya peran pegawai biasa.
     */
    private const USERNAME_PER_NIP = [
        '19780715 200212 1 006' => ['username' => 'restu', 'role' => 'pegawai'],   // Restu Kristianto
        '19870623 201403 2 002' => ['username' => 'asih', 'role' => 'pegawai'],    // Yektining Asih Rahayu
        '19771027 201101 1 002' => ['username' => 'willo', 'role' => 'pegawai'],   // Willo Friody
        '19930505 201602 2 002' => ['username' => 'yovi', 'role' => 'pegawai'],    // Yoviana Yulta
        '19970409 202104 1 002' => ['username' => 'aji', 'role' => 'pegawai'],     // Krishna Aji Satriatama
        '19980831 202104 1 001' => ['username' => 'luthfan', 'role' => 'pegawai'], // Luthfan Shaoti
        '19990307 202104 1 001' => ['username' => 'khoirul', 'role' => 'pegawai'], // Khoirul Anwar (PPK)
        '19970731 202201 1 001' => ['username' => 'noor', 'role' => 'pegawai'],    // Noor Supriyadi
        '19990424 202201 1 004' => ['username' => 'mikha', 'role' => 'pegawai'],   // Mikha Aprilio
        '19991011 202302 2 001' => ['username' => 'ifa', 'role' => 'pegawai'],     // Rifatul Mina
        '19991017 202310 2 001' => ['username' => 'diah', 'role' => 'pegawai'],    // Diah Aisyah
        '20020508 202412 1 002' => ['username' => 'angga', 'role' => 'pegawai'],   // Angga Jati Mandiri
        '20010711 202412 1 002' => ['username' => 'haidar', 'role' => 'pegawai'],  // Haidar Hilmy Ahmady
        '20000801 202412 2 001' => ['username' => 'wida', 'role' => 'pegawai'],    // Nurwidati Puspita
        '20011223 202412 2 002' => ['username' => 'vivi', 'role' => 'pegawai'],    // Vivi Dewita Sari
        '20030629 202603 2 001' => ['username' => 'fia', 'role' => 'administrator'], // Luthfiani Nur Aisyah
        '19990712 202104 2 001' => ['username' => 'tiara', 'role' => 'pegawai'],   // Tiara Andansari (Bendahara)
        '19881210 202421 1 004' => ['username' => 'siska', 'role' => 'pegawai'],   // Fransiska
        '19900107 202521 1 042' => ['username' => 'irwan', 'role' => 'pegawai'],   // Muhammad Irwan Setiawan
        '19900616 202521 1 052' => ['username' => 'jon', 'role' => 'pegawai'],     // Jonsatriafranata
        '19920101 202521 1 094' => ['username' => 'rano', 'role' => 'pegawai'],    // Rano Karno
        '19920704 202521 1 044' => ['username' => 'muslim', 'role' => 'pegawai'],  // Muslim Alatas
        '20001104 202521 2 020' => ['username' => 'satri', 'role' => 'pegawai'],   // Satri
    ];

    /**
     * Password default untuk seluruh akun yang dibuat oleh seeder ini.
     * Admin dapat mereset password masing-masing akun lewat halaman Kelola Akun.
     */
    private const DEFAULT_PASSWORD = 'password';

    public function run(): void
    {
        $this->seedPegawaiMitra();
        $this->seedJenisKegiatan();
        $this->seedAkunPengguna();
        $this->seedWilayah();
        $this->call(RateTransportAkomodasiSeeder::class);
    }

    /**
     * Data pegawai BPS Kabupaten Murung Raya, diimpor dari template resmi
     * (database/seeders/data/template_pegawai.xlsx) supaya konsisten dengan
     * yang dipakai admin saat import manual lewat menu Kelola Pegawai.
     * Pakai updateOrCreate (key: nip) supaya seeder aman dijalankan ulang.
     */
    private function seedPegawaiMitra(): void
    {
        // Data dummy lama (dibuat sebelum data pegawai resmi tersedia) dengan
        // nama yang sama tapi tanpa NIP; dibersihkan supaya tidak duplikat
        // dengan baris resmi dari template yang sekarang dipakai di bawah.
        PegawaiMitra::whereNull('nip')
            ->whereIn('nama', ['Restu Kristianto', 'Khoirul Anwar, S.Tr.Stat.', 'Tiara Andansari, A.Md.Stat', 'Luthfiani Nur Aisyah, S.Tr.Stat'])
            ->delete();

        $path = database_path('seeders/data/template_pegawai.xlsx');
        $sheet = IOFactory::load($path)->getActiveSheet();

        foreach ($sheet->toArray(null, true, true, false) as $i => $row) {
            if ($i === 0) {
                continue; // baris header
            }

            [$nip, $nama, $jabatan, $pangkat, $golongan, $peranPejabat] = array_pad($row, 6, null);
            $nama = trim((string) $nama);

            if ($nama === '') {
                continue;
            }

            PegawaiMitra::updateOrCreate(
                ['nip' => trim((string) $nip)],
                [
                    'nama' => $nama,
                    'jabatan' => $jabatan !== null && trim((string) $jabatan) !== '' ? trim($jabatan) : null,
                    'pangkat' => $pangkat !== null && trim((string) $pangkat) !== '' ? trim($pangkat) : null,
                    'golongan' => $golongan !== null && trim((string) $golongan) !== '' ? trim($golongan) : null,
                    'status_kepegawaian' => 'pegawai',
                    'peran_pejabat' => in_array($peranPejabat, ['kepala_satker', 'ppk', 'bendahara'], true) ? $peranPejabat : null,
                ]
            );
        }

        // Satu contoh data mitra statistik (tidak tercakup di template pegawai).
        PegawaiMitra::updateOrCreate(
            ['nama' => 'Jajang Aji Setiawan'],
            [
                'nip' => null,
                'jabatan' => 'Mitra Statistik',
                'pangkat' => null,
                'golongan' => null,
                'status_kepegawaian' => 'mitra',
                'peran_pejabat' => null,
            ]
        );
    }

    /**
     * Akun default untuk setiap pegawai di atas, dengan username berupa nama
     * panggilan (bukan NIP/email) supaya mudah diingat. Password default bisa
     * direset lewat halaman Kelola Akun (menu khusus administrator).
     */
    private function seedAkunPengguna(): void
    {
        foreach (self::USERNAME_PER_NIP as $nip => $akun) {
            $pegawai = PegawaiMitra::where('nip', $nip)->first();

            if (!$pegawai) {
                continue;
            }

            User::updateOrCreate(
                ['username' => $akun['username']],
                [
                    'name' => $pegawai->nama,
                    'email' => $akun['username'] . '@simpel.local',
                    'role' => $akun['role'],
                    'id_pegawai_mitra' => $pegawai->id_pegawai_mitra,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                ]
            );
        }
    }

    /**
     * Migrasi data master kota/wilayah tujuan dari config/wilayah.php (sumber data awal)
     * ke database, supaya admin bisa kelola lewat menu Kelola Wilayah dan sistem bisa
     * menghitung jumlah_dipilih untuk rekomendasi di dropdown form. Pakai firstOrCreate
     * (bukan updateOrCreate) supaya jumlah_dipilih yang sudah terkumpul tidak ke-reset
     * setiap kali seeder dijalankan ulang.
     */
    private function seedWilayah(): void
    {
        // updateOrCreate (bukan firstOrCreate) supaya baris yang sudah ada dari sebelum
        // kolom "provinsi" ditambahkan (mis. 14 kab/kota Kalimantan Tengah yang sudah
        // lama tersimpan) ikut ter-backfill provinsinya, bukan cuma baris baru.
        foreach (config('wilayah.kabupaten_kota', []) as $provinsi => $daftarNama) {
            foreach ($daftarNama as $nama) {
                WilayahKabupatenKota::updateOrCreate(['nama' => $nama], ['provinsi' => $provinsi]);
            }
        }

        foreach (config('wilayah.murung_raya', []) as $kecamatan => $wilayah) {
            foreach ($wilayah['kelurahan'] ?? [] as $nama) {
                WilayahDesa::firstOrCreate(['kecamatan' => $kecamatan, 'jenis' => 'kelurahan', 'nama' => $nama]);
            }
            foreach ($wilayah['desa'] ?? [] as $nama) {
                WilayahDesa::firstOrCreate(['kecamatan' => $kecamatan, 'jenis' => 'desa', 'nama' => $nama]);
            }
        }
    }

    /**
     * Data jenis kegiatan (versi terbaru).
     *
     * Pakai updateOrCreate (key: nama_kegiatan) supaya template
     * uraian/MAK bisa diperbarui tanpa menghapus/duplikasi baris.
     */
    private function seedJenisKegiatan(): void
    {
        $data = [
            [
                'nama_kegiatan' => 'Pendataan Lapangan',
                'template_uraian_tugas' => 'Melakukan pendataan lapangan [survei]',
                'template_perihal' => 'Melakukan Pendataan Lapangan Survei []',
                'template_uraian_laporan' => '',
                'saran_mak_default' => 'GG.[].BMA.[].[].A.524113',
            ],
            [
                'nama_kegiatan' => 'Pengawasan Lapangan',
                'template_uraian_tugas' => 'Melakukan pengawasan lapangan [survei]',
                'template_perihal' => 'Melakukan Pengawasan Pendataan Lapangan Survei []',
                'template_uraian_laporan' => '',
                'saran_mak_default' => 'GG.[].BMA.[].[].A.524113',
            ],
            [
                'nama_kegiatan' => 'Supervisi Pengawasan Lapangan',
                'template_uraian_tugas' => 'Melakukan supervisi pendataan lapangan [survei]',
                'template_perihal' => 'Melakukan Supervisi Pengawasan Pendataan Lapangan Survei []',
                'template_uraian_laporan' => '',
                'saran_mak_default' => 'GG.[].BMA.[].[].A.524113',
            ],
        ];

        foreach ($data as $row) {
            JenisKegiatan::updateOrCreate(
                ['nama_kegiatan' => $row['nama_kegiatan']], // kolom pencocok
                $row
            );
        }
    }
}
