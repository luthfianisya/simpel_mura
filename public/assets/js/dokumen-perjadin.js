/**
 * Modul render dokumen perjalanan dinas (Surat Tugas, SPD, Rincian Biaya, dst).
 * Semua fungsi render bersifat murni: menerima objek `data`, mengembalikan HTML string.
 * Dipakai oleh wizard (data dari form live) dan modal "Lihat/Export" di dashboard (data dari record tersimpan).
 *
 * Bentuk `data` yang diharapkan:
 * {
 *   no_surat_tugas, tanggal_surat_tugas, perihal, uraian_tugas,
 *   tanggal_mulai, tanggal_selesai, pembebanan, jenis_perjadin, tingkat_biaya,
 *   desa_asal, kabupaten_asal, desa_tujuan, kabupaten_tujuan,
 *   no_spd, tanggal_spd, angkutan, tanggal_kuitansi,
 *   jenis_kegiatan_nama, kesimpulan_hasil_kegiatan, tindak_lanjut,
 *   pelaksana: {nama, nip, jabatan, pangkat, golongan},
 *   penandatangan: {...}, ppk: {...}, bendahara: {...},
 *   rincian: [{jenis_komponen, nominal}], pengeluaran: [{uraian, jenis, nominal}],
 *   pernyataan: [{jenis_kondisi, keterangan}]
 * }
 */
