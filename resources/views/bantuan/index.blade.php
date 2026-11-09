@extends('layouts.app')

@section('title', 'Bantuan & FAQ')

@php
    $alur = [
        ['ikon' => 'ti-click', 'judul' => 'Pilih Jenis', 'isi' => 'Dari Dashboard, pilih jenis perjalanan dinas: Biasa, Dalam Kota ≤ 8 Jam, atau Dalam Kota > 8 Jam.'],
        ['ikon' => 'ti-forms', 'judul' => 'Isi Form', 'isi' => 'Isi step demi step: Data Umum, SPD (jika perlu), Laporan, Pernyataan, Pengeluaran Riil, Rincian Biaya, Kuitansi.'],
        ['ikon' => 'ti-eye', 'judul' => 'Cek Preview', 'isi' => 'Setiap step menampilkan preview dokumen resmi secara langsung sesuai data yang diisi.'],
        ['ikon' => 'ti-device-floppy', 'judul' => 'Simpan & Kelola', 'isi' => 'Setelah disimpan, dokumen bisa dilihat, diduplikat, atau diexport dari menu Perjalanan Dinas.'],
    ];

    // Status tiap dokumen per jenis, urut sesuai step wizard (Surat Tugas s.d. Kuitansi).
    // Disamakan dengan aturan wizard: yang "wajib" harus lengkap sebelum "Selesai & Simpan"
    // (Data Umum, SPD bila perlu, Rincian Biaya, Laporan).
    $statusDokumen = [
        'wajib' => ['label' => 'Wajib', 'ikon' => 'ti-check'],
        'opsional' => ['label' => 'Opsional', 'ikon' => 'ti-circle-dashed'],
    ];
    $catatanDokumen = [
        // 'Laporan' => 'Kesimpulan, tindak lanjut & min. 1 dokumentasi',
        // 'Rincian Biaya' => 'Minimal uang harian',
        // 'Keterlambatan Pengajuan Tagihan' => 'Bila tagihan diajukan terlambat',
        // 'Kuitansi' => 'Tersusun dari data yang diisi',
    ];
    // Urutan dokumen dari Kuitansi (atas) sampai Form Permintaan (bawah); Surat Pernyataan dirinci
    // per jenis pernyataan (nama disamakan dengan pilihan di step Pernyataan).
    // 'sub' = baris di bawah judul grup.
    $namaDokumen = [
        ['nama' => 'Kuitansi'],
        ['nama' => 'Rincian Biaya'],
        ['nama' => 'Pengeluaran Riil'],
        ['grup' => 'Surat Pernyataan'],
        ['nama' => 'Lebih dari 8 Jam', 'sub' => true],
        ['nama' => 'Tidak Menggunakan Kendaraan Dinas', 'sub' => true],
        ['nama' => 'Tidak Menginap di Hotel/Akomodasi', 'sub' => true],
        ['nama' => 'Keterlambatan Pengajuan Tagihan', 'sub' => true],
        ['nama' => 'Laporan'],
        ['nama' => 'SPD'],
        ['nama' => 'Surat Tugas'],
        ['nama' => 'Form Permintaan'],
    ];
    $kelengkapan = [
        ['judul' => 'Perjalanan Dinas Biasa', 'ket' => 'Perjalanan ke luar kota', 'ikon' => 'ti-plane-departure', 'status' => [
            'Form Permintaan' => 'wajib', 'Surat Tugas' => 'wajib', 'SPD' => 'wajib', 'Laporan' => 'wajib',
            'Tidak Menggunakan Kendaraan Dinas' => 'opsional',
            'Tidak Menginap di Hotel/Akomodasi' => 'opsional', 'Keterlambatan Pengajuan Tagihan' => 'opsional',
            'Pengeluaran Riil' => 'opsional', 'Rincian Biaya' => 'wajib', 'Kuitansi' => 'wajib',
        ]],
        ['judul' => 'Dalam Kota ≤ 8 Jam', 'ket' => 'Dalam kota, maksimal 8 jam', 'ikon' => 'ti-clock', 'status' => [
            'Form Permintaan' => 'wajib', 'Surat Tugas' => 'wajib', 'Laporan' => 'wajib',
            'Tidak Menggunakan Kendaraan Dinas' => 'opsional',
            'Tidak Menginap di Hotel/Akomodasi' => 'opsional', 'Keterlambatan Pengajuan Tagihan' => 'opsional',
            'Pengeluaran Riil' => 'opsional', 'Rincian Biaya' => 'wajib', 'Kuitansi' => 'wajib',
        ]],
        ['judul' => 'Dalam Kota > 8 Jam', 'ket' => 'Dalam kota, lebih dari 8 jam', 'ikon' => 'ti-map-pin', 'status' => [
            'Form Permintaan' => 'wajib', 'Surat Tugas' => 'wajib', 'SPD' => 'wajib', 'Laporan' => 'wajib',
            'Lebih dari 8 Jam' => 'wajib', 'Tidak Menggunakan Kendaraan Dinas' => 'opsional',
            'Tidak Menginap di Hotel/Akomodasi' => 'opsional', 'Keterlambatan Pengajuan Tagihan' => 'opsional',
            'Pengeluaran Riil' => 'opsional', 'Rincian Biaya' => 'wajib', 'Kuitansi' => 'wajib',
        ]],
    ];

    $penjelasan = [
        ['ikon' => 'ti-file-text', 'warna' => 'primary', 'judul' => 'Surat Tugas', 'isi' => 'Surat resmi dari Kepala BPS yang menugaskan pegawai/mitra untuk melaksanakan kegiatan tertentu.'],
        ['ikon' => 'ti-car', 'warna' => 'info', 'judul' => 'SPD (Surat Perjalanan Dinas)', 'isi' => 'Mencatat rincian perjalanan: tempat berangkat/tujuan, waktu, alat angkut, dan pembebanan anggaran.'],
        ['ikon' => 'ti-report-money', 'warna' => 'success', 'judul' => 'Rincian Biaya', 'isi' => 'Perhitungan komponen biaya perjalanan: uang harian, transport, dan penginapan.'],
        ['ikon' => 'ti-receipt', 'warna' => 'warning', 'judul' => 'Pengeluaran Riil', 'isi' => 'Daftar biaya transport/penginapan yang tidak dapat dibuktikan dengan kuitansi resmi (dibayar sesuai kondisi riil di lapangan).'],
        ['ikon' => 'ti-file-invoice', 'warna' => 'primary', 'judul' => 'Kuitansi', 'isi' => 'Bukti pembayaran/tanda terima uang perjalanan dinas dari Pejabat Pembuat Komitmen.'],
        ['ikon' => 'ti-writing-sign', 'warna' => 'danger', 'judul' => 'Surat Pernyataan', 'isi' => 'Pernyataan tambahan sesuai kondisi: tidak pakai kendaraan dinas, tidak menginap hotel, atau keterlambatan pengajuan tagihan.'],
        ['ikon' => 'ti-report', 'warna' => 'secondary', 'judul' => 'Laporan', 'isi' => 'Ringkasan hasil kegiatan dan tindak lanjut setelah perjalanan dinas selesai dilaksanakan.'],
    ];
