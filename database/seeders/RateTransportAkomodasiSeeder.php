<?php

namespace Database\Seeders;

use App\Models\RateAkomodasi;
use App\Models\RateTransport;
use Illuminate\Database\Seeder;

/**
 * Data resmi dari:
 * - SK KPA BPS Kab. Murung Raya Nomor 13 Tahun 2026 (Rate Akomodasi Dalam Kota)
 * - SK KPA BPS Kab. Murung Raya Nomor 14 Tahun 2026 (Rate Transport Perjalanan Dinas Dalam Kota)
 * Keduanya ditetapkan 7 Januari 2026 untuk Tahun Anggaran 2026.
 *
 * Level rate_transport mengikuti struktur Lampiran I SK Nomor 14:
 * A = Ibu Kota Kabupaten -> Ibu Kota Kecamatan
 * B = Ibu Kota Kecamatan -> Desa/Kelurahan
 * C = Ibu Kota Kabupaten -> Desa/Kelurahan (langsung)
 * Sebagian desa punya lebih dari satu baris (moda transportasi alternatif dengan
 * nilai berbeda) — semuanya disimpan apa adanya sesuai lampiran, bukan cuma nilai
 * termurah/utama, supaya datanya tetap bisa ditelusuri ke SK aslinya.
 */
class RateTransportAkomodasiSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRateAkomodasi();
        $this->seedRateTransportLevelA();
        $this->seedRateTransportLevelB();
        $this->seedRateTransportLevelC();
    }

    private function seedRateAkomodasi(): void
    {
        $data = [
            ['Permata Intan', 150000, null],
            ['Murung', 480000, null],
            ['Sumber Barito', 100000, null],
            ['Tanah Siang', 480000, 'Murung'],
            ['Tanah Siang Selatan', 480000, 'Murung'],
            ['Sungai Babuat', 150000, 'Permata Intan'],
            ['Laung Tuhup', 100000, null],
            ['Barito Tuhup Raya', 100000, 'Laung Tuhup'],
            ['Seribu Riam', 150000, null],
            ['Uut Murung', 120000, null],
        ];

        foreach ($data as [$kecamatan, $tarif, $keterangan]) {
            RateAkomodasi::updateOrCreate(
                ['nama_kecamatan' => $kecamatan],
                ['tarif_per_malam' => $tarif, 'keterangan' => $keterangan]
            );
        }
    }

    private function seedRateTransportLevelA(): void
    {
        $data = [
            ['Permata Intan', 300000, 'Perahu Besar'],
            ['Sungai Babuat', 500000, 'Ojek'],
            ['Murung', 100000, 'Ojek'],
            ['Laung Tuhup', 300000, 'Ojek'],
            ['Barito Tuhup Raya', 900000, 'Perahu Besar'],
            ['Tanah Siang', 300000, 'Ojek'],
            ['Tanah Siang Selatan', 250000, 'Ojek'],
            ['Sumber Barito', 600000, 'Speed Boat'],
            ['Seribu Riam', 6100000, 'Angkutan Desa'],
            ['Uut Murung', 5500000, 'Angkutan Desa'],
        ];

        foreach ($data as [$kecamatan, $nilai, $moda]) {
            $this->buatBaris('A', $kecamatan, null, [[$nilai, $moda]]);
        }
    }

    private function seedRateTransportLevelB(): void
    {
        $this->simpanKecamatan('B', [
            'Permata Intan' => [
                'Sungai Lobang' => [[500000, 'Ojek']],
                'Sungai Gula' => [[800000, 'Ojek']],
                'Sungai Batang' => [[400000, 'Ojek']],
                'Tumbang Salio' => [[900000, 'Ojek']],
                'Muara Bakanon' => [[350000, 'Perahu Kecil'], [350000, 'Ojek']],
                'Purnama' => [[220000, 'Perahu Kecil']],
                'Tumbang Lahung' => [[150000, 'Ojek']],
                'Muara Babuat' => [[360000, 'Perahu Kecil']],
                'Juking Supan' => [[400000, 'Perahu Kecil'], [750000, 'Ojek']],
                'Beratu' => [[600000, 'Perahu Kecil']],
                'Pantai Laga' => [[700000, 'Perahu Kecil']],
                'Sei Bakanon' => [[900000, 'Ojek']],
            ],
            'Sungai Babuat' => [
                'Batu Mirau' => [[250000, 'Ojek']],
                'Tumbang Bantian' => [[100000, 'Ojek']],
                'Tumbang Saan' => [[150000, 'Ojek']],
                'Tumbang Kolon' => [[250000, 'Ojek'], [800000, 'Perahu Kecil']],
                'Tambelum' => [[150000, 'Ojek'], [250000, 'Ojek']],
                'Tumbang Apat' => [[250000, 'Ojek'], [800000, 'Perahu Kecil']],
            ],
            'Murung' => [
                'Dirung' => [[600000, 'Ojek']],
                'Malasan' => [[600000, 'Ojek']],
                'Batu Putih' => [[500000, 'Ojek']],
                'Mangkahui' => [[400000, 'Ojek']],
                "Panu'ut" => [[400000, 'Ojek']],
                'Muara Untu' => [[200000, 'Ojek']],
                "Muara Ja'an" => [[150000, 'Ojek']],
                'Bahitom' => [[170000, 'Ojek']],
                'Danau Usung' => [[150000, 'Ojek']],
                'Juking Pajang' => [[120000, 'Ojek']],
                'Beriwit' => [[100000, 'Ojek']],
                'Puruk Cahu' => [[150000, 'Ojek']],
                'Muara Sumpoi' => [[170000, 'Ojek']],
                'Muara Bumban' => [[300000, 'Ojek']],
                'Penyang' => [[280000, 'Ojek'], [600000, 'Perahu Kecil']],
            ],
            'Laung Tuhup' => [
                'Batu Tuhup' => [[800000, 'Perahu Kecil']],
                'Tumbang Bahan' => [[700000, 'Perahu Kecil']],
                'Muara Laung I' => [[100000, 'Ojek']],
                'Muara Laung II' => [[120000, 'Ojek']],
                'Beras Belange' => [[300000, 'Ojek']],
                'Muara Tuhup' => [[150000, 'Ojek']],
                'Biha' => [[200000, 'Perahu Besar'], [700000, 'Perahu Kecil']],
                'Dirung Pundu' => [[220000, 'Perahu Besar'], [900000, 'Perahu Kecil']],
                'Pelaci' => [[400000, 'Perahu Besar']],
                'Dirung Pinang' => [[220000, 'Perahu Besar'], [900000, 'Perahu Kecil']],
                'Muara Tupuh' => [[320000, 'Perahu Besar'], [1050000, 'Perahu Kecil']],
                'Tawai Haui' => [[450000, 'Perahu Besar']],
                'Lakutan' => [[500000, 'Perahu Besar']],
                'Tumbang Bana' => [[1250000, 'Perahu Kecil']],
                'Narui' => [[1250000, 'Perahu Kecil']],
                'Muara Maruwei I' => [[500000, 'Perahu Kecil']],
                'Muara Maruwei II' => [[550000, 'Perahu Kecil']],
                'Penda Siron' => [[650000, 'Perahu Kecil']],
                'Batu Bua II' => [[600000, 'Perahu Besar']],
                'Batu Bua I' => [[600000, 'Perahu Besar']],
                'Tahujan Laung' => [[650000, 'Perahu Besar']],
                'Kalang Duhung' => [[1600000, 'Perahu Kecil']],
                'Beralang' => [[1600000, 'Perahu Kecil']],
                'Tumbang Bondang' => [[1100000, 'Perahu Kecil']],
                'Tumbang Tonduk' => [[1900000, 'Perahu Kecil']],
                'Batu Karang' => [[650000, 'Perahu Kecil']],
            ],
            'Barito Tuhup Raya' => [
                'Makunjung' => [[100000, 'Ojek']],
                'Cinta Budiman' => [[250000, 'Perahu Besar']],
                'Bumban Tuhup' => [[350000, 'Perahu Besar']],
                'Dirung Sararong' => [[1000000, 'Ojek']],
                'Kohong' => [[1000000, 'Ojek']],
                'Liang Nyaling' => [[1200000, 'Ojek']],
                'Tumbang Masalo' => [[1200000, 'Ojek']],
                'Tumbang Bauh' => [[1200000, 'Ojek']],
                'Hingan Tokung' => [[1100000, 'Ojek']],
                'Batu Tojah' => [[1300000, 'Ojek']],
                'Tumbang Baloi' => [[1400000, 'Ojek']],
            ],
            'Tanah Siang' => [
                'Sungai Lunuk' => [[180000, 'Ojek']],
                'Tino Talih' => [[200000, 'Ojek']],
                'Olong Nango' => [[250000, 'Ojek']],
                'Dirung Bakung' => [[200000, 'Ojek']],
                'Tabulang' => [[250000, 'Ojek']],
                'Mahanyan' => [[240000, 'Ojek']],
                'Olong Dojou' => [[240000, 'Ojek']],
                'Olong Siron' => [[250000, 'Ojek']],
                'Konut' => [[150000, 'Ojek']],
                'Belawan' => [[200000, 'Ojek']],
                'Mangkolisoi' => [[200000, 'Ojek']],
                'Kalang Kaluh' => [[250000, 'Ojek']],
                'Mantiat Pari' => [[150000, 'Ojek']],
                'Olong Ulu' => [[120000, 'Ojek']],
                'Saripoi' => [[120000, 'Ojek']],
                'Puruk Batu' => [[120000, 'Ojek']],
                'Doan Arung' => [[150000, 'Ojek']],
                'Cangkang' => [[250000, 'Ojek']],
                'Muwun' => [[120000, 'Ojek']],
                'Kolam' => [[250000, 'Ojek']],
                'Nonokaliwon' => [[700000, 'Ojek']],
                'Saruhung' => [[300000, 'Ojek']],
                'Olong Soloi' => [[350000, 'Ojek']],
                'Tokung' => [[700000, 'Ojek']],
                'Olong Balo' => [[700000, 'Ojek']],
                'Karali' => [[120000, 'Ojek']],
                'Osom Tompok' => [[180000, 'Ojek']],
            ],
            'Tanah Siang Selatan' => [
                'Oreng' => [[150000, 'Ojek']],
                'Olong Muro' => [[170000, 'Ojek']],
                'Olong Hanangan' => [[150000, 'Ojek']],
                'Dirung Lingkin' => [[100000, 'Ojek']],
                'Datah Kotou' => [[150000, 'Ojek']],
                'Tahujan Ontu' => [[200000, 'Ojek']],
                'Puruk Kambang' => [[100000, 'Ojek']],
            ],
            'Sumber Barito' => [
                'Kalapeh Baru' => [[750000, 'Perahu Kecil']],
                'Tumbang Masao' => [[650000, 'Perahu Kecil']],
                'Batu Makap' => [[900000, 'Perahu Kecil']],
                'Tumbang Kunyi' => [[100000, 'Ojek']],
                'Olong Liko' => [[500000, 'Perahu Kecil']],
                'Teluk Jolo' => [[700000, 'Perahu Kecil']],
                'Laas Baru' => [[900000, 'Perahu Kecil']],
                'Tumbang Tuan' => [[1100000, 'Perahu Kecil']],
                'Tumbang Molut' => [[700000, 'Perahu Kecil']],
            ],
            'Seribu Riam' => [
                'Muara Joloi I' => [[100000, 'Ojek']],
                'Muara Joloi II' => [[100000, 'Ojek']],
                'Parahau' => [[300000, 'Perahu Kecil']],
                'Tumbang Naan' => [[5000000, 'Angkutan Desa']],
                'Tumbang Tohan' => [[8000000, 'Angkutan Desa']],
                'Tumbang Jojang' => [[8600000, 'Angkutan Desa']],
                'Takajung' => [[4000000, 'Perahu Besar']],
            ],
            'Uut Murung' => [
                'Tumbang Olong' => [[100000, 'Ojek']],
                'Tumbang Olong II' => [[100000, 'Ojek']],
                'Kalasin' => [[3000000, 'Ojek']],
                'Tumbang Tujang' => [[6500000, 'Angkutan Desa']],
                'Tumbang Tupus' => [[14500000, 'Angkutan Desa']],
            ],
        ]);
    }

    private function seedRateTransportLevelC(): void
    {
        $this->simpanKecamatan('C', [
            'Permata Intan' => [
                'Sungai Lobang' => [[1100000, 'Ojek'], [940000, 'Speed Boat']],
                'Sungai Gula' => [[1200000, 'Ojek'], [1140000, 'Speed Boat']],
                'Sungai Batang' => [[1000000, 'Ojek'], [840000, 'Speed Boat']],
                'Tumbang Salio' => [[900000, 'Ojek']],
                'Muara Bakanon' => [[190000, 'Perahu Besar'], [340000, 'Speed Boat'], [750000, 'Ojek']],
                'Purnama' => [[220000, 'Perahu Besar'], [340000, 'Speed Boat']],
                'Tumbang Lahung' => [[240000, 'Perahu Besar'], [600000, 'Ojek'], [340000, 'Speed Boat']],
                'Muara Babuat' => [[340000, 'Perahu Besar'], [400000, 'Speed Boat']],
                'Juking Supan' => [[360000, 'Perahu Besar'], [450000, 'Speed Boat']],
                'Beratu' => [[380000, 'Perahu Besar'], [500000, 'Speed Boat']],
                'Pantai Laga' => [[400000, 'Perahu Besar'], [550000, 'Speed Boat']],
                'Sei Bakanon' => [[1100000, 'Ojek']],
            ],
            'Sungai Babuat' => [
                'Batu Mirau' => [[600000, 'Ojek']],
                'Tumbang Bantian' => [[600000, 'Ojek']],
                'Tumbang Saan' => [[650000, 'Ojek']],
                'Tumbang Kolon' => [[850000, 'Ojek'], [1400000, 'Perahu Kecil']],
                'Tambelum' => [[600000, 'Ojek']],
                'Tumbang Apat' => [[850000, 'Ojek'], [1400000, 'Perahu Kecil']],
            ],
            'Murung' => [
                'Dirung' => [[600000, 'Ojek']],
                'Malasan' => [[600000, 'Ojek']],
                'Batu Putih' => [[500000, 'Ojek']],
                'Mangkahui' => [[400000, 'Ojek']],
                "Panu'ut" => [[400000, 'Ojek']],
                'Muara Untu' => [[200000, 'Ojek']],
                "Muara Ja'an" => [[150000, 'Ojek']],
                'Bahitom' => [[170000, 'Ojek']],
                'Danau Usung' => [[150000, 'Ojek']],
                'Juking Pajang' => [[120000, 'Ojek']],
                'Beriwit' => [[100000, 'Ojek']],
                'Puruk Cahu' => [[150000, 'Ojek']],
                'Muara Sumpoi' => [[170000, 'Ojek']],
                'Muara Bumban' => [[300000, 'Ojek']],
                'Penyang' => [[280000, 'Ojek'], [600000, 'Perahu Kecil']],
            ],
            'Laung Tuhup' => [
                'Batu Tuhup' => [[150000, 'Perahu Besar'], [450000, 'Ojek'], [750000, 'Perahu Kecil']],
                'Tumbang Bahan' => [[200000, 'Perahu Besar'], [950000, 'Perahu Kecil']],
                'Muara Laung I' => [[300000, 'Ojek'], [250000, 'Perahu Besar']],
                'Muara Laung II' => [[320000, 'Ojek'], [250000, 'Perahu Besar']],
                'Beras Belange' => [[500000, 'Ojek'], [300000, 'Perahu Besar']],
                'Muara Tuhup' => [[400000, 'Ojek'], [350000, 'Perahu Besar']],
                'Biha' => [[900000, 'Perahu Kecil']],
                'Dirung Pundu' => [[1000000, 'Perahu Kecil']],
                'Pelaci' => [[900000, 'Perahu Kecil']],
                'Dirung Pinang' => [[1000000, 'Perahu Kecil']],
                'Muara Tupuh' => [[1150000, 'Perahu Kecil']],
                'Tawai Haui' => [[750000, 'Perahu Kecil']],
                'Lakutan' => [[900000, 'Perahu Kecil']],
                'Tumbang Bana' => [[1650000, 'Perahu Kecil']],
                'Narui' => [[1650000, 'Perahu Kecil']],
                'Muara Maruwei I' => [[1150000, 'Perahu Kecil']],
                'Muara Maruwei II' => [[1150000, 'Perahu Kecil']],
                'Penda Siron' => [[1300000, 'Perahu Kecil']],
                'Batu Bua II' => [[1250000, 'Perahu Kecil']],
                'Batu Bua I' => [[1250000, 'Perahu Kecil']],
                'Tahujan Laung' => [[1300000, 'Perahu Kecil']],
                'Kalang Duhung' => [[1800000, 'Perahu Kecil']],
                'Beralang' => [[1800000, 'Perahu Kecil']],
                'Tumbang Bondang' => [[1600000, 'Perahu Kecil']],
                'Tumbang Tonduk' => [[2200000, 'Perahu Kecil']],
                'Batu Karang' => [[1300000, 'Perahu Kecil']],
            ],
            'Barito Tuhup Raya' => [
                'Makunjung' => [[1100000, 'Perahu Besar']],
                'Cinta Budiman' => [[400000, 'Perahu Besar'], [1350000, 'Perahu Besar']],
                'Bumban Tuhup' => [[1600000, 'Perahu Besar'], [500000, 'Perahu Besar']],
                'Dirung Sararong' => [[940000, 'Ojek']],
                'Kohong' => [[940000, 'Ojek']],
                'Liang Nyaling' => [[1140000, 'Ojek']],
                'Tumbang Masalo' => [[1140000, 'Ojek']],
                'Tumbang Bauh' => [[1140000, 'Ojek']],
                'Hingan Tokung' => [[1040000, 'Ojek']],
                'Batu Tojah' => [[1340000, 'Ojek']],
                'Tumbang Baloi' => [[1540000, 'Ojek']],
            ],
            'Tanah Siang' => [
                'Sungai Lunuk' => [[120000, 'Ojek']],
                'Tino Talih' => [[140000, 'Ojek']],
                'Olong Nango' => [[140000, 'Ojek']],
                'Dirung Bakung' => [[180000, 'Ojek']],
                'Tabulang' => [[200000, 'Ojek']],
                'Mahanyan' => [[280000, 'Ojek']],
                'Olong Dojou' => [[260000, 'Ojek']],
                'Olong Siron' => [[160000, 'Ojek']],
                'Konut' => [[140000, 'Ojek']],
                'Belawan' => [[300000, 'Ojek']],
                'Mangkolisoi' => [[300000, 'Ojek']],
                'Kalang Kaluh' => [[320000, 'Ojek']],
                'Mantiat Pari' => [[260000, 'Ojek']],
                'Olong Ulu' => [[240000, 'Ojek']],
                'Saripoi' => [[300000, 'Ojek']],
                'Puruk Batu' => [[240000, 'Ojek']],
                'Doan Arung' => [[160000, 'Ojek']],
                'Cangkang' => [[300000, 'Ojek']],
                'Muwun' => [[240000, 'Ojek']],
                'Kolam' => [[400000, 'Ojek']],
                'Nonokaliwon' => [[500000, 'Ojek']],
                'Saruhung' => [[450000, 'Ojek']],
                'Olong Soloi' => [[500000, 'Ojek']],
                'Tokung' => [[900000, 'Ojek']],
                'Olong Balo' => [[800000, 'Ojek']],
                'Karali' => [[160000, 'Ojek']],
                'Osom Tompok' => [[300000, 'Ojek']],
            ],
            'Tanah Siang Selatan' => [
                'Oreng' => [[400000, 'Ojek']],
                'Olong Muro' => [[400000, 'Ojek']],
                'Olong Hanangan' => [[200000, 'Ojek']],
                'Dirung Lingkin' => [[300000, 'Ojek']],
                'Datah Kotou' => [[170000, 'Ojek']],
                'Tahujan Ontu' => [[120000, 'Ojek']],
                'Puruk Kambang' => [[350000, 'Ojek']],
            ],
            'Sumber Barito' => [
                'Kalapeh Baru' => [[500000, 'Speed Boat'], [400000, 'Perahu Besar']],
                'Tumbang Masao' => [[520000, 'Speed Boat'], [420000, 'Perahu Besar']],
                'Batu Makap' => [[550000, 'Speed Boat'], [440000, 'Perahu Besar']],
                'Tumbang Kunyi' => [[600000, 'Speed Boat'], [480000, 'Perahu Besar']],
                'Olong Liko' => [[630000, 'Speed Boat'], [500000, 'Perahu Besar']],
                'Teluk Jolo' => [[650000, 'Speed Boat'], [520000, 'Perahu Besar']],
                'Laas Baru' => [[670000, 'Speed Boat']],
                'Tumbang Tuan' => [[800000, 'Speed Boat'], [600000, 'Perahu Besar']],
                'Tumbang Molut' => [[740000, 'Speed Boat'], [560000, 'Perahu Besar']],
            ],
            'Seribu Riam' => [
                'Muara Joloi I' => [[6100000, 'Angkutan Desa']],
                'Muara Joloi II' => [[6100000, 'Angkutan Desa']],
                'Parahau' => [[6400000, 'Angkutan Desa dan Perahu Kecil']],
                'Tumbang Naan' => [[8100000, 'Angkutan Desa dan Perahu Besar'], [9600000, 'Angkutan Desa dan Perahu Besar']],
                'Tumbang Tohan' => [[10600000, 'Angkutan Desa dan Perahu Besar']],
                'Tumbang Jojang' => [[12000000, 'Angkutan Desa'], [14500000, 'Angkutan Desa']],
                'Takajung' => [[7000000, 'Angkutan Desa'], [9000000, 'Angkutan Desa dan Perahu Kecil']],
            ],
            'Uut Murung' => [
                'Tumbang Olong' => [[6000000, 'Angkutan Desa']],
                'Tumbang Olong II' => [[6000000, 'Angkutan Desa']],
                'Kalasin' => [[7500000, 'Angkutan Desa']],
                'Tumbang Tujang' => [[10000000, 'Angkutan Desa dan Perahu Kecil']],
                'Tumbang Tupus' => [[18000000, 'Angkutan Desa dan Perahu Kecil']],
            ],
        ]);
    }

    /**
     * @param array<string, array<string, array<array{0:int,1:string}>>> $perKecamatan
     */
    private function simpanKecamatan(string $level, array $perKecamatan): void
    {
        foreach ($perKecamatan as $kecamatan => $daftarDesa) {
            foreach ($daftarDesa as $desa => $baris) {
                $this->buatBaris($level, $desa, $kecamatan, $baris);
            }
        }
    }

    /**
     * Sebagian desa punya lebih dari satu baris dengan moda yang SAMA tapi nilai
     * berbeda (mis. dua rute Ojek berbeda musim) — jadi tidak bisa dibedakan lewat
     * updateOrCreate berbasis moda. Supaya seeder tetap aman dijalankan berulang
     * tanpa data dobel, baris lama untuk wilayah ini dihapus dulu baru diisi ulang
     * persis sesuai lampiran SK.
     *
     * @param array<array{0:int,1:string}> $baris
     */
    private function buatBaris(string $level, string $namaWilayah, ?string $kecamatanInduk, array $baris): void
    {
        RateTransport::where('level', $level)
            ->where('nama_wilayah', $namaWilayah)
            ->where('kecamatan_induk', $kecamatanInduk)
            ->delete();

        foreach ($baris as [$nilai, $moda]) {
            RateTransport::create([
                'level' => $level,
                'nama_wilayah' => $namaWilayah,
                'kecamatan_induk' => $kecamatanInduk,
                'moda_transportasi' => $moda,
                'nilai_pp' => $nilai,
            ]);
        }
    }
}