(function (window) {
    'use strict';

    const bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const satkerLabel = '668650 - Badan Pusat Statistik Kabupaten Murung Raya';
    // URL absolut (bukan path relatif) karena logo ini juga harus tampil di jendela
    // export PDF (origin kosong/about:blank) dan file Word/Excel yang dibuka aplikasi
    // Office di luar browser — keduanya tidak bisa resolve path relatif. Pakai .png
    // (bukan .webp) karena renderer HTML Word/Excel versi lama sering tidak
    // mendukung WebP dan menampilkan ikon gambar rusak.
    const logoUrl = window.location.origin + '/assets/img/branding/logo-bps.png';

    const komponenLabel = {
        uang_harian: 'Uang Harian',
        transport: 'Transport',
        penginapan: 'Penginapan',
    };

    const kondisiLabel = {
        tidak_pakai_kendaraan_dinas: 'Tidak Menggunakan Kendaraan Dinas',
        tidak_menginap_hotel: 'Tidak Menginap di Hotel/Akomodasi',
        keterlambatan: 'Keterlambatan Pengajuan Tagihan',
    };

    // Kecamatan -> daftar desa/kelurahan Kabupaten Murung Raya (lihat config/wilayah.php,
    // sumber sama). Dipakai untuk mencari kecamatan dari nama desa/kel. tujuan yang dipilih
    // di wizard, supaya baris Transport bisa ditulis "ke kecamatan X" saat tujuannya lebih
    // dari satu desa/kelurahan.
    const kecamatanMurungRaya = {
        'Barito Tuhup Raya': ['Batu Tojah', 'Bumban Tuhup', 'Cinta Budiman', 'Dirung Sararung', 'Hingan Tokung', 'Kohong', 'Liang Nyaling', 'Makunjung', 'Tumbang Baloi', 'Tumbang Bauh', 'Tumbang Masalo'],
        'Laung Tuhup': ['Batu Bua II', 'Batu Tuhup', 'Beralang', 'Beras Balange', 'Biha', 'Dirung Derarung', 'Dirung Pinang', 'Dirung Pundu', 'Kalang Dohong', 'Lakutan', 'Muara Laung II', 'Muara Maruwei I', 'Muara Maruwei II', 'Muara Tupuh', 'Narui', 'Pelaci', 'Penda Siron', 'Tahujan Laung', 'Tawai Haui', 'Tumbang Bahan', 'Tumbang Bana', 'Tumbang Bondang', 'Tumbang Tonduk', 'Batu Bua I', 'Muara Laung I', 'Muara Tuhup'],
        'Murung': ['Bahitom', 'Batu Putih', 'Danau Usung', 'Dirung', 'Juking Pajang', 'Malasan', 'Mangkahui', 'Muara Bumban', 'Muara Jaan', 'Muara Sumpoi', 'Muara Untu', "Panu'ut", 'Penyang', 'Beriwit', 'Puruk Cahu'],
        'Permata Intan': ['Baratu', 'Juking Sopan', 'Muara Babuat', 'Pantai Laga', 'Purnama', 'Sungai Bakanon', 'Sungai Batang', 'Sungai Gula', 'Sungai Lobang', 'Tumbang Salio', 'Muara Bakanon', 'Tumbang Lahung'],
        'Seribu Riam': ['Muara Joloi I', 'Muara Joloi II', 'Parahau', 'Takajung', 'Tumbang Jojang', 'Tumbang Naan', 'Tumbang Tohan'],
        'Sumber Barito': ['Batu Makap', 'Kelapeh Baru', "La'as Baru", 'Olung Liko', 'Teluk Jolo', 'Tumbang Masao', 'Tumbang Molut', 'Tumbang Tuan', 'Tumbang Kunyi'],
        'Sungai Babuat': ['Batu Mirau', 'Tambelum', 'Tumbang Apat', 'Tumbang Bantian', 'Tumbang Kolon', "Tumbang Sa'an"],
        'Tanah Siang': ['Belawan', 'Cangkang', 'Dirung Bakung', 'Doan Arung', 'Kalang Kaluh', 'Karali', 'Kolam', 'Konut', 'Mahanyan', 'Mangkolisoi', 'Mantiat Pari', 'Muwun', 'Nono Kliwon', 'Olung Balo', 'Olung Baloi', 'Olung Dojou', 'Olung Nango', 'Olung Siron', 'Olung Ulu', 'Osom Tompok', 'Puruk Batu', 'Saruhung', 'Sungai Lunuk', 'Tabulang', 'Tinotalih', 'Tokung', 'Saripoi'],
        'Tanah Siang Selatan': ['Datah Kotou', 'Dirung Lingkin', 'Olung Hanangan', 'Olung Muro', 'Oreng', 'Tahujan Ontu'],
        'Uut Murung': ['Kalasin', 'Tumbang Olong', 'Tumbang Olong II', 'Tumbang Topus', 'Tumbang Tujang'],
    };

    function parseDaftarDenganDan(str) {
        if (!str) return [];
        return String(str).replace(/,?\s*dan\s+/i, ', ').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    }

    function cariKecamatan(namaDesa) {
        const nama = String(namaDesa || '').replace(/^(Desa|Kelurahan)\s+/i, '').trim();
        for (const kecamatan in kecamatanMurungRaya) {
            if (kecamatanMurungRaya[kecamatan].indexOf(nama) !== -1) return kecamatan;
        }
        return null;
    }

    // Tujuan Transport: kalau desa/kel. tujuan yang dipilih lebih dari satu, tulis nama
    // kecamatannya saja ("ke kecamatan X") alih-alih daftar desa yang panjang.
    function formatTujuanTransport(desaTujuan) {
        const daftar = parseDaftarDenganDan(desaTujuan);
        if (daftar.length > 1) {
            const kecamatan = cariKecamatan(daftar[0]);
            if (kecamatan) return 'kecamatan ' + kecamatan;
        }
        return desaTujuan || '...';
    }

    function pegawaiKosong() {
        return { nama: '', nip: '', jabatan: '', pangkat: '', golongan: '' };
    }

    // Perjalanan dinas "biasa" (antar kabupaten/kota) tidak mengisi Desa/Kel. asal
    // & tujuan (tidak relevan) — dokumen jatuh balik ke Kabupaten/Kota sebagai
    // referensi wilayah asal/tujuan supaya tidak tampil kosong/titik-titik.
    function wilayahAsal(data) {
        return data.desa_asal || data.kabupaten_asal || '';
    }

    function wilayahTujuan(data) {
        return data.desa_tujuan || data.kabupaten_tujuan || '';
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function fmtNip(nip) {
        return nip ? nip : '-';
    }

    function fmtTanggalPanjang(v) {
        if (!v) return '.......................';
        const d = new Date(v + 'T00:00:00');
        if (isNaN(d.getTime())) return v;
        return d.getDate() + ' ' + bulanIndo[d.getMonth()] + ' ' + d.getFullYear();
    }

    // Tambah N hari kerja (Senin-Jumat, akhir pekan dilompati) dari sebuah tanggal
    // "Y-m-d" — dipakai untuk tanggal penandatanganan Laporan (H+1 hari kerja dari
    // tanggal selesai pelaksanaan di Surat Tugas).
    function tambahHariKerja(tanggalYmd, jumlahHariKerja) {
        if (!tanggalYmd) return '';
        const d = new Date(tanggalYmd + 'T00:00:00');
        if (isNaN(d.getTime())) return '';
        let sisa = jumlahHariKerja;
        while (sisa > 0) {
            d.setDate(d.getDate() + 1);
            const hari = d.getDay();
            if (hari !== 0 && hari !== 6) sisa--;
        }
        const pad = (n) => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function fmtRentangTanggal(mulai, selesai) {
        if (!mulai) return '...........................';
        const d1 = new Date(mulai + 'T00:00:00');
        if (isNaN(d1.getTime())) return mulai;
        if (!selesai || selesai === mulai) return fmtTanggalPanjang(mulai);
        const d2 = new Date(selesai + 'T00:00:00');
        if (isNaN(d2.getTime())) return fmtTanggalPanjang(mulai) + ' s.d. ' + fmtTanggalPanjang(selesai);
        if (d1.getMonth() === d2.getMonth() && d1.getFullYear() === d2.getFullYear()) {
            return d1.getDate() + ' – ' + d2.getDate() + ' ' + bulanIndo[d2.getMonth()] + ' ' + d2.getFullYear();
        }
        return fmtTanggalPanjang(mulai) + ' s.d. ' + fmtTanggalPanjang(selesai);
    }

    function hitungLamaHari(mulai, selesai) {
        if (!mulai || !selesai) return 0;
        const d1 = new Date(mulai + 'T00:00:00');
        const d2 = new Date(selesai + 'T00:00:00');
        if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return 0;
        return Math.max(1, Math.round((d2 - d1) / 86400000) + 1);
    }

    function fmtRupiah(n) {
        n = parseFloat(n) || 0;
        return 'Rp. ' + n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function terbilang(n) {
        n = Math.floor(Math.abs(n)) || 0;
        const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        function convert(num) {
            if (num < 12) return satuan[num];
            if (num < 20) return convert(num - 10) + ' belas';
            if (num < 100) return convert(Math.floor(num / 10)) + ' puluh' + (num % 10 ? ' ' + convert(num % 10) : '');
            if (num < 200) return 'seratus' + (num % 100 ? ' ' + convert(num % 100) : '');
            if (num < 1000) return convert(Math.floor(num / 100)) + ' ratus' + (num % 100 ? ' ' + convert(num % 100) : '');
            if (num < 2000) return 'seribu' + (num % 1000 ? ' ' + convert(num % 1000) : '');
            if (num < 1000000) return convert(Math.floor(num / 1000)) + ' ribu' + (num % 1000 ? ' ' + convert(num % 1000) : '');
            if (num < 1000000000) return convert(Math.floor(num / 1000000)) + ' juta' + (num % 1000000 ? ' ' + convert(num % 1000000) : '');
            return convert(Math.floor(num / 1000000000)) + ' miliar' + (num % 1000000000 ? ' ' + convert(num % 1000000000) : '');
        }
        return n === 0 ? 'nol' : convert(n).trim();
    }

    function terbilangHari(n) {
        return n + ' (' + terbilang(n) + ') hari';
    }

    function terbilangRupiah(n) {
        const t = terbilang(n);
        return t.charAt(0).toUpperCase() + t.slice(1) + ' rupiah';
    }

    // "nominal" di form/data rincian itu RATE per hari/malam (mis. Rp 140.000/hari),
    // BUKAN total keseluruhan — totalnya = rate x jumlah hari/malam, lihat pemakaian di
    // renderRincianBiaya. Ini sudah konsisten sama fitur rekomendasi akomodasi (yang
    // nyaranin tarif per malam dari rate_akomodasi langsung ke field ini).
    function rateRincian(data, jenisKomponen) {
        return (data.rincian || [])
            .filter(function (r) { return r.jenis_komponen === jenisKomponen; })
            .reduce(function (sum, r) { return sum + (parseFloat(r.nominal) || 0); }, 0);
    }

    // Jumlah hari/malam per komponen (Uang Harian/Transport/Penginapan) bisa beda-beda,
    // tidak selalu sama dengan lama perjalanan dinas total — makanya diisi manual per
    // komponen di form, bukan cuma dihitung otomatis dari tanggal_mulai/tanggal_selesai.
    function hariRincian(data, jenisKomponen) {
        const row = (data.rincian || []).find(function (r) { return r.jenis_komponen === jenisKomponen; });
        const hari = row ? parseInt(row.jumlah_hari, 10) : NaN;
        return isNaN(hari) ? null : hari;
    }

    function jumlahPengeluaran(data) {
        return (data.pengeluaran || []).reduce(function (sum, p) { return sum + (parseFloat(p.nominal) || 0); }, 0);
    }

    function spdAtauSt(data) {
        return {
            nomor: data.no_spd || data.no_surat_tugas || '...........................',
            tanggal: data.no_spd ? data.tanggal_spd : data.tanggal_surat_tugas,
        };
    }

    // ----- Surat Tugas -----
    function renderSuratTugas(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const penandatangan = data.penandatangan || pegawaiKosong();
        const tahun = (data.tanggal_surat_tugas || '').slice(0, 4) || '.....';
        return `
            <div style="font-family:'Bookman Old Style', serif; font-size:12pt;">
            <div class="text-center mb-3">
                <img src="${logoUrl}" alt="Logo BPS" style="width:70px;height:auto;">
                <div class="fw-bold fst-italic" style="font-family:Arial, sans-serif; font-size:12pt; line-height:1.3; margin-top:0.25rem;">
                    BADAN PUSAT STATISTIK<br>KABUPATEN MURUNG RAYA
                </div>
            </div>
            <div class="dokumen-title" style="text-decoration:none;">SURAT TUGAS</div>
            <div class="dokumen-nomor">NOMOR ${esc(data.no_surat_tugas) || '...........................'}</div>
            <table class="dokumen-table">
                <tr>
                    <td width="14%" style="vertical-align:top;">Menimbang</td>
                    <td width="3%" style="vertical-align:top;">:</td>
                    <td style="vertical-align:top;">
                        <ol type="a" class="dokumen-list">
                            <li>Bahwa dalam rangka sebagai wujud komitmen Badan Pusat Statistik sebagai penyedia data yang berkualitas;</li>
                            <li>Bahwa untuk memenuhi ketentuan butir a, maka perlu menugaskan pegawai tersebut dalam Surat Tugas ini untuk melakukan kegiatan tersebut;</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align:top;">Mengingat</td>
                    <td style="vertical-align:top;">:</td>
                    <td style="vertical-align:top;">
                        <ol class="dokumen-list">
                            <li>Undang-Undang No.16 Tahun 1997 tentang Statistik;</li>
                            <li>Peraturan Pemerintah No. 51 Tahun 1999 tentang Pedoman Penyelenggaraan Statistik;</li>
                            <li>Keputusan Presiden RI No. 72 Tahun 2004 tentang Pedoman Pelaksanaan APBN;</li>
                            <li>Keputusan Presiden Nomor 1 Tahun 2025 tentang Badan Pusat Statistik;</li>
                            <li>Keputusan Presiden RI No. 103 Tahun 2001 tentang Kedudukan, Tugas, Fungsi, Kewenangan, Susunan Organisasi dan Tata Cara Kerja Pemerintah Non Departemen;</li>
                            <li>Peraturan Menteri Keuangan No. 113/PMK.05/2012 tentang Perjalanan Dinas Dalam Negeri Bagi Pejabat Negara, Pegawai Negeri, dan Pegawai Tidak Tetap (Berita Negara Republik Indonesia Tahun 2011 Nomor 678);</li>
                            <li>Peraturan Kepala Badan Pusat Statistik No. 103 tahun 2014 tentang pelaksanaan Perjalanan dinas jabatan di lingkungan Badan Pusat Statistik;</li>
                            <li>Semua biaya yang timbul dengan diterbitkannya Surat Tugas ini dibebankan kepada DIPA BPS Kabupaten Murung Raya TA ${tahun}.</li>
                        </ol>
                    </td>
                </tr>
            </table>
            <p class="text-center fw-bold">Memberi Perintah</p>
            <table class="dokumen-table">
                <tr>
                    <td width="14%">Kepada</td>
                    <td width="3%">:</td>
                    <td>${esc(pelaksana.nama) || '...........................'}, ${esc(pelaksana.jabatan) || '-'}</td>
                </tr>
                <tr>
                    <td>Untuk</td>
                    <td>:</td>
                    <td>${esc(data.perihal) || '...........................'}${wilayahTujuan(data) ? ' di ' + esc(wilayahTujuan(data)) : ''}, tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}</td>
                </tr>
                <tr>
                    <td>Pembebanan</td>
                    <td>:</td>
                    <td>${esc(data.pembebanan) || '...........................'}</td>
                </tr>
                <tr><td>&nbsp;</td><td></td><td></td></tr>
            </table>
            <div class="ttd-block">
                <div>Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_surat_tugas)}</div>
                <div>Kepala Badan Pusat Statistik</div>
                <div>Kabupaten Murung Raya</div>
                <div class="mt-5 fw-bold text-decoration-underline">${esc(penandatangan.nama) || '(...........................)'}</div>
            </div>
            </div>
        `;
    }

    // ----- SPD -----
    function renderSpd(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const ppk = data.ppk || pegawaiKosong();
        const lama = hitungLamaHari(data.tanggal_mulai, data.tanggal_selesai);
        return `
            <div style="font-family:'Bookman Old Style', serif; font-size:11pt;">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-2">
                    <img src="${logoUrl}" alt="Logo BPS" style="width:60px;height:auto;">
                    <div class="fw-bold fst-italic" style="font-family:Arial, sans-serif; font-size:12pt; line-height:1.3;">
                        BADAN PUSAT STATISTIK<br>KABUPATEN MURUNG RAYA
                    </div>
                </div>
                <div class="text-end" style="font-size:0.8rem;">
                    <div>Nomor &nbsp;: ${esc(data.no_spd) || '...........................'}</div>
                    <div>Lembar &nbsp;: 1</div>
                </div>
            </div>
            <table class="dokumen-table dokumen-rincian mt-3">
                <tr><td colspan="3" class="text-center" style="font-size:18pt; font-weight:normal;">SURAT PERJALANAN DINAS</td></tr>
                <tr><td width="5%">1</td><td width="40%">Pejabat Pembuat Komitmen</td><td>${esc(ppk.nama) || '...'}</td></tr>
                    <tr><td>2</td><td>Nama/NIP Pegawai yang melaksanakan perjalanan dinas</td><td>${esc(pelaksana.nama) || '...'} / <br>NIP. ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td rowspan="3">3</td><td>a. Pangkat/Golongan</td><td>${esc(pelaksana.pangkat) || '-'}${pelaksana.golongan ? ' / ' + esc(pelaksana.golongan) : ''}</td></tr>
                    <tr><td>b. Jabatan</td><td>${esc(pelaksana.jabatan) || '-'}</td></tr>
                    <tr><td>c. Tingkat Biaya Perjalanan Dinas</td><td>${esc(data.tingkat_biaya) || '-'}</td></tr>
                    <tr><td>4</td><td>Maksud Perjalanan Dinas</td><td>${esc(data.perihal) || '...'}</td></tr>
                    <tr><td>5</td><td>Alat angkutan yang digunakan</td><td>${esc(data.angkutan) || '...'}</td></tr>
                    <tr><td rowspan="2">6</td><td>a. Tempat Berangkat</td><td>${esc(wilayahAsal(data)) || '...'}</td></tr>
                    <tr><td>b. Tempat Tujuan</td><td>${esc(wilayahTujuan(data)) || '...'}</td></tr>
                    <tr><td rowspan="3">7</td><td>a. Lamanya Perjalanan Dinas</td><td>${terbilangHari(lama)}</td></tr>
                    <tr><td>b. Tanggal Berangkat</td><td>${fmtTanggalPanjang(data.tanggal_mulai)}</td></tr>
                    <tr><td>c. Tanggal harus kembali</td><td>${fmtTanggalPanjang(data.tanggal_selesai)}</td></tr>
                    <tr><td>8</td><td colspan="2">
                        Pengikut :
                        <table class="dokumen-table dokumen-rincian mt-1" style="font-size:0.75rem;">
                            <thead><tr><th>Nama</th><th>Tanggal Lahir</th><th>Keterangan</th></tr></thead>
                            <tbody>
                            <tr><td>1. -</td><td>-</td><td>-</td></tr>
                            <tr><td>2. -</td><td>-</td><td>-</td></tr>
                            </tbody>
                        </table>
                    </td></tr>
                    <tr><td rowspan="2">9</td><td>a. Instansi</td><td>Badan Pusat Statistik Kabupaten Murung Raya</td></tr>
                    <tr><td>b. Program/Kegiatan/Output/(MAK)</td><td>${esc(data.pembebanan) || '...'}</td></tr>
                    <tr><td>10</td><td>Keterangan Lain-Lain</td><td>Surat Tugas Nomor ${esc(data.no_surat_tugas) || '...'}<br>tanggal ${fmtTanggalPanjang(data.tanggal_surat_tugas)}</td></tr>
            </table>
            <div class="ttd-block">
                <div style="text-align:left;">
                    <div>Dikeluarkan di : Puruk Cahu</div>
                    <div>Pada Tanggal &nbsp;&nbsp;: ${fmtTanggalPanjang(data.tanggal_spd)}</div>
                </div>
                <div>Pejabat Pembuat Komitmen,</div>
                <div class="mt-5 fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</div>
                <div>NIP. ${esc(fmtNip(ppk.nip))}</div>
            </div>
            </div>
        `;
    }

    // ----- Rincian Biaya -----
    function renderRincianBiaya(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const ppk = data.ppk || pegawaiKosong();
        const bendahara = data.bendahara || pegawaiKosong();
        const lama = hitungLamaHari(data.tanggal_mulai, data.tanggal_selesai);
        const malam = Math.max(lama - 1, 0);

        // Jumlah hari/malam per komponen bisa beda-beda dari lama perjalanan dinas total
        // (mis. penginapan cuma 3 malam meski perjalanan 5 hari) — pakai yang diisi manual
        // kalau ada, baru fallback ke hitungan otomatis dari tanggal_mulai/tanggal_selesai.
        const hariUangHarian = hariRincian(data, 'uang_harian') ?? lama;
        const hariTransport = hariRincian(data, 'transport') ?? lama;
        const malamPenginapan = hariRincian(data, 'penginapan') ?? malam;

        // Nominal yang diisi user = rate per hari/malam; totalnya dihitung di sini
        // (rate x jumlah hari/malam), bukan diketik langsung sebagai angka jadi.
        const rateHarian = rateRincian(data, 'uang_harian');
        const rateTransport = rateRincian(data, 'transport');
        const rateMalam = rateRincian(data, 'penginapan');

        const uangHarian = rateHarian * hariUangHarian;
        const transport = rateTransport * hariTransport;
        const penginapan = rateMalam * malamPenginapan;
        const pengeluaranTotal = jumlahPengeluaran(data);

        const total = uangHarian + transport + penginapan + pengeluaranTotal;
        const ref = spdAtauSt(data);

        return `
            <div style="font-family:Arial, sans-serif; font-size:9.5pt;">
            <div class="dokumen-title" style="text-decoration:none; font-size:14pt; font-weight:normal;">PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS</div>
            <table class="dokumen-table mt-3">
                <tr><td width="28%">Lampiran SPD Nomor</td><td>: ${esc(ref.nomor)}</td></tr>
                <tr><td>Tanggal</td><td>: ${fmtTanggalPanjang(ref.tanggal)}</td></tr>
            </table>
            <table class="dokumen-table dokumen-rincian">
                <thead>
                <tr>
                    <th width="4%">No</th>
                    <th colspan="3">Perincian Biaya</th>
                    <th width="18%">Jumlah</th>
                    <th width="13%">Keterangan</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td rowspan="4">1</td>
                    <td width="27%" class="bd-r-none">a. Nama yang bertugas</td>
                    <td width="1%" class="bd-l-none bd-r-none">:</td>
                    <td class="bd-l-none">${esc(pelaksana.nama) || '...'}</td>
                    <td></td>
                    <td rowspan="9"></td>
                </tr>
                <tr><td class="bd-r-none">b. Pangkat/Gol</td><td class="bd-l-none bd-r-none">:</td><td class="bd-l-none">${esc(pelaksana.pangkat) || '-'}${pelaksana.golongan ? ' / ' + esc(pelaksana.golongan) : ''}</td><td></td></tr>
                <tr><td class="bd-r-none">c. Tujuan tugas</td><td class="bd-l-none bd-r-none">:</td><td class="bd-l-none">${esc(data.kabupaten_tujuan) || '...'}</td><td></td></tr>
                <tr><td class="bd-r-none">d. Lamanya tugas</td><td class="bd-l-none bd-r-none">:</td><td class="bd-l-none">${terbilangHari(lama)}</td><td></td></tr>
                <tr><td>2</td><td class="bd-r-none">Transport</td><td class="bd-l-none bd-r-none">:</td><td class="bd-l-none">${terbilangHari(hariTransport)}, dari ${esc(wilayahAsal(data)) || '...'} ke ${esc(formatTujuanTransport(wilayahTujuan(data)))}</td><td class="text-end">${transport > 0 ? fmtRupiah(transport) : 'Rp. -'}</td></tr>
                <tr><td>3</td><td class="bd-r-none">Uang harian perjadin</td><td class="bd-l-none bd-r-none">:</td><td class="bd-l-none">${terbilangHari(hariUangHarian)} x ${fmtRupiah(rateHarian)},-</td><td class="text-end">${uangHarian > 0 ? fmtRupiah(uangHarian) : 'Rp. -'}</td></tr>
                <tr><td>4</td><td class="bd-r-none">Penginapan</td><td class="bd-l-none bd-r-none">:</td><td class="bd-l-none">${malamPenginapan} (${terbilang(malamPenginapan)}) malam x ${fmtRupiah(rateMalam)},-</td><td class="text-end">${penginapan > 0 ? fmtRupiah(penginapan) : 'Rp. -'}</td></tr>
                <tr><td>5</td><td class="bd-r-none">Pengeluaran Rill</td><td class="bd-l-none bd-r-none">:</td><td class="bd-l-none"></td><td class="text-end">${pengeluaranTotal > 0 ? fmtRupiah(pengeluaranTotal) : 'Rp. -'}</td></tr>
                <tr><td></td><td class="bd-r-none"></td><td class="bd-l-none bd-r-none"></td><td class="bd-l-none fw-bold">JUMLAH</td><td class="text-end fw-bold">${fmtRupiah(total)}</td></tr>
                <tr><td></td><td colspan="5" class="text-center fw-bold fst-italic">** ${terbilangRupiah(total)} **</td></tr>
                <tr>
                    <td colspan="3" style="border-right:none; border-bottom:none;"></td>
                    <td colspan="3" class="text-end" style="border-left:none; border-bottom:none;">Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}</td>
                </tr>
                <tr>
                    <td colspan="3" style="border-right:none; border-top:none; border-bottom:none;">Telah dibayar sejumlah:<br><span class="fst-italic">${fmtRupiah(total)}</span></td>
                    <td colspan="3" style="border-left:none; border-top:none; border-bottom:none;"><div style="margin-left:auto; width:60%;">Telah menerima jumlah uang sebesar:<br><span class="fst-italic">${fmtRupiah(total)}</span></div></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-center" style="border-right:none; border-top:none;">
                        Lunas pada tanggal:<br><br>
                        Bendahara Pengeluaran/PUMC<br>BPS Kabupaten Murung Raya<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(bendahara.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(bendahara.nip))}
                    </td>
                    <td colspan="3" style="border-left:none; border-top:none;">
                        <div style="margin-left:auto; width:60%; text-align:center;">
                            <br><br>Yang Menerima,<br><br><br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(pelaksana.nip))}
                        </div>
                    </td>
                </tr>
                <tr><td colspan="6" class="text-center fw-bold" style="border-bottom:none;">PERHITUNGAN SPD RAMPUNG</td></tr>
                <tr>
                    <td colspan="3" style="border-right:none; border-top:none;">
                        <span style="display:inline-block; width:170px;">Ditetapkan sejumlah</span>:<br>
                        <span style="display:inline-block; width:170px;">Yang telah dibayar semula</span>:<br>
                        <span style="display:inline-block; width:170px;">Sisa kurang / lebih</span>:
                    </td>
                    <td class="text-end" style="border-left:none; border-right:none; border-top:none;">
                        ${fmtRupiah(total)}<br>
                        Rp. 0,-<br>
                        <span style="display:block; border-top:1px solid #000; padding-top:2px;">${fmtRupiah(total)}</span>
                    </td>
                    <td colspan="2" style="border-left:none; border-top:none;"></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2" class="text-center">
                        Pejabat Pembuat Komitmen<br>BPS Kabupaten Murung Raya<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(ppk.nip))}
                    </td>
                </tr>
                </tbody>
            </table>
            </div>
        `;
    }

    // ----- Pengeluaran Riil -----
    const skPengeluaranLabel = {
        transportasi: 'Sesuai SK Kuasa Pengguna Anggaran BPS Kabupaten Murung Raya Nomor 14 Tahun 2026 Tanggal 7 Januari 2026 Tentang Besaran Biaya Transport Perjalanan Dinas Dalam Kota Tahun Anggaran',
        akomodasi: 'Sesuai SK Kuasa Pengguna Anggaran BPS Kabupaten Murung Raya Nomor 13 Tahun 2026 Tanggal 7 Januari 2026 Tentang Besaran Biaya Rate Akomodasi Dalam Kota Tahun Anggaran',
    };
    function renderPengeluaranRiil(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const ppk = data.ppk || pegawaiKosong();
        const rows = data.pengeluaran || [];
        const tahun = (data.tanggal_mulai || '').slice(0, 4) || '.....';
        let total = 0;
        let bodyRows = '';
        rows.forEach(function (row, i) {
            const nominal = parseFloat(row.nominal) || 0;
            total += nominal;
            const skLabel = skPengeluaranLabel[row.jenis] || skPengeluaranLabel.transportasi;
            bodyRows += `<tr>
                <td>${i + 1}</td>
                <td>${esc(row.uraian) || '-'}<br><span class="fst-italic" style="font-size:0.68rem;">${skLabel} ${tahun}</span></td>
                <td class="text-end">${fmtRupiah(nominal)}</td>
            </tr>`;
        });
        if (!bodyRows) {
            bodyRows = '<tr><td colspan="3" class="text-center text-muted">Belum ada pengeluaran riil</td></tr>';
        }
        return `
            <div style="font-family:Arial, sans-serif;">
            <div class="dokumen-title" style="font-size:14px;">DAFTAR PENGELUARAN RIIL</div>
            <p>Yang bertanda tangan dibawah ini :</p>
            <table class="dokumen-table">
                <tr><td width="20%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
            </table>
            <p>Berdasarkan ${data.no_spd ? 'SPD Nomor: ' + esc(data.no_spd) + ', tanggal ' + fmtTanggalPanjang(data.tanggal_spd) : 'Surat Tugas Nomor: ' + (esc(data.no_surat_tugas) || '...........................') + ', tanggal ' + fmtTanggalPanjang(data.tanggal_surat_tugas)}. Dengan ini kami menyatakan dengan sesungguhnya bahwa:</p>
            <ol class="dokumen-list">
                <li>Biaya transport pegawai dan/atau biaya penginapan dibawah ini yang tidak dapat diperoleh bukti-buktinya, meliputi:
                    <table class="dokumen-table dokumen-rincian mt-1">
                        <thead><tr><th width="5%">No</th><th>Uraian</th><th width="20%">Jumlah</th></tr></thead>
                        <tbody>
                        ${bodyRows}
                        <tr><td colspan="2" class="text-end fw-bold">Jumlah</td><td class="text-end fw-bold">${fmtRupiah(total)}</td></tr>
                        </tbody>
                    </table>
                </li>
                <li>Jumlah uang tersebut pada angka 1 di atas benar-benar dikeluarkan untuk pelaksanaan perjalanan dinas dimaksud dan apabila dikemudian hari terdapat kelebihan atas pembayaran, kami bersedia untuk menyetorkan kelebihan tersebut ke Kas Negara.</li>
            </ol>
            <p>Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.</p>
            <div class="text-end mb-3 pe-2">Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}</div>
            <table class="dokumen-table">
                <tr>
                    <td width="50%" class="text-center">
                        Mengetahui/Menyetujui<br>An. Kuasa Pengguna Anggaran<br>Pejabat Pembuat Komitmen<br>BPS Kabupaten Murung Raya<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(ppk.nip))}
                    </td>
                    <td class="text-center">
                        <br><br>Pejabat Negara/ Pegawai Negeri<br>Yang melakukan Perjalanan Dinas<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(pelaksana.nip))}
                    </td>
                </tr>
            </table>
            </div>
        `;
    }

    // ----- Kuitansi -----
    function renderKuitansi(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const ppk = data.ppk || pegawaiKosong();
        const bendahara = data.bendahara || pegawaiKosong();
        const total = (data.rincian || []).reduce(function (sum, r) { return sum + (parseFloat(r.nominal) || 0); }, 0) + jumlahPengeluaran(data);
        const tahun = (data.tanggal_mulai || '').slice(0, 4) || '.....';
        return `
            <div style="font-family:Arial, sans-serif;">
            <table class="ms-auto" style="width:auto; max-width:65%; word-wrap:break-word; font-size:0.75rem; margin-bottom:1rem;">
                <tr><td class="pe-2">Tahun Anggaran</td><td>: ${tahun}</td></tr>
                <tr><td class="pe-2">Nomor Bukti</td><td>:</td></tr>
                <tr><td class="pe-2">Akun</td><td>: ${esc(data.pembebanan) || '...........................'}</td></tr>
                <tr><td class="pe-2">Kepada</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                <tr><td class="pe-2">Satker</td><td>: ${satkerLabel}</td></tr>
            </table>
            <div class="dokumen-title" style="font-size:16px;">Kuitansi/Bukti Pembayaran</div>
            <table class="dokumen-table mt-3">
                <tr><td width="20%">Sudah terima dari</td><td>: Pejabat Pembuat Komitmen BPS Kabupaten Murung Raya</td></tr>
                <tr><td>Jumlah Uang</td><td>: <strong>${fmtRupiah(total)},-</strong></td></tr>
                <tr><td>Terbilang</td><td>: <span class="fw-bold fst-italic">** ${terbilangRupiah(total)} **</span></td></tr>
                <tr><td>Untuk pembayaran</td><td>: Biaya perjalanan dinas dalam rangka ${esc(data.perihal) || '...........................'}${wilayahTujuan(data) ? ' di ' + esc(wilayahTujuan(data)) : ''}, tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}, sesuai dengan:<br><br>
                    Surat Tugas Nomor: ${esc(data.no_surat_tugas) || '...........................'} tanggal ${fmtTanggalPanjang(data.tanggal_surat_tugas)}<br>
                    ${data.no_spd ? 'SPD Nomor: ' + esc(data.no_spd) + ' tanggal ' + fmtTanggalPanjang(data.tanggal_spd) : ''}
                </td></tr>
            </table>
            <div class="ttd-block">
                <div>Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}</div>
                <div>Yang menerima</div>
                <div class="mt-5 fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</div>
                <div>NIP. ${esc(fmtNip(pelaksana.nip))}</div>
            </div>
            <div class="kop-line-thin mt-3"></div>
            <table class="dokumen-table">
                <tr>
                    <td width="50%" class="text-center">
                        An. Kuasa Pengguna Anggaran<br>Pejabat Pembuat Komitmen<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(ppk.nip))}
                    </td>
                    <td class="text-center">
                        Lunas dibayar,<br>Bendahara Pengeluaran<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(bendahara.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(bendahara.nip))}
                    </td>
                </tr>
            </table>
            <div class="kop-line-thin"></div>
            <p>Barang/pekerjaan tersebut telah diterima/diselesaikan dengan lengkap dan baik</p>
            <div style="width:50%; text-align:center; margin-top:1rem;">
                <div>Pejabat yang bertanggungjawab<br>Pejabat Pembuat Komitmen</div>
                <div class="mt-5 fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</div>
                <div>NIP. ${esc(fmtNip(ppk.nip))}</div>
            </div>
            </div>
        `;
    }

    // ----- Surat Pernyataan -----
    function suratPernyataanBlock(data, kondisi, keterangan) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const noSt = esc(data.no_surat_tugas) || '...........................';
        const tglSt = fmtTanggalPanjang(data.tanggal_surat_tugas);
        const noSpd = esc(data.no_spd) || '...........................';
        const tglSpd = fmtTanggalPanjang(data.tanggal_spd);
        const tglTtd = fmtTanggalPanjang(data.tanggal_kuitansi);

        if (kondisi === 'tidak_pakai_kendaraan_dinas') {
            return `
                <div style="font-family:Arial, sans-serif;">
                <div class="dokumen-title" style="font-size:14px; font-weight:700;">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor" style="font-size:14px; font-weight:700; text-decoration:underline;">TIDAK MENGGUNAKAN KENDARAAN DINAS</div>
                <p>Yang bertanda tangan di bawah ini:</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Satuan Kerja</td><td>: BPS Kabupaten Murung Raya</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Menerangkan bahwa dalam rangka melaksanakan perjalanan dinas untuk melaksanakan tugas kedinasan sesuai surat tugas nomor: ${noSt} tanggal ${tglSt}${data.no_spd ? ' dan SPD nomor: ' + noSpd + ' tanggal ' + tglSpd : ''} saya benar-benar tidak menggunakan kendaraan dinas.</p>
                <p>Demikian pernyataan ini kami buat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya. Apabila terdapat kekeliruan dalam pertanggungjawaban dan mengakibatkan kerugian negara, saya bersedia dituntut sesuai peraturan yang berlaku dan mengembalikan biaya transport atau biaya transport lokal yang sudah terlanjur saya terima ke kas negara.</p>
                <div class="ttd-block">
                    <div>Puruk Cahu, ${tglTtd}</div>
                    <div>Pelaksana Perjalanan Dinas,</div>
                    <div class="mt-5 fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</div>
                    <div>NIP. ${esc(fmtNip(pelaksana.nip))}</div>
                </div>
                </div>
            `;
        }

        if (kondisi === 'tidak_menginap_hotel') {
            return `
                <div style="font-family:Arial, sans-serif;">
                <div class="dokumen-title" style="font-size:14px; font-weight:700;">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor" style="font-size:14px; font-weight:700; text-decoration:underline;">TIDAK MENGINAP DI HOTEL ATAU DI PENYEDIA JASA AKOMODASI LAINNYA</div>
                <p>Yang bertanda tangan di bawah ini:</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Pangkat</td><td>: ${esc(pelaksana.pangkat) || '-'}${pelaksana.golongan ? ' / ' + esc(pelaksana.golongan) : ''}</td></tr>
                    <tr><td>Satuan Kerja</td><td>: BPS Kabupaten Murung Raya</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Menerangkan bahwa selama melaksanakan perjalanan dinas dalam rangka ${esc(data.perihal) || '...........................'}${wilayahTujuan(data) ? ' di ' + esc(wilayahTujuan(data)) : ''} pada tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}, saya benar-benar tidak menginap di hotel atau jasa akomodasi komersial lainnya.</p>
                <p>Demikian pernyataan ini saya buat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya. Apabila terdapat kekeliruan dalam pertanggung jawaban SPD dan mengakibatkan kerugian negara, saya bersedia dituntut sesuai peraturan yang berlaku dan mengembalikan biaya kompensasi menginap di hotel atau jasa akomodasi komersial lainnya yang sudah terlanjur saya terima ke kas negara.</p>
                <div class="ttd-block">
                    <div>Puruk Cahu, ${tglTtd}</div>
                    <div>Pelaksana Perjalanan Dinas,</div>
                    <div class="mt-5 fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</div>
                    <div>NIP. ${esc(fmtNip(pelaksana.nip))}</div>
                </div>
                </div>
            `;
        }

        if (kondisi === 'keterlambatan') {
            return `
                <div style="font-family:Arial, sans-serif;">
                <div class="dokumen-title" style="font-size:14px; font-weight:700;">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor" style="font-size:14px; font-weight:700; text-decoration:underline;">KETERLAMBATAN PENGAJUAN TAGIHAN</div>
                <p>Yang bertanda tangan di bawah ini:</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Dengan ini menyatakan sebagai berikut:</p>
                <ol class="dokumen-list">
                    <li>Telah terjadi keterlambatan mengajukan hak tagihan kepada Pejabat Pembuat Komitmen (PPK) BPS Kabupaten Murung Raya atas pelaksanaan perjalanan dinas ${esc(data.perihal) || '...........................'} yang telah selesai dilaksanakan pada tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}.<br>
                    Berdasarkan Surat Tugas Nomor: ${noSt} tanggal ${tglSt}${data.no_spd ? '<br>Berdasarkan SPD Nomor: ' + noSpd + ' tanggal ' + tglSpd : ''}</li>
                    <li>Keterlambatan mengajukan hak tagihan disebabkan oleh ${esc(keterangan) || '...........................'}</li>
                    <li>Selanjutnya saya tidak akan mengalami keterlambatan kembali dalam pengajuan hak tagihan sesuai dengan batas waktu pengajuan hak tagihan kepada Negara yang diatur dalam PMK Nomor 190/PMK.05/2012 tentang Tata Cara Pembayaran dalam Rangka Pelaksanaan Anggaran Pendapatan dan Belanja Negara.</li>
                </ol>
                <p>Demikian pernyataan ini dibuat dengan sebenar-benarnya.</p>
                <div class="ttd-block">
                    <div>Puruk Cahu, ${tglTtd}</div>
                    <div>Yang menyatakan,</div>
                    <div class="mt-5 fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</div>
                    <div>NIP. ${esc(fmtNip(pelaksana.nip))}</div>
                </div>
                </div>
            `;
        }

        return '';
    }

    function renderPernyataan(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const ppk = data.ppk || pegawaiKosong();
        const rows = data.pernyataan || [];
        const blocks = [];

        if (data.jenis_perjadin === 'dalam_kota_lebih_8_jam') {
            blocks.push(`
                <div style="font-family:Arial, sans-serif;">
                <div class="dokumen-title" style="font-size:14px; font-weight:700;">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor" style="font-size:14px; font-weight:700; text-decoration:underline;">PERJALANAN DINAS DALAM KOTA LEBIH DARI 8 JAM</div>
                <p>Yang bertanda tangan di bawah ini :</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Dengan ini menyatakan bahwa perjalanan dinas dalam kota yang saya laksanakan sebagai berikut :</p>
                <table class="dokumen-table">
                    <tr><td width="26%">a. Nomor Surat Tugas</td><td width="1%">:</td><td>${esc(data.no_surat_tugas) || '...........................'}, tanggal ${fmtTanggalPanjang(data.tanggal_surat_tugas)}</td></tr>
                    <tr><td>b. Nomor SPD</td><td>:</td><td>${data.no_spd ? esc(data.no_spd) + ', tanggal ' + fmtTanggalPanjang(data.tanggal_spd) : '-'}</td></tr>
                    <tr><td>c. Tujuan</td><td>:</td><td>${esc(data.perihal) || '...........................'}${wilayahTujuan(data) ? ' di ' + esc(wilayahTujuan(data)) : ''} selama ${terbilangHari(hitungLamaHari(data.tanggal_mulai, data.tanggal_selesai))} pada tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}</td></tr>
                </table>
                <p>Adalah benar dilaksanakan lebih dari 8 jam.</p>
                <p>Demikian pernyataan ini dibuat dengan sebenar-benarnya, dan saya sanggup menerima konsekuensi jika pernyataan ini tidak benar.</p>
                <div class="text-end mb-3 pe-2">Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}</div>
                <table class="dokumen-table mt-3">
                    <tr>
                        <td width="50%" class="text-center">
                            Mengetahui,<br>BPS Kabupaten Murung Raya<br>Pejabat Pembuat Komitmen,<br><br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(ppk.nip))}
                        </td>
                        <td class="text-center">
                            <br><br>Pelaksana Perjalanan Dinas<br><br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(pelaksana.nip))}
                        </td>
                    </tr>
                </table>
                </div>
            `);
        }

        if (!rows.length && !blocks.length) {
            return `
                <div class="dokumen-title">SURAT PERNYATAAN</div>
                <p class="text-center text-muted">Tidak ada surat pernyataan tambahan.</p>
            `;
        }

        rows.forEach(function (row) {
            blocks.push(suratPernyataanBlock(data, row.jenis_kondisi, row.keterangan));
        });

        // Tiap surat pernyataan mulai di halaman baru saat print/export (kecuali yang
        // pertama, karena dia sudah otomatis di halaman pertama).
        return blocks.map(function (block, i) {
            return i === 0 ? block : '<div class="dokumen-page-break">' + block + '</div>';
        }).join('');
    }

    // ----- Laporan -----
    function renderLaporan(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const penandatangan = data.penandatangan || pegawaiKosong();

        let dasar = '1. Surat Tugas Nomor ' + (esc(data.no_surat_tugas) || '...........................')
            + '<br>&nbsp;&nbsp;&nbsp;tanggal ' + fmtTanggalPanjang(data.tanggal_surat_tugas);
        if (data.no_spd) {
            dasar += '<br>2. SPD Nomor ' + esc(data.no_spd) + '<br>&nbsp;&nbsp;&nbsp;tanggal ' + fmtTanggalPanjang(data.tanggal_spd);
        }

        // Tindak Lanjut: satu poin per baris di textarea input -> daftar bernomor.
        const tindakLanjutItems = String(data.tindak_lanjut || '').split('\n').map(function (s) { return s.trim(); }).filter(Boolean);
        const tindakLanjutHtml = tindakLanjutItems.length
            ? '<ol class="dokumen-list">' + tindakLanjutItems.map(function (item) { return '<li>' + esc(item) + '</li>'; }).join('') + '</ol>'
            : '-';

        // Pakai <table> (bukan CSS grid) supaya susunan 2 kolomnya juga kebaca benar
        // saat di-export ke Excel — Excel cuma paham struktur table, bukan CSS grid.
        const rows = data.dokumentasi || [];
        let dokumentasiGrid;
        if (rows.length) {
            function dokumentasiCell(d) {
                if (!d) return '<td class="dokumentasi-cell"></td>';
                const isPdf = /\.pdf$/i.test(d.nama_file || '');
                const inner = (!isPdf && d.url)
                    ? '<img src="' + esc(d.url) + '" alt="' + esc(d.nama_file) + '">'
                    : '<div class="dokumentasi-file"><i class="ti ti-file-text ti-lg"></i><br>' + esc(d.nama_file) + '</div>';
                const caption = d.caption ? '<div class="dokumentasi-caption">' + esc(d.caption) + '</div>' : '';
                return '<td class="dokumentasi-cell">' + inner + caption + '</td>';
            }
            let bodyRows = '';
            for (let i = 0; i < rows.length; i += 2) {
                bodyRows += '<tr>' + dokumentasiCell(rows[i]) + dokumentasiCell(rows[i + 1]) + '</tr>';
            }
            dokumentasiGrid = '<table class="dokumen-table dokumentasi-grid"><colgroup><col width="50%"><col width="50%"></colgroup>' + bodyRows + '</table>';
        } else {
            dokumentasiGrid = '<p class="text-center text-muted">Belum ada dokumentasi.</p>';
        }

        return `
            <div style="font-family:Arial, sans-serif;">
            <div class="dokumen-title">LAPORAN HASIL PERJALANAN DINAS</div>
            <table class="dokumen-table mt-3">
                <tr><td width="16%" style="vertical-align:top;">Kepada Yth</td><td>: Kepala Badan Pusat Statistik Kabupaten Murung Raya</td></tr>
                <tr><td style="vertical-align:top;">Perihal</td><td>: Laporan Perjalanan Dinas dalam rangka ${esc(data.perihal) || '...........................'}</td></tr>
            </table>
            <table class="dokumen-table mt-3">
                <tr><td width="4%" style="vertical-align:top;">I.</td><td width="27%" style="vertical-align:top;">Pelaksana Perjalanan</td><td style="vertical-align:top;">
                    <table class="dokumen-table">
                        <tr><td width="18%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                        <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                        <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                    </table>
                </td></tr>
                <tr><td style="vertical-align:top;">II.</td><td style="vertical-align:top;">Dasar Pelaksanaan</td><td style="vertical-align:top;">${dasar}</td></tr>
                <tr><td style="vertical-align:top;">III.</td><td style="vertical-align:top;">Tujuan Perjalanan Dinas</td><td style="vertical-align:top;">${esc(data.perihal) || '...........................'}</td></tr>
                <tr><td style="vertical-align:top;">IV.</td><td style="vertical-align:top;">Daerah Tujuan/Instansi</td><td style="vertical-align:top;">${data.desa_tujuan ? esc(data.desa_tujuan) + (data.kabupaten_tujuan ? ', ' + esc(data.kabupaten_tujuan) : '') : (esc(data.kabupaten_tujuan) || '...........................')}</td></tr>
                <tr><td style="vertical-align:top;">V.</td><td style="vertical-align:top;">Waktu Pelaksanaan</td><td style="vertical-align:top;">${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}</td></tr>
                <tr><td style="vertical-align:top;">VI.</td><td colspan="2" style="vertical-align:top;">
                    Kesimpulan Hasil Kegiatan :<br>
                    <span style="text-align:justify;display:block;">${esc(data.kesimpulan_hasil_kegiatan) || '-'}</span>
                </td></tr>
                <tr><td style="vertical-align:top;">VII.</td><td style="vertical-align:top;">Tindak Lanjut</td><td style="vertical-align:top;">
                    : Hal-hal yang perlu diperhatikan antara lain :
                    ${tindakLanjutHtml}
                </td></tr>
            </table>

            <div class="dokumen-page-break">
                <div class="dokumen-page-num">- 2 -</div>
                <p>Demikian Laporan Perjalanan Dinas ini dibuat sebagai bahan Laporan.</p>
                <table class="dokumen-table mt-3">
                    <tr>
                        <td width="50%" class="text-center">
                            Mengetahui :<br>Kepala BPS Kabupaten Murung Raya,<br><br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(penandatangan.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(penandatangan.nip))}
                        </td>
                        <td class="text-center">
                            Puruk Cahu, ${fmtTanggalPanjang(tambahHariKerja(data.tanggal_selesai, 1))}<br>Yang Melakukan Perjalanan Dinas,<br><br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(pelaksana.nip))}
                        </td>
                    </tr>
                </table>
            </div>

            <div class="dokumen-page-break">
                <div class="dokumen-page-num">- 3 -</div>
                <div class="dokumen-title" style="text-decoration:none;">Dokumentasi</div>
                ${dokumentasiGrid}
            </div>
            </div>
        `;
    }

    // ----- Export -----
    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    // Jendela export (popup PDF, download Word) tidak memuat Bootstrap, jadi class
    // seperti text-center/fw-bold/dst harus disertakan sendiri. Ambil dari stylesheet
    // dokumen-perjadin.css yang sudah termuat di halaman ini, supaya tidak duplikasi CSS.
    function getDokumenCss() {
        for (let i = 0; i < document.styleSheets.length; i++) {
            const sheet = document.styleSheets[i];
            try {
                if (sheet.href && sheet.href.indexOf('dokumen-perjadin.css') !== -1) {
                    return Array.from(sheet.cssRules).map(function (r) { return r.cssText; }).join('\n');
                }
            } catch (e) {
                // stylesheet cross-origin, cssRules tidak bisa dibaca, lewati
            }
        }
        return '';
    }

    // Word/Excel mengabaikan CSS border-collapse pada <table>, jadi border antar sel
    // suka jadi dobel/ada celah walau sudah keliatan rapi di PDF. Perbaikannya harus
    // pakai atribut HTML cellspacing/cellpadding lawas, bukan CSS — di-set lewat DOM
    // supaya tidak perlu ubah setiap render function satu-satu.
    function addTableCompatAttrs(html) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        wrapper.querySelectorAll('table').forEach(function (table) {
            table.setAttribute('cellspacing', '0');
            table.setAttribute('cellpadding', '0');
            table.setAttribute('border', '0');
        });
        return wrapper.innerHTML;
    }

    // A4 untuk sebagian besar dokumen, F4/Folio khusus Surat Tugas & SPD.
    const PAPER_SIZES = {
        a4: { pageCss: 'A4', width: '210mm', height: '297mm', cssClass: 'dokumen-a4' },
        f4: { pageCss: '215mm 330mm', width: '215mm', height: '330mm', cssClass: 'dokumen-f4' },
    };

    function resolvePaperSize(paperSize) {
        return PAPER_SIZES[(paperSize || 'a4').toLowerCase()] || PAPER_SIZES.a4;
    }

    // Lebar/tinggi AREA KONTEN (kertas dikurangi padding/margin dokumen) dalam px —
    // HARUS sinkron dengan padding di @media print pada dokumen-perjadin.css
    // (2,54cm A4 biasa, 1,27cm Rincian Biaya, 15mm F4).
    const MM_PER_PX = 25.4 / 96;
    function pageContentSizePx(paperClass) {
        const isF4 = paperClass.indexOf('dokumen-f4') !== -1;
        const isRincian = paperClass.indexOf('dokumen-rincian-biaya') !== -1;
        const widthMm = isF4 ? 215 : 210;
        const heightMm = isF4 ? 330 : 297;
        const marginMm = isF4 ? 15 : (isRincian ? 12.7 : 25.4);
        return {
            width: (widthMm - 2 * marginMm) / MM_PER_PX,
            height: (heightMm - 2 * marginMm) / MM_PER_PX,
        };
    }

    // Dokumen dengan tabel yang barisnya TUMBUH mengikuti data (mis. Daftar Pengeluaran
    // Riil — 1 baris per item yang diinput pemohon, "Uraian"-nya pun textarea bebas
    // panjang) bisa jadi lebih tinggi dari 1 halaman fisik. Kalau dibiarkan sebagai 1
    // div biasa, browser MEMANG tetap otomatis pindah halaman saat print (lihat
    // page-break-inside:avoid per <tr> di CSS) — tapi margin (padding) div cuma
    // berlaku di tepi div itu sendiri, jadi halaman ke-2/3/dst hasil auto-split itu
    // nempel rata ke tepi kertas, tanpa margin (lihat komentar exportAsPdf).
    //
    // Ini ukur tinggi asli tiap baris tabel (dengan render sungguhan di DOM
    // tersembunyi, BUKAN capture/screenshot), lalu — cuma kalau memang meluber dari 1
    // halaman — pecah tabelnya jadi beberapa <table> terpisah yang disisipi penanda
    // .dokumen-page-break yang SUDAH ADA (dikonsumsi oleh splitLogicalPages di bawah),
    // supaya tiap "halaman" hasil pecahan itu jadi div sendiri dengan margin sendiri.
    // Kalau kontennya muat 1 halaman (kasus normal, hampir semua dokumen), fungsi ini
    // tidak mengubah apa-apa.
    function splitOverflowingTable(html, paperClass) {
        const size = pageContentSizePx(paperClass);
        const measure = document.createElement('div');
        measure.className = 'dokumen-preview';
        measure.style.cssText = 'position:fixed;top:-99999px;left:-99999px;visibility:hidden;'
            + 'width:' + size.width + 'px;';
        measure.innerHTML = html;
        document.body.appendChild(measure);

        // Kalau dokumennya cuma 1 div pembungkus (font-family, dst — pola yang dipakai
        // semua render function), kerja di DALAM situ supaya struktur pembungkusnya
        // (termasuk style-nya) tetap utuh di halaman pertama.
        const wrapper = (measure.children.length === 1 && !measure.children[0].classList.contains('dokumen-page-break'))
            ? measure.children[0] : measure;

        // Tabel dinamis yang paling mungkin jadi biang meluber: yang barisnya paling
        // banyak. Kalau tidak ada yang barisnya > 1, tidak ada yang perlu dipecah.
        const table = Array.from(wrapper.querySelectorAll('table.dokumen-rincian')).filter(function (t) {
            return t.tBodies[0] && t.tBodies[0].rows.length > 1;
        }).sort(function (a, b) {
            return b.tBodies[0].rows.length - a.tBodies[0].rows.length;
        })[0];

        if (!table || measure.getBoundingClientRect().height <= size.height) {
            document.body.removeChild(measure);
            return html;
        }

        const tbody = table.tBodies[0];
        const rowEls = Array.from(tbody.rows);
        const rowHeights = rowEls.map(function (tr) { return tr.getBoundingClientRect().height; });
        const theadHeight = table.tHead ? table.tHead.getBoundingClientRect().height : 0;
        const wrapperTop = wrapper.getBoundingClientRect().top;
        const prefixHeight = table.getBoundingClientRect().top - wrapperTop;

        // Lepas semua node SESUDAH tabel (di semua level nenek moyang sampai wrapper)
        // dari DOM — disimpan buat ditempel lagi di halaman TERAKHIR hasil pecahan.
        // <li> yang lepas dari <ol>-nya dibungkus ulang biar penomorannya tetap benar.
        const afterNodes = [];
        (function collect(node) {
            while (node && node !== wrapper) {
                let sibling = node.nextSibling;
                while (sibling) {
                    const next = sibling.nextSibling;
                    // Simpan nomor urut aslinya (1-based) di <ol> asal SEBELUM dilepas,
                    // supaya waktu dibungkus ulang jadi <ol> baru, "1." tidak nongol lagi
                    // dari awal (lihat pemakaian data-li-index di bawah).
                    if (sibling.nodeType === 1 && sibling.tagName === 'LI') {
                        sibling.dataset.liIndex = Array.from(sibling.parentNode.children).indexOf(sibling) + 1;
                    }
                    afterNodes.push(sibling);
                    sibling.parentNode.removeChild(sibling);
                    sibling = next;
                }
                node = node.parentNode;
            }
        })(table);
        const suffixWrap = document.createElement('div');
        let pendingOl = null;
        afterNodes.forEach(function (node) {
            if (node.nodeType === 1 && node.tagName === 'LI') {
                if (!pendingOl) {
                    pendingOl = document.createElement('ol');
                    pendingOl.className = 'dokumen-list';
                    pendingOl.setAttribute('start', node.dataset.liIndex || '1');
                    suffixWrap.appendChild(pendingOl);
                }
                delete node.dataset.liIndex;
                pendingOl.appendChild(node);
            } else {
                pendingOl = null;
                suffixWrap.appendChild(node);
            }
        });

        // Bagi baris tbody ke beberapa "halaman" sesuai sisa tinggi yang ada.
        const rowPages = [[]];
        let used = prefixHeight + theadHeight;
        rowEls.forEach(function (tr, i) {
            const h = rowHeights[i];
            if (rowPages[rowPages.length - 1].length && used + h > size.height) {
                rowPages.push([]);
                used = theadHeight;
            }
            rowPages[rowPages.length - 1].push(tr);
            used += h;
        });
        // Kalau baris terakhir + suffix (penutup/ttd) kepepet, kasih halaman sendiri.
        // suffixWrap belum nempel di DOM (masih lepas) jadi tingginya perlu diukur
        // dengan ditempel sementara — elemen lepas selalu keukur tinggi 0.
        if (suffixWrap.childNodes.length) {
            measure.appendChild(suffixWrap);
            const suffixHeight = suffixWrap.getBoundingClientRect().height;
            measure.removeChild(suffixWrap);
            if (used + suffixHeight > size.height) {
                rowPages.push([]);
            }
        }

        function tableWithRows(rows) {
            const clone = table.cloneNode(false);
            if (table.tHead) clone.appendChild(table.tHead.cloneNode(true));
            const newBody = document.createElement('tbody');
            rows.forEach(function (tr) { newBody.appendChild(tr); });
            clone.appendChild(newBody);
            return clone;
        }

        // Halaman 1: sisa isi wrapper (prefix, sudah otomatis tersisa di tempatnya)
        // + tabel yang tbody-nya cuma diisi baris jatah halaman 1.
        table.parentNode.replaceChild(tableWithRows(rowPages[0]), table);

        // Halaman 2 dst: <div class="dokumen-page-break"> baru berisi tabel lanjutan
        // (thead diulang) — dan suffix ditempel di halaman TERAKHIR.
        for (let i = 1; i < rowPages.length; i++) {
            const pageDiv = document.createElement('div');
            pageDiv.className = 'dokumen-page-break';
            if (rowPages[i].length) pageDiv.appendChild(tableWithRows(rowPages[i]));
            if (i === rowPages.length - 1) {
                Array.from(suffixWrap.childNodes).forEach(function (node) { pageDiv.appendChild(node); });
            }
            wrapper.appendChild(pageDiv);
        }
        // Kalau tidak ada baris ekstra yang butuh halaman baru (suffix saja yang
        // kepepet), suffix belum ketempel — susulkan ke wrapper langsung.
        if (suffixWrap.childNodes.length) {
            Array.from(suffixWrap.childNodes).forEach(function (node) { wrapper.appendChild(node); });
        }

        const result = measure.innerHTML;
        document.body.removeChild(measure);
        return result;
    }

    // Pisah HTML dokumen jadi "halaman logis" berdasarkan penanda manual
    // .dokumen-page-break (dipakai Laporan & Surat Pernyataan multi-kondisi) — baik dia
    // ada di level atas (render function beda-beda concat) maupun bersarang di dalam 1
    // div pembungkus (font-family, dst).
    function splitLogicalPages(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;

        let rootStyle = '';
        let nodes;
        if (temp.children.length === 1 && !temp.children[0].classList.contains('dokumen-page-break')) {
            rootStyle = temp.children[0].getAttribute('style') || '';
            nodes = Array.from(temp.children[0].childNodes);
        } else {
            nodes = Array.from(temp.childNodes);
        }

        const pages = [[]];
        nodes.forEach(function (node) {
            if (node.nodeType === 1 && node.classList && node.classList.contains('dokumen-page-break')) {
                pages.push(Array.from(node.childNodes));
            } else {
                pages[pages.length - 1].push(node);
            }
        });

        return pages.map(function (pageNodes) { return { style: rootStyle, nodes: pageNodes }; });
    }

    // Dipertahankan untuk kompatibilitas (kalau ada pemakai lain yang cuma butuh HTML
    // string, tanpa auto-split-per-tinggi) — hasilnya 1 <div> per halaman LOGIS saja.
    function renderPaginated(html, paperClass) {
        return splitLogicalPages(html).map(function (page) {
            const pageWrap = document.createElement('div');
            pageWrap.className = 'dokumen-preview ' + paperClass;
            const inner = document.createElement('div');
            if (page.style) inner.setAttribute('style', page.style);
            page.nodes.forEach(function (node) { inner.appendChild(node); });
            pageWrap.appendChild(inner);
            return pageWrap.outerHTML;
        }).join('');
    }

    // CSS min-height A4/F4 di dokumen-perjadin.css itu literal mm — akurat kalau lebar
    // kolomnya juga persis 210mm/215mm, tapi meleset (jadi kelihatan kurang proporsional)
    // begitu kolomnya lebih sempit dan lebarnya ke-cap oleh max-width:100%. Ini
    // menghitung ulang min-height berdasarkan lebar HASIL RENDER yang sebenarnya, tetap
    // pakai min-height (bukan height) supaya halaman yang isinya pas-pasan tetap kelihatan
    // penuh 1 lembar kertas.
    function applyPageMinHeights(containerEl) {
        containerEl.querySelectorAll('.dokumen-preview.dokumen-a4, .dokumen-preview.dokumen-f4').forEach(function (page) {
            const ratio = page.classList.contains('dokumen-f4') ? (330 / 215) : (297 / 210);
            page.style.minHeight = Math.round(page.offsetWidth * ratio) + 'px';
        });
    }

    function exportAsWord(html, filename, paperSize, extraClass) {
        const css = getDokumenCss();
        const safeHtml = addTableCompatAttrs(html);
        const paper = resolvePaperSize(paperSize);
        // "WordSection1" + @page adalah cara standar bikin Word pakai ukuran halaman &
        // margin yang kita mau (A4/F4), bukan default Word (biasanya Letter). object-fit
        // juga tidak didukung Word, jadi foto dokumentasi di-override supaya scale
        // proporsional (auto+max-height) alih-alih ketarik/gepeng. A4 pakai margin ala
        // Word (preset "Normal": 2,54 cm rata keempat sisi); F4/Folio tetap seperti semula;
        // Perincian Biaya khusus pakai preset "Narrow" (1,27 cm).
        const marginWord = extraClass === 'dokumen-rincian-biaya' ? '1.27cm'
            : (paper.cssClass === 'dokumen-a4' ? '2.54cm' : '2cm 1.5cm');
        const wordStyle = '@page WordSection1{size:' + paper.width + ' ' + paper.height + ';margin:' + marginWord + ';}'
            + 'div.WordSection1{page:WordSection1;}'
            + '.dokumentasi-cell img{width:auto;max-width:100%;height:auto;max-height:65mm;}';
        const content = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'
            + '<head><meta charset="utf-8"><meta name="ProgId" content="Word.Document"><meta name="Generator" content="Microsoft Word">'
            + '<style>body{font-family:"Times New Roman",serif;font-size:12pt;}' + css + wordStyle + '</style></head>'
            + '<body><div class="WordSection1"><div class="dokumen-preview">' + safeHtml + '</div></div></body></html>';
        downloadBlob(new Blob(['﻿' + content], { type: 'application/msword' }), filename + '.doc');
    }

    function exportAsExcel(html, filename) {
        const css = getDokumenCss();
        const safeHtml = addTableCompatAttrs(html);
        const content = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">'
            + '<head><meta charset="utf-8"><style>' + css + '</style></head>'
            + '<body><div class="dokumen-preview">' + safeHtml + '</div></body></html>';
        downloadBlob(new Blob(['﻿' + content], { type: 'application/vnd.ms-excel' }), filename + '.xls');
    }

    // Bangun HTML dokumen lengkap (CSS + @page + halaman-halaman yang sudah dipecah
    // lewat splitOverflowingTable/renderPaginated) — dipakai baik oleh exportAsPdf
    // (dialog print, lihat window.print()) maupun exportAsPdfHd (dikirim ke server
    // buat dikonversi Chrome headless), supaya keduanya persis sama hasilnya.
    function buildDokumenHtmlDocument(html, title, cssUrl, paperSize, extraClass, skipLinkTag, cssOverride) {
        // @page margin di-nol-kan: browser tidak bisa diandalkan menghormati
        // @page{margin:custom} di dialog print asli (lihat komentar @media print di
        // dokumen-perjadin.css) — margin visualnya dibikin dari padding div
        // .dokumen-a4/.dokumen-f4 sendiri, yang selalu dihormati.
        const paper = paperSize ? resolvePaperSize(paperSize) : null;
        const pageStyle = paper ? '<style>@page{size:' + paper.pageCss + ';margin:0;}</style>' : '';
        // cssOverride (dari fetch mentah, lihat exportAsPdfHd) dipakai kalau ada —
        // getDokumenCss() baca ulang CSS lewat cssRules[].cssText milik BROWSER, dan
        // browser DIAM-DIAM membuang properti lawas "page-break-before: always;" saat
        // serialize ulang (cuma nyisain versi modernnya "break-before: page;") karena
        // dianggap alias. dompdf TIDAK paham "break-before" (cuma paham sintaks CSS2.1
        // "page-break-before"), jadi kalau lewat cssRules, .dokumen-page-break (dipakai
        // Laporan buat mecah halaman Dokumentasi) di server diam-diam tidak ke-apply.
        const css = cssOverride || getDokumenCss();
        // Dokumen yang punya lebih dari 1 "halaman logis" (ditandai .dokumen-page-break,
        // mis. Laporan) HARUS dipecah jadi beberapa div .dokumen-preview terpisah lewat
        // renderPaginated (sama seperti preview di layar) — bukan 1 div gede berisi semua
        // halaman. Soalnya margin (padding) itu properti div, cuma berlaku di tepi div
        // itu sendiri; kalau semua halaman digabung 1 div, cuma tepi paling atas & paling
        // bawah dari keseluruhan konten yang kebagian padding, sedangkan potongan halaman
        // di tengah (hasil page-break internal) nempel rata ke tepi kertas fisik.
        const paperClass = (paper ? paper.cssClass : '') + (extraClass ? ' ' + extraClass : '');
        // Tabel yang barisnya tumbuh mengikuti data (mis. Daftar Pengeluaran Riil) bisa
        // meluber dari 1 halaman fisik — kalau iya, splitOverflowingTable menyisipkan
        // penanda .dokumen-page-break sendiri di titik potong barisnya (lihat komentar
        // di fungsi itu) sebelum di-render jadi div per halaman lewat renderPaginated.
        const splitHtml = paper ? splitOverflowingTable(html, paperClass) : html;
        const bodyHtml = paper ? renderPaginated(splitHtml, paperClass) : ('<div class="dokumen-preview">' + html + '</div>');
        // <style> di atas sudah berisi salinan LENGKAP dokumen-perjadin.css (lihat
        // getDokumenCss/cssOverride) — <link> di sini cuma cadangan buat jendela print
        // (kalau ada CSS yang entah kenapa gagal ke-serialize ulang lewat cssRules).
        // Untuk export ke server (exportAsPdfHd, skipLinkTag=true) sengaja DIHILANGKAN
        // — dompdf sudah dikonfigurasi isRemoteEnabled:false (lihat
        // DokumenExportController), jadi <link> ke situ tidak akan pernah ke-fetch,
        // percuma disertakan.
        const linkTag = skipLinkTag ? '' : '<link rel="stylesheet" href="' + cssUrl + '">';
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + esc(title) + '</title>'
            + linkTag + '<style>' + css + '</style>' + pageStyle + '</head>'
            + '<body>' + bodyHtml + '</body></html>';
    }

    // Export lewat dialog print browser (window.print(), user pilih "Simpan sebagai
    // PDF" sendiri) — dipertahankan sebagai jalur cadangan kalau server PDF (lihat
    // exportAsPdfHd) tidak tersedia (mis. Node/Chrome belum terpasang di server).
    function exportAsPdf(html, title, cssUrl, paperSize, extraClass) {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Popup diblokir browser. Izinkan popup untuk export PDF.');
            return;
        }
        printWindow.document.write(buildDokumenHtmlDocument(html, title, cssUrl, paperSize, extraClass));
        printWindow.document.close();
        printWindow.focus();
        printWindow.onload = function () {
            setTimeout(function () { printWindow.print(); }, 300);
        };
    }

    // Export PDF langsung ke-download, HD (bukan capture/screenshot): HTML dokumen
    // yang sama seperti buat print (lihat buildDokumenHtmlDocument) dikirim ke server
    // lewat exportUrl, dikonversi jadi PDF sungguhan oleh dompdf (renderer PHP murni,
    // lihat DokumenExportController) — teksnya tetap vector/tajam, cuma tanpa dialog
    // print & tanpa perlu klik "Simpan" manual. Foto Dokumentasi (<img
    // src="{APP_URL}/storage/...">) sengaja TIDAK di-inline base64 di sini — itu perlu
    // fetch dari BROWSER USER ke APP_URL, yang gagal kena CORS kalau APP_URL beda dari
    // domain yang dipakai user buka aplikasinya. Server (DokumenExportController) yang
    // inline foto-foto itu langsung dari disk, tanpa fetch HTTP sama sekali.
    async function exportAsPdfHd(html, title, cssUrl, paperSize, extraClass, exportUrl, csrfToken) {
        // CSS diambil MENTAH lewat fetch (bukan getDokumenCss/cssRules) khusus buat jalur
        // export ini — lihat komentar di buildDokumenHtmlDocument kenapa itu penting
        // (browser diam-diam membuang "page-break-before: always;" saat serialize ulang
        // cssRules, padahal dompdf di server cuma paham sintaks lawas itu, bukan
        // "break-before" versi modern).
        let cssOverride = null;
        try {
            const cssRes = await fetch(cssUrl);
            if (cssRes.ok) cssOverride = await cssRes.text();
        } catch (e) {
            // Gagal fetch (mis. offline) — fallback ke getDokumenCss() di dalam
            // buildDokumenHtmlDocument seperti biasa.
        }
        const fullHtml = buildDokumenHtmlDocument(html, title, cssUrl, paperSize, extraClass, true, cssOverride);
        const response = await fetch(exportUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ html: fullHtml, filename: title }),
        });
        if (!response.ok) {
            let message = 'Gagal membuat PDF (status ' + response.status + ').';
            try {
                const data = await response.json();
                if (data.message) message = data.message;
            } catch (e) {
                // Respons bukan JSON (mis. error 500 polos dari server) — pakai pesan default.
            }
            throw new Error(message);
        }
        const blob = await response.blob();
        downloadBlob(blob, title + '.pdf');
    }

    window.DokumenPerjadin = {
        komponenLabel: komponenLabel,
        kondisiLabel: kondisiLabel,
        esc: esc,
        fmtNip: fmtNip,
        fmtTanggalPanjang: fmtTanggalPanjang,
        fmtRentangTanggal: fmtRentangTanggal,
        hitungLamaHari: hitungLamaHari,
        fmtRupiah: fmtRupiah,
        terbilang: terbilang,
        terbilangHari: terbilangHari,
        terbilangRupiah: terbilangRupiah,
        renderSuratTugas: renderSuratTugas,
        renderSpd: renderSpd,
        renderRincianBiaya: renderRincianBiaya,
        renderPengeluaranRiil: renderPengeluaranRiil,
        renderKuitansi: renderKuitansi,
        renderPernyataan: renderPernyataan,
        renderLaporan: renderLaporan,
        renderPaginated: renderPaginated,
        applyPageMinHeights: applyPageMinHeights,
        exportAsWord: exportAsWord,
        exportAsExcel: exportAsExcel,
        exportAsPdf: exportAsPdf,
        exportAsPdfHd: exportAsPdfHd,
    };
})(window);
