<?php

namespace Database\Seeders;

use App\Models\JenisKegiatan;
use App\Models\PegawaiMitra;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // data contoh untuk testing wizard perjalanan dinas (sesuai contoh dokumen BPS Kab. Murung Raya)
        if (PegawaiMitra::count() === 0) {
            PegawaiMitra::insert([
                [
                    'nip' => null,
                    'nama' => 'Restu Kristianto',
                    'jabatan' => 'Kepala Badan Pusat Statistik Kabupaten Murung Raya',
                    'pangkat' => null,
                    'golongan' => null,
                    'status_kepegawaian' => 'pegawai',
                    'peran_pejabat' => 'kepala_satker',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nip' => '19990307 202104 1 001',
                    'nama' => 'Khoirul Anwar, S.Tr.Stat.',
                    'jabatan' => 'Pejabat Pembuat Komitmen',
                    'pangkat' => null,
                    'golongan' => null,
                    'status_kepegawaian' => 'pegawai',
                    'peran_pejabat' => 'ppk',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nip' => '19990712 202104 2 001',
                    'nama' => 'Tiara Andansari, A.Md.Stat',
                    'jabatan' => 'Bendahara Pengeluaran',
                    'pangkat' => null,
                    'golongan' => null,
                    'status_kepegawaian' => 'pegawai',
                    'peran_pejabat' => 'bendahara',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nip' => '20030629 202603 2 001',
                    'nama' => 'Luthfiani Nur Aisyah, S.Tr.Stat',
                    'jabatan' => 'Pelaksana',
                    'pangkat' => 'Penata Muda',
                    'golongan' => 'III/a',
                    'status_kepegawaian' => 'pegawai',
                    'peran_pejabat' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nip' => null,
                    'nama' => 'Jajang Aji Setiawan',
                    'jabatan' => 'Mitra Statistik',
                    'pangkat' => null,
                    'golongan' => null,
                    'status_kepegawaian' => 'mitra',
                    'peran_pejabat' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (JenisKegiatan::count() === 0) {
            JenisKegiatan::insert([
                [
                    'nama_kegiatan' => 'Pendataan Lapangan',
                    'template_uraian_tugas' => 'Melaksanakan pendataan lapangan sesuai jadwal yang telah ditetapkan.',
                    'template_uraian_laporan' => 'Kegiatan pendataan lapangan telah dilaksanakan sesuai target.',
                    'saran_mak_default' => '521211',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nama_kegiatan' => 'Rapat Koordinasi',
                    'template_uraian_tugas' => 'Menghadiri rapat koordinasi terkait pelaksanaan kegiatan statistik.',
                    'template_uraian_laporan' => 'Rapat koordinasi telah dilaksanakan dan menghasilkan beberapa kesepakatan.',
                    'saran_mak_default' => '521211',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nama_kegiatan' => 'Supervisi dan Pemeriksaan',
                    'template_uraian_tugas' => 'Melaksanakan supervisi dan pemeriksaan hasil kegiatan lapangan.',
                    'template_uraian_laporan' => 'Supervisi dan pemeriksaan telah dilaksanakan, hasil kegiatan sesuai standar.',
                    'saran_mak_default' => '521211',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