@endphp

@push('page-css')
    <style>
        .bantuan {
            --rgb-primary: 37, 99, 235;
            --rgb-success: 40, 199, 111;
            --rgb-warning: 255, 159, 67;
            --rgb-info: 0, 186, 209;
            --rgb-danger: 234, 84, 85;
            --rgb-secondary: 130, 134, 139;
        }
        .bantuan .w-primary { --w: var(--rgb-primary); }
        .bantuan .w-success { --w: var(--rgb-success); }
        .bantuan .w-warning { --w: var(--rgb-warning); }
        .bantuan .w-info { --w: var(--rgb-info); }
        .bantuan .w-danger { --w: var(--rgb-danger); }
        .bantuan .w-secondary { --w: var(--rgb-secondary); }

        .judul-bagian-bantuan { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; }
        .judul-bagian-bantuan .ikon-bagian {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
        }
        .judul-bagian-bantuan h5 { margin: 0; font-weight: 700; }
        .judul-bagian-bantuan p { margin: 0; font-size: 0.8125rem; color: #6b7896; }

        /* Alur: langkah bernomor dengan garis penghubung */
        .alur-bantuan { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
        .langkah-bantuan { position: relative; text-align: center; padding: 0 0.5rem; }
        .langkah-bantuan:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 28px;
            left: calc(50% + 36px);
            right: calc(-50% - 1.25rem + 36px);
            border-top: 2px dashed #c7d5f5;
        }
        .langkah-bantuan .bulatan {
            position: relative;
            width: 56px;
            height: 56px;
            margin: 0 auto 0.9rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            background: linear-gradient(135deg, #3D5EE1 0%, #1B449C 100%);
            box-shadow: 0 8px 18px -8px rgba(27, 68, 156, 0.8);
        }
        .langkah-bantuan .nomor {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #2563eb;
            background: #fff;
            border: 2px solid #2563eb;
        }
        .langkah-bantuan h6 { font-weight: 700; margin-bottom: 0.35rem; }
        .langkah-bantuan p { font-size: 0.8125rem; color: #6b7896; margin: 0; }

        /* Kelengkapan per jenis: kartu polos, satu aksen biru */
        .kartu-jenis-bantuan {
            height: 100%;
            padding: 1.25rem;
            border-radius: 0.625rem;
            border: 1px solid #e3e7f1;
            background: #fff;
        }
        .kartu-jenis-bantuan .kepala {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 0.9rem;
            margin-bottom: 0.25rem;
            border-bottom: 1px solid #eef1f7;
        }
        .kartu-jenis-bantuan .ikon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
        }
        .kartu-jenis-bantuan h6 { margin: 0; font-weight: 700; }
        .kartu-jenis-bantuan .kepala small { color: #8a94ad; }
        .daftar-cek { list-style: none; padding: 0; margin: 0; }
        .daftar-cek li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0;
            border-bottom: 1px dashed #eef1f7;
            font-size: 0.875rem;
        }
        .daftar-cek li:last-child { border-bottom: 0; padding-bottom: 0; }
        .daftar-cek li.grup { padding-bottom: 0.15rem; border-bottom: 0; }
        .daftar-cek li.sub { padding-left: 0.9rem; font-size: 0.8125rem; position: relative; }
        .daftar-cek li.sub::before {
            content: "";
            position: absolute;
            left: 0.2rem;
            top: calc(0.55rem + 0.55em);
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #c3c9d6;
        }
        .daftar-cek .catatan-dok { display: block; font-size: 0.75rem; color: #a1a8b8; line-height: 1.35; }
        .daftar-cek .status {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .daftar-cek .status i { font-size: 0.95rem; }
        .daftar-cek .wajib { color: #2563eb; }
        .daftar-cek .opsional { color: #8a94ad; font-weight: 500; }
        .info-bantuan {
            display: flex;
            gap: 0.6rem;
            margin-top: 1.25rem;
            padding: 0.85rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #34507f;
            background: rgba(37, 99, 235, 0.07);
        }
        .info-bantuan i { color: #2563eb; font-size: 1.15rem; flex-shrink: 0; }

        /* Penjelasan dokumen */
        .kartu-dokumen-bantuan {
            display: flex;
            gap: 0.9rem;
            height: 100%;
            padding: 1rem 1.1rem;
            border-radius: 0.625rem;
            border: 1px solid #e6eaf3;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .kartu-dokumen-bantuan:hover { border-color: rgba(var(--w), 0.35); box-shadow: 0 10px 22px -14px rgba(var(--w), 0.6); }
        .kartu-dokumen-bantuan .ikon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: rgb(var(--w));
            background: rgba(var(--w), 0.12);
        }
        .kartu-dokumen-bantuan h6 { margin-bottom: 0.25rem; font-weight: 700; }
        .kartu-dokumen-bantuan p { margin: 0; font-size: 0.8125rem; color: #6b7896; }

        /* Ajakan feedback */
        .cta-bantuan {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 1.75rem;
            border-radius: 0.75rem;
            color: #fff;
            background: linear-gradient(72.47deg, #1B449C 22.16%, #3D5EE1 76.47%);
        }
        .cta-bantuan::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: -60px;
            top: -110px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }
        .cta-bantuan h5 { color: #fff; margin-bottom: 0.25rem; font-weight: 700; }
        .cta-bantuan p { margin: 0; color: rgba(255, 255, 255, 0.8); }
        .cta-bantuan .btn {
            position: relative;
            z-index: 1;
            font-weight: 600;
            color: #1B449C;
            background: #fff;
            border-color: #fff;
            box-shadow: 0 8px 18px -8px rgba(10, 25, 70, 0.6);
            transition: transform 0.15s ease;
        }
        .cta-bantuan .btn:hover { color: #1B449C; background: #fff; transform: translateY(-2px); }

        @media (max-width: 767.98px) {
            .alur-bantuan { grid-template-columns: 1fr 1fr; }
            .langkah-bantuan::after { display: none; }
        }
    </style>
@endpush

@section('content')
    <div class="bantuan">
        <div class="mb-4">
            <h4 class="fw-bold mb-1">Bantuan &amp; FAQ</h4>
            <p class="text-muted mb-0">Panduan alur pengisian, kelengkapan dokumen, dan penjelasan setiap dokumen perjalanan dinas.</p>
        </div>

        {{-- Alur singkat --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="judul-bagian-bantuan">
                    <span class="ikon-bagian"><i class="ti ti-route"></i></span>
                    <div>
                        <h5>Alur Membuat Perjalanan Dinas</h5>
                        <p>Empat langkah dari memilih jenis sampai dokumen siap diexport.</p>
                    </div>
                </div>
                <div class="alur-bantuan">
                    @foreach ($alur as $langkah)
                        <div class="langkah-bantuan">
                            <div class="bulatan">
                                <i class="ti {{ $langkah['ikon'] }}"></i>
                                <span class="nomor">{{ $loop->iteration }}</span>
                            </div>
                            <h6>{{ $langkah['judul'] }}</h6>
                            <p>{{ $langkah['isi'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Kelengkapan dokumen per jenis --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="judul-bagian-bantuan">
                    <span class="ikon-bagian"><i class="ti ti-checklist"></i></span>
                    <div>
                        <h5>Kelengkapan Dokumen per Jenis</h5>
                        <p>Daftar disusun mengikuti urutan berkas SPJ, dari lembar teratas hingga terbawah.</p>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach ($kelengkapan as $jenis)
                        <div class="col-md-4">
                            <div class="kartu-jenis-bantuan">
                                <div class="kepala">
                                    <span class="ikon"><i class="ti {{ $jenis['ikon'] }}"></i></span>
                                    <div>
                                        <h6>{{ $jenis['judul'] }}</h6>
                                        <small>{{ $jenis['ket'] }}</small>
                                    </div>
                                </div>
                                <ul class="daftar-cek">
                                    @foreach ($namaDokumen as $baris)
                                        @if (isset($baris['grup']))
                                            <li class="grup">{{ $baris['grup'] }}</li>
                                            @continue
                                        @endif
                                        @php($nama = $baris['nama'])
                                        {{-- Dokumen yang tidak dipakai jenis ini (tidak ada di 'status') tidak ditampilkan. --}}
                                        @continue(! isset($jenis['status'][$nama]))
                                        @php($status = $jenis['status'][$nama])
                                        @php($catatan = $catatanDokumen[$nama] ?? null)
                                        <li class="{{ !empty($baris['sub']) ? 'sub' : '' }}">
                                            <span>
                                                {{ $nama }}
                                                @if ($catatan)
                                                    <small class="catatan-dok">{{ $catatan }}</small>
                                                @endif
                                            </span>
                                            <span class="status {{ $status }}"><i class="ti {{ $statusDokumen[$status]['ikon'] }}"></i> {{ $statusDokumen[$status]['label'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{-- <div class="info-bantuan">
                    <i class="ti ti-info-circle"></i>
                    <span>
                        Saat menyusun berkas SPJ, susun dokumen <b>persis sesuai urutan daftar di atas</b>: Kuitansi
                        diletakkan paling atas, lalu dilanjutkan ke bawah hingga Form Permintaan di bagian paling akhir.
                        <b>Wajib</b> harus lengkap sebelum &ldquo;Selesai &amp; Simpan&rdquo; (draft boleh belum lengkap),
                        sedangkan <b>Opsional</b> cukup disertakan bila ada dan boleh disusulkan setelah perjalanan selesai.
                    </span>
                </div> --}}
            </div>
        </div>

        {{-- Penjelasan tiap dokumen --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="judul-bagian-bantuan">
                    <span class="ikon-bagian"><i class="ti ti-files"></i></span>
                    <div>
                        <h5>Penjelasan Tiap Dokumen</h5>
                        <p>Fungsi masing-masing dokumen dalam berkas pertanggungjawaban.</p>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach ($penjelasan as $dok)
                        <div class="col-md-6 col-xl-4">
                            <div class="kartu-dokumen-bantuan w-{{ $dok['warna'] }}">
                                <span class="ikon"><i class="ti {{ $dok['ikon'] }}"></i></span>
                                <div>
                                    <h6>{{ $dok['judul'] }}</h6>
                                    <p>{{ $dok['isi'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ajakan ke halaman feedback --}}
        <div class="cta-bantuan mb-4">
            <div>
                <h5>Masih ada kendala atau pertanyaan?</h5>
                <p>Sampaikan lewat halaman Feedback, administrator akan menindaklanjutinya.</p>
            </div>
            <a href="{{ route('feedback.index') }}" class="btn btn-light d-inline-flex align-items-center gap-1">
                <i class="ti ti-message-report"></i> Kirim Feedback
            </a>
        </div>
    </div>

    {{-- FAQ --}}
    {{-- <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Pertanyaan yang Sering Ditanyakan</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="accordion-faq">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq-1">
                            Kenapa saya tidak bisa membuat perjalanan dinas?
                        </button>
                    </h2>
                    <div id="faq-1" class="accordion-collapse collapse show" data-bs-parent="#accordion-faq">
                        <div class="accordion-body">
                            Pemohon perjalanan dinas otomatis diambil dari identitas pegawai yang tertaut ke akun Anda. Kalau akun belum
                            tertaut ke data pegawai, lengkapi dulu di halaman <a href="{{ route('profile.edit') }}">Profil</a> — isi NIP,
                            nama, jabatan, pangkat, dan golongan, lalu simpan.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-2">
                            Apa bedanya "Perjalanan Dinas Saya" dan "Semua Perjalanan Dinas"?
                        </button>
                    </h2>
                    <div id="faq-2" class="accordion-collapse collapse" data-bs-parent="#accordion-faq">
                        <div class="accordion-body">
                            "Perjalanan Dinas Saya" hanya menampilkan perjalanan dinas yang Anda ajukan sebagai pemohon. "Semua Perjalanan
                            Dinas" menampilkan rekap dari seluruh pemohon.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-3">
                            Bagaimana cara menduplikat perjalanan dinas yang sudah ada?
                        </button>
                    </h2>
                    <div id="faq-3" class="accordion-collapse collapse" data-bs-parent="#accordion-faq">
                        <div class="accordion-body">
                            Klik tombol salin (ikon copy) pada kolom Aksi di tabel rekap. Wizard baru akan terbuka dengan semua data
                            terisi otomatis dari record tersebut, tinggal disesuaikan lalu disimpan sebagai perjalanan dinas baru.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-4">
                            Bagaimana cara export dokumen ke Word/PDF/Excel?
                        </button>
                    </h2>
                    <div id="faq-4" class="accordion-collapse collapse" data-bs-parent="#accordion-faq">
                        <div class="accordion-body">
                            Klik tombol mata (Lihat) atau unduh (Export) pada kolom Aksi. Di jendela yang muncul, pilih tab dokumen yang
                            ingin diunduh, lalu pilih format (Word, Excel, atau PDF) dari dropdown Export.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-5">
                            Apakah semua data wajib diisi sekaligus saat membuat perjalanan dinas?
                        </button>
                    </h2>
                    <div id="faq-5" class="accordion-collapse collapse" data-bs-parent="#accordion-faq">
                        <div class="accordion-body">
                            Tidak. Data Umum, SPD (jika perlu), dan minimal 1 komponen Rincian Biaya wajib diisi. Pengeluaran Riil,
                            Kuitansi, Surat Pernyataan tambahan, Laporan, dan Dokumentasi sifatnya opsional dan boleh disusulkan
                            setelah perjalanan selesai (lewat fitur Copy untuk memperbarui data).
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-6">
                            Kenapa step SPD tidak muncul di wizard saya?
                        </button>
                    </h2>
                    <div id="faq-6" class="accordion-collapse collapse" data-bs-parent="#accordion-faq">
                        <div class="accordion-body">
                            SPD hanya wajib untuk jenis "Biasa" dan "Dalam Kota &gt; 8 Jam". Untuk jenis "Dalam Kota &le; 8 Jam", SPD
                            tidak diperlukan sehingga step-nya otomatis disembunyikan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
@endsection
