@extends('layouts.app')

@section('title', 'Bantuan & FAQ')

@section('content')
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Bantuan &amp; FAQ</h4>
        <p class="text-muted mb-0">Panduan alur pengisian, kelengkapan dokumen, dan pertanyaan yang sering ditanyakan.</p>
    </div>

    {{-- Alur singkat --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Alur Membuat Perjalanan Dinas</h5>
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">1</span></span>
                        <span class="fw-semibold">Pilih Jenis</span>
                    </div>
                    <p class="text-muted small mb-0">Dari Dashboard, pilih jenis perjalanan dinas: Biasa, Dalam Kota ≤8 Jam, atau Dalam Kota &gt;8 Jam.</p>
                </div>
                <div class="col-md-3 col-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">2</span></span>
                        <span class="fw-semibold">Isi Form</span>
                    </div>
                    <p class="text-muted small mb-0">Isi step demi step: Data Umum, SPD (jika perlu), Rincian Biaya, Pengeluaran Riil, Kuitansi, Pernyataan, Laporan.</p>
                </div>
                <div class="col-md-3 col-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">3</span></span>
                        <span class="fw-semibold">Cek Preview</span>
                    </div>
                    <p class="text-muted small mb-0">Setiap step menampilkan preview dokumen resmi secara langsung sesuai data yang diisi.</p>
                </div>
                <div class="col-md-3 col-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">4</span></span>
                        <span class="fw-semibold">Simpan &amp; Kelola</span>
                    </div>
                    <p class="text-muted small mb-0">Setelah disimpan, dokumen bisa dilihat, diduplikat, atau diexport dari menu Perjalanan Dinas.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Kelengkapan dokumen per jenis --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Kelengkapan Dokumen per Jenis Perjalanan Dinas</h5>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Jenis Perjalanan Dinas</th>
                        <th class="text-center">Surat Tugas</th>
                        <th class="text-center">SPD</th>
                        <th>Keterangan Tambahan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="badge bg-label-primary">Biasa</span>
                            <div class="small text-muted mt-1">Perjalanan ke luar kota</div>
                        </td>
                        <td class="text-center"><i class="ti ti-circle-check text-success"></i> Wajib</td>
                        <td class="text-center"><i class="ti ti-circle-check text-success"></i> Wajib</td>
                        <td class="text-muted small">-</td>
                    </tr>
                    <tr>
                        <td>
                            <span class="badge bg-label-success">Dalam Kota &le; 8 Jam</span>
                            <div class="small text-muted mt-1">Dalam kota, maksimal 8 jam</div>
                        </td>
                        <td class="text-center"><i class="ti ti-circle-check text-success"></i> Wajib</td>
                        <td class="text-center"><i class="ti ti-circle-x text-muted"></i> Tidak perlu</td>
                        <td class="text-muted small">-</td>
                    </tr>
                    <tr>
                        <td>
                            <span class="badge bg-label-warning">Dalam Kota &gt; 8 Jam</span>
                            <div class="small text-muted mt-1">Dalam kota, lebih dari 8 jam</div>
                        </td>
                        <td class="text-center"><i class="ti ti-circle-check text-success"></i> Wajib</td>
                        <td class="text-center"><i class="ti ti-circle-check text-success"></i> Wajib</td>
                        <td class="text-muted small">Surat Pernyataan "Lebih dari 8 Jam" otomatis dibuatkan</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-0">
                Rincian Biaya, Pengeluaran Riil, Kuitansi, Surat Pernyataan tambahan, dan Laporan berlaku untuk semua jenis —
                Rincian Biaya wajib diisi minimal 1 komponen, sisanya opsional dan boleh disusulkan setelah perjalanan selesai.
            </p>
        </div>
    </div>

    {{-- Penjelasan tiap dokumen --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Penjelasan Tiap Dokumen</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="avatar flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-primary"><i class="ti ti-file-text"></i></span></span>
                        <div>
                            <h6 class="mb-1">Surat Tugas</h6>
                            <p class="text-muted small mb-0">Surat resmi dari Kepala BPS yang menugaskan pegawai/mitra untuk melaksanakan kegiatan tertentu.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="avatar flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-info"><i class="ti ti-car"></i></span></span>
                        <div>
                            <h6 class="mb-1">SPD (Surat Perjalanan Dinas)</h6>
                            <p class="text-muted small mb-0">Mencatat rincian perjalanan: tempat berangkat/tujuan, waktu, alat angkut, dan pembebanan anggaran.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="avatar flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-success"><i class="ti ti-report-money"></i></span></span>
                        <div>
                            <h6 class="mb-1">Rincian Biaya</h6>
                            <p class="text-muted small mb-0">Perhitungan komponen biaya perjalanan: uang harian, transport, dan penginapan.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="avatar flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-warning"><i class="ti ti-receipt"></i></span></span>
                        <div>
                            <h6 class="mb-1">Pengeluaran Riil</h6>
                            <p class="text-muted small mb-0">Daftar biaya transport/penginapan yang tidak dapat dibuktikan dengan kuitansi resmi (dibayar sesuai kondisi riil di lapangan).</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="avatar flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-primary"><i class="ti ti-file-invoice"></i></span></span>
                        <div>
                            <h6 class="mb-1">Kuitansi</h6>
                            <p class="text-muted small mb-0">Bukti pembayaran/tanda terima uang perjalanan dinas dari Pejabat Pembuat Komitmen.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="avatar flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-danger"><i class="ti ti-writing-sign"></i></span></span>
                        <div>
                            <h6 class="mb-1">Surat Pernyataan</h6>
                            <p class="text-muted small mb-0">Pernyataan tambahan sesuai kondisi: tidak pakai kendaraan dinas, tidak menginap hotel, atau keterlambatan pengajuan tagihan.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3">
                        <span class="avatar flex-shrink-0"><span class="avatar-initial rounded-circle bg-label-secondary"><i class="ti ti-report"></i></span></span>
                        <div>
                            <h6 class="mb-1">Laporan</h6>
                            <p class="text-muted small mb-0">Ringkasan hasil kegiatan dan tindak lanjut setelah perjalanan dinas selesai dilaksanakan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="card">
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
    </div>
@endsection
