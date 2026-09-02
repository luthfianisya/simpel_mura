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

    const kopAlamat = 'Jl. P. Diponegoro (Komplek Pemda) 73911 Puruk Cahu, Telp (0528) 3033022, WhatsApp 0811-3205-100, E-mail: bps6213@bps.go.id';
    const satkerLabel = '668650 - Badan Pusat Statistik Kabupaten Murung Raya';

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

    function pegawaiKosong() {
        return { nama: '', nip: '', jabatan: '', pangkat: '', golongan: '' };
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

    function jumlahRincian(data, jenisKomponen) {
        return (data.rincian || [])
            .filter(function (r) { return r.jenis_komponen === jenisKomponen; })
            .reduce(function (sum, r) { return sum + (parseFloat(r.nominal) || 0); }, 0);
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
            <div class="kop-title">BADAN PUSAT STATISTIK</div>
            <div class="kop-subtitle">KABUPATEN MURUNG RAYA</div>
            <div class="kop-address">${kopAlamat}</div>
            <div class="kop-line-thick"></div>
            <div class="kop-line-thin"></div>
            <div class="dokumen-title">SURAT TUGAS</div>
            <div class="dokumen-nomor">NOMOR ${esc(data.no_surat_tugas) || '...........................'}</div>
            <p><strong>Menimbang</strong> : a. Bahwa dalam rangka sebagai wujud komitmen Badan Pusat Statistik sebagai penyedia data yang berkualitas;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;b. Bahwa untuk memenuhi ketentuan butir a, maka perlu menugaskan pegawai tersebut dalam Surat Tugas ini untuk melakukan kegiatan tersebut;</p>
            <p><strong>Mengingat</strong> : 1. Undang-Undang No.16 Tahun 1997 tentang Statistik;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;2. Peraturan Pemerintah No. 51 Tahun 1999 tentang Pedoman Penyelenggaraan Statistik;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;3. Keputusan Presiden RI No. 72 Tahun 2004 tentang Pedoman Pelaksanaan APBN;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;4. Keputusan Presiden Nomor 1 Tahun 2025 tentang Badan Pusat Statistik;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;5. Keputusan Presiden RI No. 103 Tahun 2001 tentang Kedudukan, Tugas, Fungsi, Kewenangan, Susunan Organisasi dan Tata Cara Kerja Pemerintah Non Departemen;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;6. Peraturan Menteri Keuangan No. 113/PMK.05/2012 tentang Perjalanan Dinas Dalam Negeri Bagi Pejabat Negara, Pegawai Negeri, dan Pegawai Tidak Tetap (Berita Negara Republik Indonesia Tahun 2011 Nomor 678);<br>
            &nbsp;&nbsp;&nbsp;&nbsp;7. Peraturan Kepala Badan Pusat Statistik No. 103 tahun 2014 tentang pelaksanaan Perjalanan dinas jabatan di lingkungan Badan Pusat Statistik;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;8. Semua biaya yang timbul dengan diterbitkannya Surat Tugas ini dibebankan kepada DIPA BPS Kabupaten Murung Raya TA ${tahun}.</p>
            <p class="fw-bold mb-1">Memberi Perintah</p>
            <table class="dokumen-table">
                <tr><td width="26%">Kepada &nbsp; Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                <tr><td>Pangkat/Gol</td><td>: ${esc(pelaksana.pangkat) || '-'}${pelaksana.golongan ? ' / ' + esc(pelaksana.golongan) : ''}</td></tr>
                <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
            </table>
            <p><strong>Untuk</strong> : ${esc(data.perihal) || '...........................'}${data.desa_tujuan ? ' di ' + esc(data.desa_tujuan) : ''}, tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}</p>
            <div class="ttd-block">
                <div>Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_surat_tugas)}</div>
                <div>Kepala Badan Pusat Statistik</div>
                <div>Kabupaten Murung Raya</div>
                <div class="mt-5 fw-bold text-decoration-underline">${esc(penandatangan.nama) || '(...........................)'}</div>
            </div>
        `;
    }

    // ----- SPD -----
    function renderSpd(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const ppk = data.ppk || pegawaiKosong();
        const lama = hitungLamaHari(data.tanggal_mulai, data.tanggal_selesai);
        return `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kop-title" style="text-align:left;">BADAN PUSAT STATISTIK</div>
                    <div class="kop-subtitle" style="text-align:left;">KABUPATEN MURUNG RAYA</div>
                </div>
                <div class="text-end" style="font-size:0.75rem;">
                    <div>Nomor : ${esc(data.no_spd) || '...........................'}</div>
                    <div>Lembar: I</div>
                </div>
            </div>
            <div class="kop-line-thick"></div>
            <div class="kop-line-thin"></div>
            <div class="dokumen-title">SURAT PERJALANAN DINAS</div>
            <table class="dokumen-table dokumen-rincian mt-3">
                <tr><td width="5%">1</td><td width="45%">Pejabat Pembuat Komitmen</td><td>${esc(ppk.nama) || '...'}</td></tr>
                <tr><td>2</td><td>Nama/NIP Pegawai yang melaksanakan perjalanan dinas</td><td>${esc(pelaksana.nama) || '...'} / NIP. ${esc(fmtNip(pelaksana.nip))}</td></tr>
                <tr><td rowspan="3">3</td><td>a. Pangkat/Golongan</td><td>${esc(pelaksana.pangkat) || '-'}${pelaksana.golongan ? ' / ' + esc(pelaksana.golongan) : ''}</td></tr>
                <tr><td>b. Jabatan</td><td>${esc(pelaksana.jabatan) || '-'}</td></tr>
                <tr><td>c. Tingkat Biaya Perjalanan Dinas</td><td>${esc(data.tingkat_biaya) || '-'}</td></tr>
                <tr><td>4</td><td>Maksud Perjalanan Dinas</td><td>${esc(data.perihal) || '...'}</td></tr>
                <tr><td>5</td><td>Alat angkutan yang digunakan</td><td>${esc(data.angkutan) || '...'}</td></tr>
                <tr><td rowspan="2">6</td><td>a. Tempat Berangkat</td><td>${esc(data.desa_asal) || '...'}</td></tr>
                <tr><td>b. Tempat Tujuan</td><td>${esc(data.desa_tujuan) || '...'}</td></tr>
                <tr><td rowspan="3">7</td><td>a. Lamanya Perjalanan Dinas</td><td>${terbilangHari(lama)}</td></tr>
                <tr><td>b. Tanggal Berangkat</td><td>${fmtTanggalPanjang(data.tanggal_mulai)}</td></tr>
                <tr><td>c. Tanggal harus kembali</td><td>${fmtTanggalPanjang(data.tanggal_selesai)}</td></tr>
                <tr><td>8</td><td colspan="2">
                    Pengikut :
                    <table class="dokumen-table dokumen-rincian mt-1" style="font-size:0.75rem;">
                        <tr><th>Nama</th><th>Tanggal Lahir</th><th>Keterangan</th></tr>
                        <tr><td>1. -</td><td>-</td><td>-</td></tr>
                        <tr><td>2. -</td><td>-</td><td>-</td></tr>
                    </table>
                </td></tr>
                <tr><td rowspan="2">9</td><td>a. Instansi</td><td>Badan Pusat Statistik Kabupaten Murung Raya</td></tr>
                <tr><td>b. Program/Kegiatan/Output/(MAK)</td><td>${esc(data.pembebanan) || '...'}</td></tr>
                <tr><td>10</td><td>Keterangan Lain-Lain</td><td>Surat Tugas Nomor ${esc(data.no_surat_tugas) || '...'} tanggal ${fmtTanggalPanjang(data.tanggal_surat_tugas)}</td></tr>
            </table>
            <div class="ttd-block">
                <div>Dikeluarkan di : Puruk Cahu</div>
                <div>Pada Tanggal &nbsp;&nbsp;: ${fmtTanggalPanjang(data.tanggal_spd)}</div>
                <div>Pejabat Pembuat Komitmen,</div>
                <div class="mt-5 fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</div>
                <div>NIP. ${esc(fmtNip(ppk.nip))}</div>
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

        const uangHarian = jumlahRincian(data, 'uang_harian');
        const transport = jumlahRincian(data, 'transport');
        const penginapan = jumlahRincian(data, 'penginapan');
        const pengeluaranTotal = jumlahPengeluaran(data);

        const total = uangHarian + transport + penginapan + pengeluaranTotal;
        const rateHarian = lama > 0 ? Math.round(uangHarian / lama) : 0;
        const rateMalam = malam > 0 ? Math.round(penginapan / malam) : 0;
        const ref = spdAtauSt(data);

        return `
            <div class="dokumen-boxed">
            <div class="dokumen-title" style="text-decoration:none;">PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS</div>
            <table class="dokumen-table mt-3">
                <tr><td width="28%">Lampiran SPD Nomor</td><td>: ${esc(ref.nomor)}</td></tr>
                <tr><td>Tanggal</td><td>: ${fmtTanggalPanjang(ref.tanggal)}</td></tr>
            </table>
            <table class="dokumen-table dokumen-rincian">
                <tr>
                    <th width="5%">No</th>
                    <th>Perincian Biaya</th>
                    <th width="15%">Jumlah</th>
                    <th width="15%">Keterangan</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>
                        a. Nama yang bertugas : ${esc(pelaksana.nama) || '...'}<br>
                        b. Pangkat/Gol : ${esc(pelaksana.pangkat) || '-'}${pelaksana.golongan ? ' / ' + esc(pelaksana.golongan) : ''}<br>
                        c. Tujuan tugas : ${esc(data.kabupaten_tujuan) || '...'}<br>
                        d. Lamanya tugas : ${terbilangHari(lama)}
                    </td>
                    <td class="text-end">Rp. -</td>
                    <td></td>
                </tr>
                <tr><td>2</td><td>Transport : dari ${esc(data.desa_asal) || '...'} ke ${esc(data.desa_tujuan) || '...'}</td><td class="text-end">${transport > 0 ? fmtRupiah(transport) : 'Rp. -'}</td><td></td></tr>
                <tr><td>3</td><td>Uang harian perjadin : ${terbilangHari(lama)} x ${fmtRupiah(rateHarian)},-</td><td class="text-end">${fmtRupiah(uangHarian)}</td><td></td></tr>
                <tr><td>4</td><td>Penginapan : ${malam} (${terbilang(malam)}) malam x ${fmtRupiah(rateMalam)},-</td><td class="text-end">${penginapan > 0 ? fmtRupiah(penginapan) : 'Rp. -'}</td><td></td></tr>
                <tr><td>5</td><td>Pengeluaran Rill</td><td class="text-end">${pengeluaranTotal > 0 ? fmtRupiah(pengeluaranTotal) : 'Rp. -'}</td><td></td></tr>
                <tr><td colspan="2" class="text-center fw-bold">JUMLAH</td><td class="text-end fw-bold">${fmtRupiah(total)}</td><td></td></tr>
                <tr><td colspan="4" class="text-center fw-bold fst-italic">** ${terbilangRupiah(total)} **</td></tr>
            </table>
            <div class="text-end mb-3">Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}</div>
            <table class="dokumen-table">
                <tr>
                    <td width="50%">Telah dibayar sejumlah:<br><span class="fst-italic">${fmtRupiah(total)}</span></td>
                    <td>Telah menerima jumlah uang sebesar:<br><span class="fst-italic">${fmtRupiah(total)}</span></td>
                </tr>
            </table>
            <p>Lunas pada tanggal:</p>
            <table class="dokumen-table">
                <tr>
                    <td width="50%" class="text-center">
                        Bendahara Pengeluaran/PUMC<br>BPS Kabupaten Murung Raya<br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(bendahara.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(bendahara.nip))}
                    </td>
                    <td class="text-center">
                        Yang Menerima,<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(pelaksana.nip))}
                    </td>
                </tr>
            </table>
            <div class="dokumen-title mt-3" style="font-size:0.8rem;text-decoration:none;">PERHITUNGAN SPD RAMPUNG</div>
            <table class="dokumen-table">
                <tr><td width="35%">Ditetapkan sejumlah</td><td>: ${fmtRupiah(total)}</td></tr>
                <tr><td>Yang telah dibayar semula</td><td>: Rp. 0,-</td></tr>
                <tr><td>Sisa kurang / lebih</td><td>: ${fmtRupiah(total)}</td></tr>
            </table>
            </div>
            <div class="ttd-block">
                <div>Pejabat Pembuat Komitmen</div>
                <div>BPS Kabupaten Murung Raya</div>
                <div class="mt-5 fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</div>
                <div>NIP. ${esc(fmtNip(ppk.nip))}</div>
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
            <div class="dokumen-title">DAFTAR PENGELUARAN RIIL</div>
            <p>Yang bertanda tangan dibawah ini :</p>
            <table class="dokumen-table">
                <tr><td width="20%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
            </table>
            <p>Berdasarkan Surat Tugas Nomor: ${esc(data.no_surat_tugas) || '...........................'}, tanggal ${fmtTanggalPanjang(data.tanggal_surat_tugas)}. Dengan ini kami menyatakan dengan sesungguhnya bahwa:</p>
            <p>1. Biaya transport pegawai dan/atau biaya penginapan dibawah ini yang tidak dapat diperoleh bukti-buktinya, meliputi:</p>
            <table class="dokumen-table dokumen-rincian">
                <tr><th width="5%">No</th><th>Uraian</th><th width="20%">Jumlah (Rp)</th></tr>
                ${bodyRows}
                <tr><td colspan="2" class="text-end fw-bold">Jumlah</td><td class="text-end fw-bold">${fmtRupiah(total)}</td></tr>
            </table>
            <p>2. Jumlah uang tersebut pada angka 1 di atas benar-benar dikeluarkan untuk pelaksanaan perjalanan dinas dimaksud dan apabila dikemudian hari terdapat kelebihan atas pembayaran, kami bersedia untuk menyetorkan kelebihan tersebut ke Kas Negara.</p>
            <p>Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.</p>
            <div class="text-end mb-3">Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}</div>
            <table class="dokumen-table">
                <tr>
                    <td width="50%" class="text-center">
                        Mengetahui/Menyetujui<br>An. Kuasa Pengguna Anggaran<br>Pejabat Pembuat Komitmen<br>BPS Kabupaten Murung Raya<br><br>
                        <span class="fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(ppk.nip))}
                    </td>
                    <td class="text-center">
                        Pejabat Negara/ Pegawai Negeri<br>Yang melakukan Perjalanan Dinas<br><br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(pelaksana.nip))}
                    </td>
                </tr>
            </table>
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
            <table class="ms-auto" style="width:auto; font-size:0.75rem; margin-bottom:1rem;">
                <tr><td class="pe-2">Tahun Anggaran</td><td>: ${tahun}</td></tr>
                <tr><td class="pe-2">Nomor Bukti</td><td>:</td></tr>
                <tr><td class="pe-2">Akun</td><td>: ${esc(data.pembebanan) || '...........................'}</td></tr>
                <tr><td class="pe-2">Kepada</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                <tr><td class="pe-2">Satker</td><td>: ${satkerLabel}</td></tr>
            </table>
            <div class="dokumen-title">Kuitansi/Bukti Pembayaran</div>
            <table class="dokumen-table mt-3">
                <tr><td width="20%">Sudah terima dari</td><td>: Pejabat Pembuat Komitmen BPS Kabupaten Murung Raya</td></tr>
                <tr><td>Jumlah Uang</td><td>: <strong>${fmtRupiah(total)},-</strong></td></tr>
                <tr><td>Terbilang</td><td>: ** ${terbilangRupiah(total)} **</td></tr>
                <tr><td>Untuk pembayaran</td><td>: Biaya perjalanan dinas dalam rangka ${esc(data.perihal) || '...........................'}${data.desa_tujuan ? ' di ' + esc(data.desa_tujuan) : ''}, tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}, sesuai dengan:<br><br>
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
                        An. Kuasa Pengguna Anggaran<br>Pejabat Pembuat Komitmen<br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(ppk.nip))}
                    </td>
                    <td class="text-center">
                        Lunas dibayar,<br>Bendahara Pengeluaran<br><br><br>
                        <span class="fw-bold text-decoration-underline">${esc(bendahara.nama) || '(...........................)'}</span><br>
                        NIP. ${esc(fmtNip(bendahara.nip))}
                    </td>
                </tr>
            </table>
            <div class="kop-line-thin"></div>
            <p class="mt-3 mb-1">Barang/pekerjaan tersebut telah diterima/diselesaikan dengan lengkap dan baik</p>
            <p>Pejabat yang bertanggungjawab<br>Pejabat Pembuat Komitmen</p>
            <div class="mt-4 fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</div>
            <div>NIP. ${esc(fmtNip(ppk.nip))}</div>
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
                <div class="dokumen-title">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor">TIDAK MENGGUNAKAN KENDARAAN DINAS</div>
                <p>Yang bertanda tangan di bawah ini:</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Satuan Kerja</td><td>: BPS Kabupaten Murung Raya</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Menerangkan bahwa dalam rangka melaksanakan perjalanan dinas untuk melaksanakan tugas kedinasan sesuai surat tugas nomor: ${noSt} saya benar-benar tidak menggunakan kendaraan dinas.</p>
                <p>Demikian pernyataan ini kami buat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya. Apabila terdapat kekeliruan dalam pertanggungjawaban dan mengakibatkan kerugian negara, saya bersedia dituntut sesuai peraturan yang berlaku dan mengembalikan biaya transport atau biaya transport lokal yang sudah terlanjur saya terima ke kas negara.</p>
                <div class="ttd-block">
                    <div>Puruk Cahu, ${tglTtd}</div>
                    <div>Pelaksana Perjalanan Dinas,</div>
                    <div class="mt-5 fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</div>
                    <div>NIP. ${esc(fmtNip(pelaksana.nip))}</div>
                </div>
            `;
        }

        if (kondisi === 'tidak_menginap_hotel') {
            return `
                <div class="dokumen-title">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor">TIDAK MENGINAP DI HOTEL ATAU DI PENYEDIA JASA AKOMODASI LAINNYA</div>
                <p>Yang bertanda tangan di bawah ini:</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Pangkat</td><td>: ${esc(pelaksana.pangkat) || '-'}${pelaksana.golongan ? ' / ' + esc(pelaksana.golongan) : ''}</td></tr>
                    <tr><td>Satuan Kerja</td><td>: BPS Kabupaten Murung Raya</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Menerangkan bahwa selama melaksanakan perjalanan dinas dalam rangka ${esc(data.perihal) || '...........................'}${data.desa_tujuan ? ' di ' + esc(data.desa_tujuan) : ''} pada tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}, saya benar-benar tidak menginap di hotel atau jasa akomodasi komersial lainnya.</p>
                <p>Demikian pernyataan ini saya buat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya. Apabila terdapat kekeliruan dalam pertanggung jawaban SPD dan mengakibatkan kerugian negara, saya bersedia dituntut sesuai peraturan yang berlaku dan mengembalikan biaya kompensasi menginap di hotel atau jasa akomodasi komersial lainnya yang sudah terlanjur saya terima ke kas negara.</p>
                <div class="ttd-block">
                    <div>Puruk Cahu, ${tglTtd}</div>
                    <div>Pelaksana Perjalanan Dinas,</div>
                    <div class="mt-5 fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</div>
                    <div>NIP. ${esc(fmtNip(pelaksana.nip))}</div>
                </div>
            `;
        }

        if (kondisi === 'keterlambatan') {
            return `
                <div class="dokumen-title">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor">KETERLAMBATAN PENGAJUAN TAGIHAN</div>
                <p>Yang bertanda tangan di bawah ini:</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Dengan ini menyatakan sebagai berikut:</p>
                <p>1. Telah terjadi keterlambatan mengajukan hak tagihan kepada Pejabat Pembuat Komitmen (PPK) BPS Kabupaten Murung Raya atas pelaksanaan perjalanan dinas ${esc(data.perihal) || '...........................'} yang telah selesai dilaksanakan pada tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}.<br><br>
                Berdasarkan Surat Tugas Nomor: ${noSt} tanggal ${tglSt}${data.no_spd ? '<br>dan SPD Nomor: ' + noSpd + ' tanggal ' + tglSpd : ''}</p>
                <p>2. Keterlambatan mengajukan hak tagihan disebabkan oleh ${esc(keterangan) || '...........................'}</p>
                <p>3. Selanjutnya saya tidak akan mengalami keterlambatan kembali dalam pengajuan hak tagihan sesuai dengan batas waktu pengajuan hak tagihan kepada Negara yang diatur dalam PMK Nomor 190/PMK.05/2012 tentang Tata Cara Pembayaran dalam Rangka Pelaksanaan Anggaran Pendapatan dan Belanja Negara.</p>
                <p>Demikian pernyataan ini dibuat dengan sebenar-benarnya.</p>
                <div class="ttd-block">
                    <div>Puruk Cahu, ${tglTtd}</div>
                    <div>Yang menyatakan,</div>
                    <div class="mt-5 fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</div>
                    <div>NIP. ${esc(fmtNip(pelaksana.nip))}</div>
                </div>
            `;
        }

        return '';
    }

    function renderPernyataan(data) {
        const pelaksana = data.pelaksana || pegawaiKosong();
        const ppk = data.ppk || pegawaiKosong();
        const rows = data.pernyataan || [];
        let blocks = '';

        if (data.jenis_perjadin === 'dalam_kota_lebih_8_jam') {
            blocks += `
                <div class="dokumen-title">SURAT PERNYATAAN</div>
                <div class="dokumen-nomor">PERJALANAN DINAS DALAM KOTA LEBIH DARI 8 JAM</div>
                <p>Yang bertanda tangan di bawah ini :</p>
                <table class="dokumen-table">
                    <tr><td width="22%">Nama</td><td>: ${esc(pelaksana.nama) || '...........................'}</td></tr>
                    <tr><td>NIP</td><td>: ${esc(fmtNip(pelaksana.nip))}</td></tr>
                    <tr><td>Jabatan</td><td>: ${esc(pelaksana.jabatan) || '-'}</td></tr>
                </table>
                <p>Dengan ini menyatakan bahwa perjalanan dinas dalam kota yang saya laksanakan sebagai berikut :</p>
                <p>
                    a. Nomor Surat Tugas &nbsp;: ${esc(data.no_surat_tugas) || '...........................'}, tanggal ${fmtTanggalPanjang(data.tanggal_surat_tugas)}<br>
                    b. Nomor SPD &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ${esc(data.no_spd) || '...........................'}, tanggal ${fmtTanggalPanjang(data.tanggal_spd)}<br>
                    c. Tujuan &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ${esc(data.perihal) || '...........................'}${data.desa_tujuan ? ' di ' + esc(data.desa_tujuan) : ''} selama ${terbilangHari(hitungLamaHari(data.tanggal_mulai, data.tanggal_selesai))} pada tanggal ${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}
                </p>
                <p>Adalah benar dilaksanakan lebih dari 8 jam.</p>
                <p>Demikian pernyataan ini dibuat dengan sebenar-benarnya, dan saya sanggup menerima konsekuensi jika pernyataan ini tidak benar.</p>
                <div class="text-end mb-3">Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}</div>
                <table class="dokumen-table mt-3">
                    <tr>
                        <td width="50%" class="text-center">
                            Mengetahui,<br>BPS Kabupaten Murung Raya<br>Pejabat Pembuat Komitmen,<br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(ppk.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(ppk.nip))}
                        </td>
                        <td class="text-center">
                            <br>Pelaksana Perjalanan Dinas<br><br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(pelaksana.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(pelaksana.nip))}
                        </td>
                    </tr>
                </table>
                <hr>
            `;
        }

        if (!rows.length && !blocks) {
            return `
                <div class="dokumen-title">SURAT PERNYATAAN</div>
                <p class="text-center text-muted">Tidak ada surat pernyataan tambahan.</p>
            `;
        }

        rows.forEach(function (row) {
            blocks += suratPernyataanBlock(data, row.jenis_kondisi, row.keterangan) + '<hr>';
        });

        return blocks;
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
                return '<td class="dokumentasi-cell">' + inner + '</td>';
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
                <tr><td style="vertical-align:top;">IV.</td><td style="vertical-align:top;">Daerah Tujuan/Instansi</td><td style="vertical-align:top;">${esc(data.desa_tujuan) || '...........................'}${data.kabupaten_tujuan ? ', ' + esc(data.kabupaten_tujuan) : ''}</td></tr>
                <tr><td style="vertical-align:top;">V.</td><td style="vertical-align:top;">Waktu Pelaksanaan</td><td style="vertical-align:top;">${fmtRentangTanggal(data.tanggal_mulai, data.tanggal_selesai)}</td></tr>
                <tr><td style="vertical-align:top;">VI.</td><td colspan="2" style="vertical-align:top;">
                    Kesimpulan Hasil Kegiatan :<br>
                    <span style="text-align:justify;display:block;">${esc(data.kesimpulan_hasil_kegiatan) || '-'}</span>
                </td></tr>
                <tr><td style="vertical-align:top;">VII.</td><td colspan="2" style="vertical-align:top;">
                    Tindak Lanjut :<br>
                    <span style="text-align:justify;display:block;">${esc(data.tindak_lanjut) || '-'}</span>
                </td></tr>
            </table>

            <div class="dokumen-page-break">
                <div class="dokumen-page-num">- 2 -</div>
                <p>Demikian Laporan Perjalanan Dinas ini dibuat sebagai bahan Laporan.</p>
                <table class="dokumen-table mt-3">
                    <tr>
                        <td width="50%" class="text-center">
                            Mengetahui :<br>Kepala BPS Kabupaten Murung Raya,<br><br><br>
                            <span class="fw-bold text-decoration-underline">${esc(penandatangan.nama) || '(...........................)'}</span><br>
                            NIP. ${esc(fmtNip(penandatangan.nip))}
                        </td>
                        <td class="text-center">
                            Puruk Cahu, ${fmtTanggalPanjang(data.tanggal_kuitansi)}<br>Yang Melakukan Perjalanan Dinas,<br><br><br>
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

    function exportAsWord(html, filename) {
        const css = getDokumenCss();
        const content = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'
            + '<head><meta charset="utf-8"><style>body{font-family:"Times New Roman",serif;font-size:12pt;}' + css + '</style></head>'
            + '<body><div class="dokumen-preview">' + html + '</div></body></html>';
        downloadBlob(new Blob(['﻿' + content], { type: 'application/msword' }), filename + '.doc');
    }

    function exportAsExcel(html, filename) {
        const css = getDokumenCss();
        const content = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">'
            + '<head><meta charset="utf-8"><style>' + css + '</style></head>'
            + '<body><div class="dokumen-preview">' + html + '</div></body></html>';
        downloadBlob(new Blob(['﻿' + content], { type: 'application/vnd.ms-excel' }), filename + '.xls');
    }

    function exportAsPdf(html, title, cssUrl, pageSize) {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Popup diblokir browser. Izinkan popup untuk export PDF.');
            return;
        }
        // Margin di-nol-kan karena ukuran halaman fisik (mm) harus persis sama dengan
        // lebar/tinggi div .dokumen-a4 (lihat dokumen-perjadin.css) — kalau @page masih
        // punya margin sendiri, kontennya kepotong di tepi halaman. Padding div itu
        // sendiri yang jadi margin visualnya.
        const pageStyle = pageSize ? '<style>@page{size:' + pageSize + ';margin:0;}</style>' : '';
        const css = getDokumenCss();
        const previewClass = pageSize ? 'dokumen-preview dokumen-a4' : 'dokumen-preview';
        printWindow.document.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + esc(title) + '</title>'
            + '<link rel="stylesheet" href="' + cssUrl + '"><style>' + css + '</style>' + pageStyle + '</head>'
            + '<body><div class="' + previewClass + '">' + html + '</div></body></html>'
        );
        printWindow.document.close();
        printWindow.focus();
        printWindow.onload = function () {
            setTimeout(function () { printWindow.print(); }, 300);
        };
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
        exportAsWord: exportAsWord,
        exportAsExcel: exportAsExcel,
        exportAsPdf: exportAsPdf,
    };
})(window);
