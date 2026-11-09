@extends('layouts.app')

@section('title', 'Monitoring SPJ')

@push('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        /* Baris kepala kelompok GUP (DataTables RowGroup). Isinya dibuat "sticky" selebar area
           tabel yang terlihat, jadi total GUP tetap tampil di kanan walau tabel di-scroll. */
        #table-monitoring-spj tbody tr.baris-gup > td {
            padding: 0;
            background: #eaf0fd !important;
            border-top: 2px solid #c9d7f6;
            border-bottom: 1px solid #c9d7f6;
        }
        #table-monitoring-spj tbody tr.baris-gup.tanpa-gup > td {
            background: #f4f5f8 !important;
            border-top-color: #dfe2ea;
            border-bottom-color: #dfe2ea;
        }
        .gup-kepala-isi {
            position: sticky;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.7rem 1rem;
        }
        .gup-kepala-isi .kiri { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
        .gup-kepala-isi .ikon {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: #fff;
            background: #2563eb;
        }
        .tanpa-gup .gup-kepala-isi .ikon { color: #6b7896; background: #e3e6ee; }
        .gup-kepala-isi .judul { font-weight: 700; color: #1B449C; line-height: 1.25; }
        .tanpa-gup .gup-kepala-isi .judul { color: #4b5a7c; }
        .gup-kepala-isi .sub { font-size: 0.8125rem; color: #6b7896; }
        .gup-kepala-isi .kanan { display: flex; align-items: center; gap: 1rem; flex-shrink: 0; text-align: right; }
        .gup-kepala-isi .jumlah { font-size: 0.8125rem; color: #6b7896; }
        .gup-kepala-isi .total { font-size: 1rem; font-weight: 800; color: #1B449C; font-variant-numeric: tabular-nums; }
        .tanpa-gup .gup-kepala-isi .total { color: #4b5a7c; }
        .gup-kepala-isi .aksi-gup .btn { padding: 0.2rem 0.45rem; }

        #table-monitoring-spj_wrapper .dataTables_filter input {
            min-width: 220px;
        }

        #table-monitoring-spj thead th,
        #table-monitoring-spj_wrapper .dataTables_scrollHead thead th {
            text-transform: uppercase;
            font-size: var(--bs-body-font-size);
            letter-spacing: 0.4px;
            color: #fff;
            background-color: #1B449C;
            border-bottom: 2px solid #1B449C;
            white-space: nowrap;
            text-align: center;
            vertical-align: middle;
        }

        #table-monitoring-spj tbody tr:nth-child(even) {
            background-color: #f8f7fc;
        }

        #table-monitoring-spj tbody tr:hover {
            background-color: #eef0fb;
        }

        /* Teks isi tabel dibuat gelap (bawaan tema abu-abu, kurang terbaca). Badge tetap
           pakai warnanya sendiri karena kelas bg-label-* menimpa warna teks. */
        #table-monitoring-spj td {
            vertical-align: middle;
            color: #212529;
        }

        #table-monitoring-spj td.kolom-uraian {
            min-width: 350px;
        }

        #table-monitoring-spj .rekap-nomor {
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.8rem;
        }

        /* Modal scrollable: <form> membungkus header/body/footer, jadi form-nya yang
           dijadikan flex column supaya .modal-body bisa scroll dan footer tetap kelihatan. */
        .judul-isian-spj {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #1B449C;
            padding-bottom: 0.5rem;
            margin-bottom: 0.9rem;
            border-bottom: 1px solid #eef1f7;
        }

        .kotak-kelengkapan-spj {
            padding: 0.85rem 1rem;
            border-radius: 0.5rem;
            background: #f6f8fd;
            border: 1px solid #e6ebf6;
        }

        .modal-dialog-scrollable .modal-content > form {
            display: flex;
            flex-direction: column;
            max-height: 100%;
            overflow: hidden;
        }
    </style>
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Monitoring SPJ</h4>
        {{-- <p class="text-muted mb-0">
            Pemantauan pertanggungjawaban perjalanan dinas yang sudah selesai. Pembebanan, tanggal, uraian, nominal, dan nama penerima
            diambil otomatis dari data perjalanan dinas; kolom lainnya diisi oleh PPK / PPSPM / Bendahara.
        </p> --}}
    </div>

    @php
        $fmtTanggal = fn ($tgl) => $tgl ? $tgl->format('d/m/Y') : '';
        // Badge status Ya/Tidak; teksnya sama dengan pilihan di modal dan ikut ter-export ke Excel.
        $badgeStatus = fn ($nilai, $labelSudah, $labelBelum) => $nilai
            ? '<span class="badge rounded-pill bg-label-success"><i class="ti ti-circle-check ti-xs me-1"></i>' . $labelSudah . '</span>'
            : '<span class="badge rounded-pill bg-label-secondary"><i class="ti ti-clock ti-xs me-1"></i>' . $labelBelum . '</span>';
        $caraBayarBadge = ['tunai' => 'warning', 'transfer' => 'info'];
        $posisiBadge = ['PPK' => 'info', 'PPSPM' => 'primary', 'Bendahara' => 'success', 'Dikembalikan' => 'danger'];
    @endphp

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0"><i class="ti ti-clipboard-check me-1"></i> Daftar SPJ</h5>
            <div class="d-flex gap-2 flex-wrap">
                @if ($bisaIsi)
                    <button type="button" class="btn btn-primary" onclick="bukaModalRincianBaru(null)">
                        <i class="ti ti-plus me-1"></i> Tambah Rincian SPJ
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-label-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-file-spreadsheet ti-sm me-1"></i> Import Excel
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('monitoring-spj.rincian.import-template') }}">
                                    <i class="ti ti-download me-1"></i> Unduh Template
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal-import-rincian">
                                    <i class="ti ti-upload me-1"></i> Import Data
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif
                {{-- Dipindah ke sebelah tombol "Kolom" oleh atur-kolom.js (data-filter-tabel). --}}
                <div data-filter-tabel="table-monitoring-spj">
                    <select id="filter-posisi-spj" class="form-select" title="Filter posisi SPJ">
                        <option value="">Semua Posisi SPJ</option>
                        @foreach ($posisiSpj as $posisi)
                            <option value="{{ $posisi }}">{{ $posisi }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-filter-tabel="table-monitoring-spj">
                    <select id="filter-jenis-pembayaran" class="form-select" title="Filter jenis pembayaran">
                        <option value="">Semua Jenis Pembayaran</option>
                        @foreach ($jenisPembayaran as $jenis)
                            <option value="{{ $jenis }}">{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="monitoring-spj-export"></div>
            </div>
        </div>
        {{-- Bar aksi massal: tampil hanya saat ada baris dicentang, dipindah ke atas header tabel oleh JS di bawah --}}
        <div class="bulk-bar d-none" id="bulk-bar-spj">
            <span class="bulk-bar-info"><i class="ti ti-checks me-1"></i><span id="jumlah-terpilih">0</span> baris dipilih</span>
            <div class="d-flex gap-2 flex-wrap ms-2">
                <button type="button" class="btn btn-sm btn-warning" id="btn-bulk-edit" data-bs-toggle="modal" data-bs-target="#modal-bulk-spj">
                    <i class="ti ti-edit me-1"></i> Edit Terpilih
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-stack-2 me-1"></i> Kelompokkan ke GUP
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modal-gup-baru">
                                <i class="ti ti-square-plus me-2"></i>Buat GUP Baru
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modal-gup-tambah" @disabled($pilihanGup->isEmpty())>
                                <i class="ti ti-stack-push me-2"></i>Masukkan ke GUP yang Ada
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="dropdown-item text-danger" id="btn-keluarkan-gup">
                                <i class="ti ti-stack-pop me-2"></i>Keluarkan dari GUP
                            </button>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-sm btn-label-secondary" id="btn-batal-pilih-spj">
                    <i class="ti ti-x me-1"></i> Batal Pilih
                </button>
            </div>
        </div>
        <div class="card-datatable pt-0">
            <table class="table" id="table-monitoring-spj">
                <thead>
                    <tr>
                        <th class="text-center">
                            @if ($bisaIsi)
                                <input type="checkbox" class="form-check-input cek-semua-spj" title="Pilih semua baris yang tampil sesuai filter/pencarian">
                            @endif
                        </th>
                        <th data-kolom="posisi">Posisi SPJ</th>
                        <th>Tanggal Diperiksa PPK</th>
                        <th>Nomor SPP/SPM</th>
                        <th>Tanggal SPP</th>
                        <th>Tanggal SPM</th>
                        <th>Nomor SP2D</th>
                        <th>Tanggal SP2D</th>
                        <th>Nomor FP</th>
                        <th>Pembebanan</th>
                        <th>Tanggal</th>
                        <th>Uraian</th>
                        <th>Nominal (Rp)</th>
                        <th>Nama Penerima</th>
                        <th>Keterangan</th>
                        <th>Status Pembayaran</th>
                        <th>Tunai/Transfer</th>
                        <th>Status SPBy</th>
                        <th>Status Kuitansi</th>
                        <th>Status DRPP</th>
                        <th data-kolom="jenis-pembayaran">Jenis Pembayaran</th>
                        <th>Tanggal GUP</th>
                        {{-- Kunci kelompok GUP (tersembunyi, dipakai RowGroup); header kosong supaya tidak muncul di Atur Kolom. --}}
                        {{-- Kunci kelompok GUP (tersembunyi, dipakai RowGroup); diekspor sebagai label GUP. --}}
                        <th data-atur-kolom="false">Kelompok GUP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarBaris as $baris)
                        @php
                            $spj = $baris['spj'];
                            $tanggal = $baris['tanggal'];
                        @endphp
                        <tr data-grup="{{ $baris['kunci_gup'] }}" @class(['baris-rincian' => $baris['rincian']])>
                            <td class="text-center">
                                @if ($bisaIsi)
                                    <input type="checkbox" class="form-check-input cek-baris-spj" value="{{ $baris['kunci'] }}">
                                @endif
                            </td>
                            <td>
                                @if ($spj?->posisi_spj)
                                    <span class="badge rounded-pill bg-label-{{ $posisiBadge[$spj->posisi_spj] ?? 'secondary' }}">{{ $spj->posisi_spj }}</span>
                                @endif
                            </td>
                            <td data-order="{{ optional($spj?->tanggal_diperiksa_ppk)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_diperiksa_ppk) }}</td>
                            <td class="rekap-nomor">{{ $spj?->no_spp_spm }}</td>
                            <td data-order="{{ optional($spj?->tanggal_spp)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_spp) }}</td>
                            <td data-order="{{ optional($spj?->tanggal_spm)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_spm) }}</td>
                            <td class="rekap-nomor">{{ $spj?->no_sp2d }}</td>
                            <td data-order="{{ optional($spj?->tanggal_sp2d)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_sp2d) }}</td>
                            {{-- Perjadin: Nomor FP dari Data Umum (no_form_permintaan); rincian: diisi di modal. --}}
                            <td class="rekap-nomor">{{ $baris['nomor_fp'] }}</td>
                            <td class="text-nowrap">{{ $baris['pembebanan'] }}</td>
                            <td data-order="{{ optional($tanggal)->format('Y-m-d') }}">{{ $fmtTanggal($tanggal) }}</td>
                            <td class="kolom-uraian">
                                @if ($baris['rincian'])
                                    <span class="badge rounded-pill bg-label-info me-1" title="Diinput langsung oleh tim keuangan, di luar perjalanan dinas">Non-Perjadin</span>
                                @endif
                                {{ $baris['uraian'] }}
                            </td>
                            <td class="text-end text-nowrap" data-order="{{ $baris['nominal'] }}">{{ number_format($baris['nominal'], 0, ',', '.') }}</td>
                            <td>{{ $baris['penerima'] }}</td>
                            <td>{{ $spj?->keterangan }}</td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_dibayar, 'Sudah Dibayar', 'Belum Dibayar') !!}</td>
                            <td>
                                @if ($spj?->cara_bayar)
                                    <span class="badge rounded-pill bg-label-{{ $caraBayarBadge[$spj->cara_bayar] ?? 'secondary' }}">{{ $caraBayar[$spj->cara_bayar] ?? $spj->cara_bayar }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_spby, 'Sudah Diterbitkan', 'Belum Diterbitkan') !!}</td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_kuitansi, 'Sudah Dibuat', 'Belum Dibuat') !!}</td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_drpp, 'Sudah Dibuat', 'Belum Dibuat') !!}</td>
                            <td>{{ $spj?->jenis_pembayaran }}</td>
                            <td data-order="{{ optional($spj?->tanggal_gup)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_gup) }}</td>
                            <td class="kolom-gup">{{ $baris['kunci_gup'] }}</td>
                            <td class="text-nowrap">
                                @unless ($baris['rincian'])
                                    <a href="{{ route('perjalanan-dinas.show', $baris['perjalanan_dinas']) }}" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Lihat Perjalanan Dinas">
                                        <i class="ti ti-eye ti-sm"></i>
                                    </a>
                                @endunless
                                @if ($bisaIsi)
                                    @php
                                        $payload = [
                                            'rincian' => $baris['rincian'],
                                            'url' => $baris['rincian']
                                                ? route('monitoring-spj.rincian.update', $spj)
                                                : route('monitoring-spj.update', $baris['perjalanan_dinas']),
                                            'penerima' => $baris['penerima'],
                                            'uraian' => $baris['uraian'],
                                            'tanggal_diperiksa_ppk' => optional($spj?->tanggal_diperiksa_ppk)->format('Y-m-d'),
                                            'no_spp_spm' => $spj?->no_spp_spm,
                                            'tanggal_spp' => optional($spj?->tanggal_spp)->format('Y-m-d'),
                                            'tanggal_spm' => optional($spj?->tanggal_spm)->format('Y-m-d'),
                                            'no_sp2d' => $spj?->no_sp2d,
                                            'tanggal_sp2d' => optional($spj?->tanggal_sp2d)->format('Y-m-d'),
                                            'keterangan' => $spj?->keterangan,
                                            'posisi_spj' => $spj?->posisi_spj,
                                            'sudah_dibayar' => (bool) $spj?->sudah_dibayar,
                                            'cara_bayar' => $spj?->cara_bayar,
                                            'sudah_spby' => (bool) $spj?->sudah_spby,
                                            'sudah_kuitansi' => (bool) $spj?->sudah_kuitansi,
                                            'sudah_drpp' => (bool) $spj?->sudah_drpp,
                                            'jenis_pembayaran' => $spj?->jenis_pembayaran,
                                        ];
                                        if ($baris['rincian']) {
                                            $payload += [
                                                'tanggal_rincian' => optional($spj->tanggal_rincian)->format('Y-m-d'),
                                                'uraian_rincian' => $spj->uraian_rincian,
                                                'penerima_rincian' => $spj->penerima_rincian,
                                                'pembebanan_rincian' => $spj->pembebanan_rincian,
                                                'nomor_fp' => $spj->nomor_fp,
                                                'nominal_rincian' => $spj->nominal_rincian !== null ? (float) $spj->nominal_rincian : null,
                                            ];
                                        }
                                    @endphp
                                    <button type="button" class="btn btn-icon btn-sm btn-text-warning rounded-pill"
                                        title="{{ $baris['rincian'] ? 'Edit Rincian' : 'Isi Monitoring SPJ' }}"
                                        data-spj='@json($payload)' onclick="bukaModalSpj(this)">
                                        <i class="ti ti-edit ti-sm"></i>
                                    </button>
                                    @if ($baris['rincian'])
                                        <form action="{{ route('monitoring-spj.rincian.destroy', $spj) }}" method="POST" class="d-inline"
                                            onsubmit="return confirmSubmit(this, 'Hapus rincian ini? Tindakan ini tidak bisa dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus Rincian">
                                                <i class="ti ti-trash ti-sm"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal isi kolom manual Monitoring SPJ (PPK / PPSPM / Bendahara) --}}
    <div class="modal fade" id="modal-edit-spj" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="form-edit-spj" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0" id="spj-judul">Edit Monitoring SPJ</h5>
                            <div class="text-muted small" id="spj-info"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Data rincian: hanya untuk Rincian Non-Perjadin (untuk SPJ perjalanan dinas
                             datanya diambil dari perjalanan dinas, jadi bagian ini disembunyikan). --}}
                        <div id="spj-bagian-rincian" class="d-none">
                            <h6 class="judul-isian-spj">Data Rincian</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label" for="rincian-uraian">Uraian <span class="text-danger">*</span></label>
                                    <textarea id="rincian-uraian" name="uraian_rincian" class="form-control" rows="2" maxlength="2000" required></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="rincian-tanggal">Tanggal</label>
                                    <input type="date" id="rincian-tanggal" name="tanggal_rincian" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="rincian-nominal">Nominal (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" id="rincian-nominal" name="nominal_rincian" class="form-control" min="0" step="1" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="rincian-penerima">Nama Penerima</label>
                                    <input type="text" id="rincian-penerima" name="penerima_rincian" class="form-control" maxlength="255">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="rincian-pembebanan">Pembebanan</label>
                                    <input type="text" id="rincian-pembebanan" name="pembebanan_rincian" class="form-control" maxlength="255">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="rincian-fp">Nomor FP</label>
                                    <input type="text" id="rincian-fp" name="nomor_fp" class="form-control" maxlength="255">
                                </div>
                                <div class="col-12" id="spj-pilih-gup">
                                    <label class="form-label" for="rincian-gup">Masukkan ke GUP</label>
                                    <select id="rincian-gup" name="id_gup" class="form-select">
                                        <option value="">Belum Masuk GUP</option>
                                        @foreach ($kelompokGup as $gup)
                                            @if ($gup['id'])
                                                <option value="{{ $gup['id'] }}">{{ $gup['label'] }} &middot; {{ $gup['tanggal_teks'] ?? 'tanggal belum diisi' }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="form-text">Jika dimasukkan ke GUP, Jenis Pembayaran otomatis "GUP".</div>
                                </div>
                            </div>
                        </div>
                        @include('monitoring-spj._form-isian', ['bulk' => false, 'prefix' => 'spj'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal bulk edit: nilai yang diisi diterapkan ke semua baris terpilih --}}
    <div class="modal fade" id="modal-bulk-spj" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="form-bulk-spj" action="{{ route('monitoring-spj.bulk-update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div id="bulk-ids"></div>
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Edit <span id="bulk-jumlah">0</span> Baris Sekaligus</h5>
                            <div class="text-muted small">Hanya isian yang diisi yang diterapkan. Isian kosong, "Tetap", atau centang bertanda &ndash; tidak diubah.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('monitoring-spj._form-isian', ['bulk' => true, 'prefix' => 'bulk'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan ke Baris Terpilih</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if ($bisaIsi)
        {{-- Buat GUP baru dari SPJ terpilih --}}
        <div class="modal fade" id="modal-gup-baru" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="form-gup-baru" action="{{ route('gup.store') }}" method="POST">
                        @csrf
                        <div class="wadah-ids"></div>
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title mb-0">Buat GUP Baru</h5>
                                <div class="text-muted small"><span class="jumlah-gup-terpilih">0</span> SPJ terpilih akan dikelompokkan ke GUP baru.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label" for="input-gup-baru-tanggal">Tanggal GUP</label>
                            <input type="date" id="input-gup-baru-tanggal" name="tanggal_gup" class="form-control">
                            <div class="form-text">
                                Boleh dikosongkan dan diisi belakangan. Nomor "GUP ke-" dibuat otomatis menurut urutan tanggal dalam tahun yang sama.
                                SPJ yang sudah ada di GUP lain akan dipindahkan.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-square-plus me-1"></i> Buat GUP</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Masukkan SPJ terpilih ke GUP yang sudah ada --}}
        <div class="modal fade" id="modal-gup-tambah" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="form-gup-tambah" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="wadah-ids"></div>
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title mb-0">Masukkan ke GUP yang Ada</h5>
                                <div class="text-muted small"><span class="jumlah-gup-terpilih">0</span> SPJ terpilih akan ditambahkan ke GUP yang dipilih.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label" for="input-gup-tujuan">GUP Tujuan</label>
                            <select id="input-gup-tujuan" class="form-select" required>
                                <option value="" disabled selected>Pilih GUP</option>
                                @foreach ($pilihanGup as $gup)
                                    <option value="{{ $gup['id'] }}">
                                        {{ $gup['label'] }} &middot; {{ $gup['tanggal_teks'] ?? 'tanggal belum diisi' }} &middot; {{ $gup['jumlah'] }} SPJ
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">SPJ yang sudah ada di GUP lain akan dipindahkan ke GUP ini.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-stack-push me-1"></i> Masukkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Ubah tanggal GUP (dari tombol di baris kepala GUP) --}}
        <div class="modal fade" id="modal-gup-tanggal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="form-gup-tanggal" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title mb-0">Ubah Tanggal GUP</h5>
                                <div class="text-muted small" id="gup-tanggal-label"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label" for="input-gup-tanggal">Tanggal GUP</label>
                            <input type="date" id="input-gup-tanggal" name="tanggal_gup" class="form-control">
                            <div class="form-text">Tanggal GUP semua SPJ di dalam GUP ini ikut berubah.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Import Excel Rincian Non-Perjadin --}}
        <div class="modal fade" id="modal-import-rincian" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('monitoring-spj.rincian.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Import Rincian Non-Perjadin</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                Belum punya filenya? <a href="{{ route('monitoring-spj.rincian.import-template') }}">Unduh template Excel</a> terlebih dahulu,
                                isi datanya (1 baris = 1 rincian), baru unggah di sini.
                            </p>
                            <ul class="text-muted small ps-3 mb-3">
                                <li>Baris contoh (kuning, Uraian diawali <b>[CONTOH]</b>) tidak ikut diimport — boleh dibiarkan.</li>
                                <li>Isi kolom <b>Tanggal GUP</b> untuk langsung memasukkan rincian ke GUP bertanggal itu (GUP dibuat otomatis bila belum ada).</li>
                                <li>Untuk SPJ perjalanan dinas, gunakan Import Excel di halaman Perjalanan Dinas.</li>
                            </ul>
                            <label class="form-label" for="input-file-rincian">File Excel (.xlsx)</label>
                            <input type="file" id="input-file-rincian" name="file" class="form-control" accept=".xlsx,.xls" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-upload ti-sm me-1"></i> Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <form id="form-gup-keluarkan" action="{{ route('gup.keluarkan') }}" method="POST" class="d-none">
            @csrf
            @method('PATCH')
            <div class="wadah-ids"></div>
        </form>
        <form id="form-gup-hapus" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

@push('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-buttons/datatables-buttons.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jszip/jszip.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons/buttons.html5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-fixedcolumns/datatables.fixedcolumns.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-rowgroup/datatables.rowgroup.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.js') }}"></script>

    <script>
        const formEditSpj = document.getElementById('form-edit-spj');

        const URL_RINCIAN_BARU = '{{ route('monitoring-spj.rincian.store') }}';

        // Satu modal untuk tiga keperluan: isi monitoring SPJ perjalanan dinas, edit Rincian
        // Non-Perjadin, dan tambah Rincian Non-Perjadin baru (data.baru).
        function bukaModalSpj(tombol, dataLangsung) {
            const data = dataLangsung || JSON.parse(tombol.dataset.spj);
            formEditSpj.action = data.url;
            formEditSpj.querySelector('input[name=_method]').value = data.baru ? 'POST' : 'PUT';
            document.getElementById('spj-judul').textContent = data.baru
                ? 'Tambah Rincian Non-Perjadin'
                : (data.rincian ? 'Edit Rincian Non-Perjadin' : 'Edit Monitoring SPJ');
            document.getElementById('spj-info').textContent = data.baru
                ? 'SPJ di luar perjalanan dinas, diinput langsung oleh tim keuangan.'
                : data.penerima + ' — ' + (data.uraian || '');

            // Bagian Data Rincian hanya aktif untuk rincian; input yang disembunyikan
            // dinonaktifkan supaya tidak ikut terkirim/divalidasi browser.
            const bagianRincian = document.getElementById('spj-bagian-rincian');
            bagianRincian.classList.toggle('d-none', !data.rincian);
            bagianRincian.querySelectorAll('[name]').forEach(function (el) { el.disabled = !data.rincian; });
            const pilihGup = document.getElementById('spj-pilih-gup');
            pilihGup.classList.toggle('d-none', !data.baru);
            pilihGup.querySelector('select').disabled = !data.baru;

            formEditSpj.querySelectorAll('input[name], select[name], textarea[name]').forEach(function (el) {
                if (['_token', '_method'].includes(el.name)) return;
                if (el.type === 'checkbox') {
                    el.checked = !!data[el.name];
                } else {
                    el.value = data[el.name] ?? '';
                }
            });
            kelengkapanEdit.sinkron();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-edit-spj')).show();
        }

        // Tambah rincian baru; idGup terisi kalau dibuka dari tombol "+" di kepala GUP.
        function bukaModalRincianBaru(idGup) {
            bukaModalSpj(null, { baru: true, rincian: true, url: URL_RINCIAN_BARU, id_gup: idGup || '' });
        }

        // Kelengkapan (Pembayaran/SPBy/Kuitansi/DRPP) + "Centang Semua", sama untuk modal Edit
        // dan Edit Terpilih. Tunai/Transfer baru bisa diisi kalau Pembayaran dicentang; kalau
        // centangnya dilepas, isiannya dikosongkan (server juga mengosongkannya).
        // Di Edit Terpilih centangnya 3 keadaan: – (indeterminate) = tidak diubah; begitu
        // diklik, nilainya (1/0) dikirim lewat input tersembunyi di sebelahnya.
        // SPJ yang Posisi-nya "Dikembalikan" tidak bisa dibayar: centang Pembayaran
        // dilepas & dikunci (server menerapkan aturan yang sama).
        const POSISI_DIKEMBALIKAN = @json(\App\Models\MonitoringSpj::POSISI_DIKEMBALIKAN);

        function pasangKelengkapan(form, bulk) {
            const cekStatus = Array.from(form.querySelectorAll('.cek-status-spj'));
            const cekSemua = form.querySelector('.cek-centang-semua');
            const cekPembayaran = form.querySelector('.cek-status-spj[data-field="sudah_dibayar"]');
            const selectCaraBayar = form.querySelector('select[name="cara_bayar"]');
            const selectPosisi = form.querySelector('select[name="posisi_spj"]');
            let pembayaranDikunci = false;

            function tandaiDiubah(c) {
                c.indeterminate = false;
                if (bulk) {
                    const tersembunyi = c.nextElementSibling;
                    tersembunyi.value = c.checked ? '1' : '0';
                    tersembunyi.disabled = false;
                }
            }

            function sinkron() {
                const dikembalikan = selectPosisi.value === POSISI_DIKEMBALIKAN;
                if (dikembalikan) {
                    cekPembayaran.checked = false;
                    tandaiDiubah(cekPembayaran);
                    cekPembayaran.disabled = true;
                    pembayaranDikunci = true;
                } else if (pembayaranDikunci) {
                    // Posisi diganti dari "Dikembalikan": buka lagi; di Edit Terpilih kembali "tidak diubah".
                    cekPembayaran.disabled = false;
                    pembayaranDikunci = false;
                    if (bulk) {
                        cekPembayaran.indeterminate = true;
                        cekPembayaran.nextElementSibling.value = '';
                        cekPembayaran.nextElementSibling.disabled = true;
                    }
                }
                cekPembayaran.title = dikembalikan ? 'SPJ yang dikembalikan ke pembuat belum bisa dibayar' : '';

                const aktif = cekStatus.filter(function (c) { return !c.disabled; });
                cekSemua.checked = aktif.every(function (c) { return c.checked && !c.indeterminate; });
                const sudahBayar = cekPembayaran.checked && !cekPembayaran.indeterminate;
                selectCaraBayar.disabled = !sudahBayar;
                if (!sudahBayar) selectCaraBayar.value = '';
            }

            cekStatus.forEach(function (c) {
                c.addEventListener('change', function () { tandaiDiubah(c); sinkron(); });
            });
            cekSemua.addEventListener('change', function () {
                cekStatus.forEach(function (c) {
                    if (c.disabled) return;
                    c.checked = cekSemua.checked;
                    tandaiDiubah(c);
                });
                sinkron();
            });
            selectPosisi.addEventListener('change', sinkron);

            function reset() {
                cekPembayaran.disabled = false;
                pembayaranDikunci = false;
                cekStatus.forEach(function (c) {
                    c.checked = false;
                    c.indeterminate = bulk;
                    if (bulk) {
                        c.nextElementSibling.value = '';
                        c.nextElementSibling.disabled = true;
                    }
                });
                sinkron();
            }

            return { sinkron: sinkron, reset: reset };
        }

        const kelengkapanEdit = pasangKelengkapan(formEditSpj, false);
        const formBulkSpj = document.getElementById('form-bulk-spj');
        const kelengkapanBulk = pasangKelengkapan(formBulkSpj, true);

        // Ringkasan tiap kelompok GUP (kunci → label, tanggal, jumlah SPJ, total nominal).
        const INFO_GUP = @json($kelompokGup);
        const BISA_ISI = @json($bisaIsi);
        const URL_GUP_TAMBAH = '{{ route('gup.tambah', ['gup' => '__ID__']) }}';
        const URL_GUP_UBAH = '{{ route('gup.update', ['gup' => '__ID__']) }}';
        const fmtRupiah = (n) => 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
        const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function kepalaGup(kunci, jumlahKolom) {
            const info = INFO_GUP[kunci] || { label: kunci, jumlah: 0, total: 0 };
            const tanpaGup = !info.id;
            const sub = tanpaGup
                ? 'SPJ yang belum dikelompokkan ke GUP mana pun'
                : (info.tanggal_teks ? 'Tanggal GUP: ' + esc(info.tanggal_teks) : '<span class="text-warning">Tanggal GUP belum diisi</span>');
            const cek = BISA_ISI
                ? '<input type="checkbox" class="form-check-input cek-grup-spj" data-grup="' + esc(kunci) + '" title="Pilih semua SPJ di kelompok ini">'
                : '';
            const tombolTambah = '<button type="button" class="btn btn-sm btn-text-primary btn-tambah-rincian-gup" data-id="' + (info.id || '') + '" title="Tambah rincian non-perjadin ke kelompok ini"><i class="ti ti-plus"></i></button>';
            const aksi = !BISA_ISI ? '' : tanpaGup
                ? '<div class="aksi-gup d-flex gap-1">' + tombolTambah + '</div>'
                : '<div class="aksi-gup d-flex gap-1">'
                    + tombolTambah
                    + '<button type="button" class="btn btn-sm btn-text-primary btn-ubah-gup" data-id="' + info.id + '" data-tanggal="' + esc(info.tanggal || '') + '" data-label="' + esc(info.label) + '" title="Ubah tanggal GUP"><i class="ti ti-calendar-event"></i></button>'
                    + '<button type="button" class="btn btn-sm btn-text-danger btn-hapus-gup" data-id="' + info.id + '" data-label="' + esc(info.label) + '" title="Hapus GUP"><i class="ti ti-trash"></i></button>'
                    + '</div>';
            const isi = '<div class="gup-kepala-isi">'
                + '<div class="kiri">' + cek
                + '<span class="ikon"><i class="ti ' + (tanpaGup ? 'ti-inbox' : 'ti-stack-2') + '"></i></span>'
                + '<div><div class="judul">' + esc(info.label) + '</div><div class="sub">' + sub + '</div></div>'
                + aksi + '</div>'
                + '<div class="kanan"><span class="jumlah">' + info.jumlah + ' SPJ</span>'
                + '<span class="total">' + fmtRupiah(info.total) + '</span></div>'
                + '</div>';

            return $('<tr class="baris-gup' + (tanpaGup ? ' tanpa-gup' : '') + '"/>')
                .append('<td colspan="' + jumlahKolom + '">' + isi + '</td>');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const KOLOM_GUP = $('#table-monitoring-spj thead th').length - 2;
            const tabelSpj = $('#table-monitoring-spj').DataTable({
                scrollX: true,
                autoWidth: false,
                fixedColumns: {
                    rightColumns: 1,
                },
                // Tanpa urutan kolom awal: ikut urutan server (terakhir dibuat paling atas).
                order: [],
                // Baris dikelompokkan per GUP; kunci kelompoknya urut teks ("0000" = Belum Masuk
                // GUP, lalu GUP terbaru), dan selalu jadi urutan pertama supaya kelompok tetap utuh.
                orderFixed: { pre: [KOLOM_GUP, 'asc'] },
                rowGroup: {
                    dataSrc: KOLOM_GUP,
                    startRender: function (rows, kunci) {
                        return kepalaGup(kunci, this.s.dt.columns(':visible').count());
                    },
                },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, -1] },
                    { visible: false, searchable: false, targets: KOLOM_GUP },
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Cari data...',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                    emptyTable: 'Belum ada SPJ perjalanan dinas berstatus selesai maupun rincian non-perjadin.',
                },
                dom: '<"row mx-1 mt-3 align-items-center"' +
                    '<"col-md-6"<"me-3"l>>' +
                    '<"col-md-6 d-flex justify-content-md-end"f>' +
                    '>' +
                    't' +
                    '<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 25,
                lengthMenu: [10, 25, 50, 100],
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="ti ti-file-spreadsheet me-1"></i> Export Excel',
                        className: 'btn btn-label-primary',
                        title: 'Monitoring SPJ',
                        exportOptions: {
                            columns: ':not(:first-child):not(:last-child):not(.no-export)',
                            format: {
                                // Kolom tersembunyi kunci GUP diekspor sebagai label kelompoknya.
                                body: function (data, baris, kolom, node) {
                                    if (node && node.classList.contains('kolom-gup')) return (INFO_GUP[data] || {}).label || data;
                                    return SimpelExportExcel.isiSel(data, baris, kolom, node);
                                },
                            },
                        },
                        customize: SimpelExportExcel.rapikan,
                    },
                ],
                initComplete: function () {
                    window.SimpelAturKolom.pasang(this.api());
                    this.api().buttons().container().appendTo('#monitoring-spj-export');
                },
            });

            // Kolom dicari lewat atribut header, jadi tidak bergeser kalau urutan kolom berubah.
            document.getElementById('filter-posisi-spj').addEventListener('change', function () {
                tabelSpj.column('[data-kolom="posisi"]').search(this.value, false, false).draw();
            });
            document.getElementById('filter-jenis-pembayaran').addEventListener('change', function () {
                tabelSpj.column('[data-kolom="jenis-pembayaran"]').search(this.value, false, false).draw();
            });

            // ----- Bulk edit. Semua baris tetap ada di DOM DataTables (client-side), jadi
            // centang di halaman lain ikut terhitung. "Pilih semua" = semua baris yang lolos
            // filter/pencarian saat ini, lintas halaman. Pakai delegasi di document karena
            // scrollX bikin DataTables mengkloning header (checkbox-nya bukan elemen asli). -----
            const barBulkSpj = document.getElementById('bulk-bar-spj');
            // Taruh bar tepat di atas header tabel (sebelum area scroll DataTables).
            const scrollSpj = document.querySelector('#table-monitoring-spj_wrapper .dataTables_scroll');
            if (scrollSpj) scrollSpj.parentNode.insertBefore(barBulkSpj, scrollSpj);

            function cekbokTerpilih() {
                return $(tabelSpj.rows().nodes()).find('.cek-baris-spj:checked').toArray();
            }

            function cekbokTersaring() {
                return $(tabelSpj.rows({ search: 'applied' }).nodes()).find('.cek-baris-spj').toArray();
            }

            function cekbokKelompok(kunci) {
                return cekbokTersaring().filter(function (c) { return c.closest('tr').dataset.grup === kunci; });
            }

            function perbaruiStatusPilihan() {
                const jumlah = cekbokTerpilih().length;
                document.getElementById('jumlah-terpilih').textContent = jumlah;
                barBulkSpj.classList.toggle('d-none', jumlah === 0);

                const tersaring = cekbokTersaring();
                const semuaTercentang = tersaring.length > 0 && tersaring.every(function (c) { return c.checked; });
                $(".cek-semua-spj").prop('checked', semuaTercentang);

                document.querySelectorAll('.cek-grup-spj').forEach(function (cg) {
                    const anggota = cekbokKelompok(cg.dataset.grup);
                    const tercentang = anggota.filter(function (c) { return c.checked; }).length;
                    cg.checked = anggota.length > 0 && tercentang === anggota.length;
                    cg.indeterminate = tercentang > 0 && tercentang < anggota.length;
                });
            }

            // Isi kepala GUP selebar area tabel yang terlihat (tabel bisa di-scroll ke samping).
            const scrollBodySpj = document.querySelector('#table-monitoring-spj_wrapper .dataTables_scrollBody');
            function aturLebarKepalaGup() {
                const lebar = scrollBodySpj ? scrollBodySpj.clientWidth : 0;
                document.querySelectorAll('#table-monitoring-spj .gup-kepala-isi').forEach(function (el) {
                    if (lebar) el.style.width = lebar + 'px';
                });
            }
            window.addEventListener('resize', aturLebarKepalaGup);

            document.getElementById('btn-batal-pilih-spj').addEventListener('click', function () {
                cekbokTerpilih().forEach(function (c) { c.checked = false; });
                perbaruiStatusPilihan();
            });

            $(document).on("change", ".cek-semua-spj", function () {
                const centang = this.checked;
                cekbokTersaring().forEach(function (c) { c.checked = centang; });
                perbaruiStatusPilihan();
            });
            $(document).on('change', '.cek-baris-spj', perbaruiStatusPilihan);
            $(document).on('change', '.cek-grup-spj', function () {
                const centang = this.checked;
                cekbokKelompok(this.dataset.grup).forEach(function (c) { c.checked = centang; });
                perbaruiStatusPilihan();
            });
            // Kepala GUP digambar ulang oleh RowGroup setiap draw.
            tabelSpj.on('draw', function () {
                setTimeout(function () { perbaruiStatusPilihan(); aturLebarKepalaGup(); });
            });
            aturLebarKepalaGup();
            perbaruiStatusPilihan();

            // Nilai centang: "12" = perjalanan dinas (ids[]), "r5" = Rincian Non-Perjadin (rincian_ids[]).
            function inputIdTerpilih(terpilih) {
                return terpilih.map(function (c) {
                    const rincian = c.value.charAt(0) === 'r';
                    return '<input type="hidden" name="' + (rincian ? 'rincian_ids[]' : 'ids[]') + '" value="'
                        + parseInt(rincian ? c.value.slice(1) : c.value, 10) + '">';
                }).join('');
            }

            $(document).on('click', '.btn-tambah-rincian-gup', function () {
                bukaModalRincianBaru(this.dataset.id);
            });

            // ----- Pengelompokan GUP -----
            function isiIdTerpilih(form) {
                const terpilih = cekbokTerpilih();
                form.querySelector('.wadah-ids').innerHTML = inputIdTerpilih(terpilih);
                form.closest('.modal')?.querySelectorAll('.jumlah-gup-terpilih').forEach(function (el) {
                    el.textContent = terpilih.length;
                });
                return terpilih.length;
            }

            if (BISA_ISI) {
                const formGupBaru = document.getElementById('form-gup-baru');
                const formGupTambah = document.getElementById('form-gup-tambah');
                const formKeluarkan = document.getElementById('form-gup-keluarkan');
                const formHapus = document.getElementById('form-gup-hapus');
                const formTanggal = document.getElementById('form-gup-tanggal');

                document.getElementById('modal-gup-baru').addEventListener('show.bs.modal', function () {
                    isiIdTerpilih(formGupBaru);
                    formGupBaru.querySelector('[name=tanggal_gup]').value = '';
                });
                document.getElementById('modal-gup-tambah').addEventListener('show.bs.modal', function () {
                    isiIdTerpilih(formGupTambah);
                    document.getElementById('input-gup-tujuan').selectedIndex = 0;
                });
                formGupTambah.addEventListener('submit', function () {
                    formGupTambah.action = URL_GUP_TAMBAH.replace('__ID__', document.getElementById('input-gup-tujuan').value);
                });

                document.getElementById('btn-keluarkan-gup').addEventListener('click', function () {
                    const jumlah = isiIdTerpilih(formKeluarkan);
                    swalConfirm('Keluarkan ' + jumlah + ' SPJ terpilih dari GUP-nya?', { confirmButtonText: 'Ya, keluarkan' })
                        .then(function (ok) { if (ok) formKeluarkan.submit(); });
                });

                $(document).on('click', '.btn-ubah-gup', function () {
                    formTanggal.action = URL_GUP_UBAH.replace('__ID__', this.dataset.id);
                    document.getElementById('gup-tanggal-label').textContent = this.dataset.label;
                    document.getElementById('input-gup-tanggal').value = this.dataset.tanggal || '';
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-gup-tanggal')).show();
                });
                $(document).on('click', '.btn-hapus-gup', function () {
                    const label = this.dataset.label;
                    formHapus.action = URL_GUP_UBAH.replace('__ID__', this.dataset.id);
                    swalConfirm('Hapus ' + label + '? SPJ di dalamnya kembali ke "Belum Masuk GUP".', { confirmButtonText: 'Ya, hapus' })
                        .then(function (ok) { if (ok) formHapus.submit(); });
                });
            }

            document.getElementById('modal-bulk-spj').addEventListener('show.bs.modal', function () {
                const terpilih = cekbokTerpilih();
                document.getElementById('bulk-jumlah').textContent = terpilih.length;
                document.getElementById('bulk-ids').innerHTML = inputIdTerpilih(terpilih);
                formBulkSpj.querySelectorAll('input:not([type=hidden]):not([type=checkbox]), select').forEach(function (el) {
                    el.value = '';
                });
                kelengkapanBulk.reset();
            });
        });
    </script>
@endpush
