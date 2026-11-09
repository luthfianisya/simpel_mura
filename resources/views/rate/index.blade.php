@extends('layouts.app')

@section('title', 'Rate Transport dan Akomodasi')

@php
    $fmtRupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $isAdmin = auth()->user()->role === 'administrator';

    // Ikon per moda transportasi (moda gabungan, mis. "Angkutan Desa dan Perahu Kecil", dapat beberapa ikon).
    $ikonModa = function (string $moda): array {
        $m = strtolower($moda);
        $ikon = [];
        if (str_contains($m, 'ojek')) $ikon[] = 'ti-motorbike';
        if (str_contains($m, 'angkutan') || str_contains($m, 'mobil')) $ikon[] = 'ti-bus';
        if (str_contains($m, 'speed')) $ikon[] = 'ti-ship';
        if (str_contains($m, 'perahu')) $ikon[] = 'ti-sailboat';
        return $ikon ?: ['ti-route'];
    };

    $fmtUkuran = function (?int $byte): ?string {
        if ($byte === null) return null;
        return $byte >= 1048576
            ? number_format($byte / 1048576, 1, ',', '.') . ' MB'
            : number_format(max($byte / 1024, 1), 0, ',', '.') . ' KB';
    };

    $jumlahB = $daftarTransportB->flatten()->count();
    $jumlahC = $daftarTransportC->flatten()->count();
    $levelTransport = [
        'A' => ['tab' => 'tab-level-a', 'dari' => 'Kabupaten', 'ke' => 'Kecamatan', 'jumlah' => $daftarTransportA->count()],
        'B' => ['tab' => 'tab-level-b', 'dari' => 'Kecamatan', 'ke' => 'Desa/Kel.', 'jumlah' => $jumlahB],
        'C' => ['tab' => 'tab-level-c', 'dari' => 'Kabupaten', 'ke' => 'Desa/Kel.', 'jumlah' => $jumlahC],
    ];
@endphp

@push('page-css')
    <style>
        .judul-bagian-rate { display: flex; align-items: center; gap: 0.75rem; }
        .judul-bagian-rate .ikon-bagian {
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

        /* Kartu dokumen pendukung */
        .kartu-dokumen {
            --w: 37, 99, 235;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            height: 100%;
            padding: 1rem 1.1rem;
            border-radius: 0.625rem;
            border: 1px solid #e6eaf3;
            background: #fff;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .kartu-dokumen.jenis-pdf { --w: 234, 84, 85; }
        .kartu-dokumen.jenis-xlsx, .kartu-dokumen.jenis-xls { --w: 40, 199, 111; }
        .kartu-dokumen:hover {
            color: inherit;
            transform: translateY(-3px);
            border-color: rgba(var(--w), 0.35);
            box-shadow: 0 12px 24px -14px rgba(var(--w), 0.6);
        }
        .kartu-dokumen .ikon-file {
            position: relative;
            width: 46px;
            height: 52px;
            flex-shrink: 0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: rgb(var(--w));
            background: rgba(var(--w), 0.1);
        }
        .kartu-dokumen .ikon-file .ext {
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            padding: 0 0.3rem;
            border-radius: 0.2rem;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #fff;
            background: rgb(var(--w));
        }
        .kartu-dokumen .nama { font-weight: 600; color: #2f3349; line-height: 1.3; }
        .kartu-dokumen .ket {
            font-size: 0.8125rem;
            color: #6b7896;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .kartu-dokumen .unduh {
            margin-left: auto;
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
            transition: background 0.2s ease, color 0.2s ease;
        }
        .kartu-dokumen:hover .unduh { color: #fff; background: #2563eb; }

        /* Tab level transport */
        .tab-level { display: flex; flex-wrap: wrap; gap: 0.75rem; border: 0; }
        .tab-level .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 220px;
            padding: 0.7rem 0.9rem;
            border: 1px solid #e3e7f1;
            border-radius: 0.5rem;
            background: #fff;
            color: #4b5a7c;
            text-align: left;
            transition: all 0.15s ease;
        }
        .tab-level .nav-link:hover { border-color: rgba(37, 99, 235, 0.4); color: #2563eb; }
        .tab-level .nav-link .huruf {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
        }
        .tab-level .nav-link .rute { font-weight: 600; font-size: 0.875rem; display: flex; align-items: center; gap: 0.25rem; }
        .tab-level .nav-link small { color: #8a94ad; }
        .tab-level .nav-link.active {
            border-color: #2563eb;
            background: rgba(37, 99, 235, 0.06);
            color: #2563eb;
            box-shadow: 0 6px 14px -8px rgba(37, 99, 235, 0.6);
        }
        .tab-level .nav-link.active .huruf { color: #fff; background: #2563eb; }

        .badge-moda {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.6rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.08);
            white-space: nowrap;
        }
        .badge-moda i { font-size: 1rem; }
        .nominal { font-weight: 600; color: #2f3349; font-variant-numeric: tabular-nums; white-space: nowrap; }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Rate Transportasi dan Akomodasi</h4>
            <p class="text-muted mb-0">Besaran biaya rate akomodasi &amp; transport dalam kota, serta dokumen referensi (SK, nota, peraturan) sesuai ketentuan yang berlaku.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
            <i class="ti ti-arrow-left ti-sm"></i> Kembali
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Periksa kembali data yang diisi:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Dokumen Pendukung --}}
    <div class="card mb-4">
        <div class="card-header">
            <div class="judul-bagian-rate">
                <span class="ikon-bagian"><i class="ti ti-folder"></i></span>
                <div>
                    <h5 class="mb-0">Dokumen Pendukung</h5>
                    <p class="text-muted small mb-0">SK, nota, dan peraturan terkait pertanggungjawaban perjalanan dinas.</p>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row g-3">
                @foreach ($daftarDokumenPendukung as $key => $dokumen)
                    <div class="col-md-6">
                        <a href="{{ route('dokumen-pendukung.download', $key) }}" class="kartu-dokumen jenis-{{ $dokumen['ekstensi'] }}" title="Unduh {{ $dokumen['file'] }}">
                            <span class="ikon-file">
                                <i class="ti {{ in_array($dokumen['ekstensi'], ['xlsx', 'xls']) ? 'ti-file-spreadsheet' : 'ti-file-text' }}"></i>
                                <span class="ext">{{ $dokumen['ekstensi'] }}</span>
                            </span>
                            <span class="d-block flex-grow-1" style="min-width: 0;">
                                <span class="nama d-block">{{ $dokumen['label'] }}</span>
                                <span class="ket">{{ $dokumen['keterangan'] }}</span>
                                @if ($fmtUkuran($dokumen['ukuran']))
                                    <small class="text-muted d-block mt-1">{{ $fmtUkuran($dokumen['ukuran']) }}</small>
                                @endif
                            </span>
                            <span class="unduh"><i class="ti ti-download"></i></span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Rate Akomodasi --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="judul-bagian-rate">
                <span class="ikon-bagian"><i class="ti ti-bed"></i></span>
                <div>
                    <h5 class="mb-0">Rate Akomodasi Dalam Kota</h5>
                    <p class="text-muted small mb-0">Tarif akomodasi per malam untuk {{ $daftarAkomodasi->count() }} kecamatan.</p>
                </div>
            </div>
            @if ($isAdmin)
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-akomodasi" onclick="bukaModalTambahAkomodasi()">
                    <i class="ti ti-plus ti-sm me-1"></i> Tambah
                </button>
            @endif
        </div>
        {{-- Padding samping & bawah 1.5rem, sama dengan .tab-content di kartu Rate Transport. --}}
        <div class="table-responsive px-4 pb-4">
            <table class="table" data-datatable data-kosong="Belum ada data.">
                <thead>
                    <tr>
                        <th>Kecamatan</th>
                        <th class="text-end">Tarif / Malam</th>
                        <th>Keterangan</th>
                        @if ($isAdmin)
                            <th data-orderable="false">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarAkomodasi as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->nama_kecamatan }}</td>
                            <td class="text-end nominal" data-order="{{ (float) $item->tarif_per_malam }}">{{ $fmtRupiah($item->tarif_per_malam) }}</td>
                            <td class="text-muted">{{ $item->keterangan ?? '-' }}</td>
                            @if ($isAdmin)
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                        data-bs-toggle="modal" data-bs-target="#modal-akomodasi"
                                        onclick='bukaModalEditAkomodasi({{ json_encode($item) }})'>
                                        <i class="ti ti-edit ti-sm"></i>
                                    </button>
                                    <form action="{{ route('rate.akomodasi.destroy', $item->id_rate_akomodasi) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, 'Hapus rate akomodasi {{ $item->nama_kecamatan }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus">
                                            <i class="ti ti-trash ti-sm"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rate Transport --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="judul-bagian-rate">
                <span class="ikon-bagian"><i class="ti ti-route"></i></span>
                <div>
                    <h5 class="mb-0">Rate Transport Dalam Kota</h5>
                    <p class="text-muted small mb-0">Nilai transport pulang-pergi (PP) per rute dan moda transportasi.</p>
                </div>
            </div>
            @if ($isAdmin)
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-transport" onclick="bukaModalTambahTransport()">
                    <i class="ti ti-plus ti-sm me-1"></i> Tambah
                </button>
            @endif
        </div>
        <div class="card-body pt-0 pb-2">
            <ul class="nav tab-level" role="tablist">
                @foreach ($levelTransport as $huruf => $level)
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $level['tab'] }}">
                            <span class="huruf">{{ $huruf }}</span>
                            <span>
                                <span class="rute">{{ $level['dari'] }} <i class="ti ti-arrow-narrow-right"></i> {{ $level['ke'] }}</span>
                                <small>{{ $level['jumlah'] }} rute{{ $huruf === 'C' ? ' · langsung' : '' }}</small>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="tab-content">
            {{-- Level A --}}
            <div class="tab-pane fade show active" id="tab-level-a">
                <div class="table-responsive">
                    <table class="table" data-datatable data-kosong="Belum ada data.">
                        <thead>
                            <tr>
                                <th>Kecamatan</th>
                                <th>Moda Transportasi</th>
                                <th class="text-end">Nilai PP</th>
                                @if ($isAdmin)
                                    <th data-orderable="false">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarTransportA as $item)
                                <tr>
                                    <td class="fw-medium">{{ $item->nama_wilayah }}</td>
                                    <td><span class="badge-moda">@foreach ($ikonModa($item->moda_transportasi) as $ikon)<i class="ti {{ $ikon }}"></i>@endforeach{{ $item->moda_transportasi }}</span></td>
                                    <td class="text-end nominal" data-order="{{ (float) $item->nilai_pp }}">{{ $fmtRupiah($item->nilai_pp) }}</td>
                                    @if ($isAdmin)
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                                data-bs-toggle="modal" data-bs-target="#modal-transport"
                                                onclick='bukaModalEditTransport({{ json_encode($item) }})'>
                                                <i class="ti ti-edit ti-sm"></i>
                                            </button>
                                            <form action="{{ route('rate.transport.destroy', $item->id_rate_transport) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, 'Hapus rate transport {{ $item->nama_wilayah }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus">
                                                    <i class="ti ti-trash ti-sm"></i>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Level B & C: dikelompokkan per kecamatan --}}
            @foreach (['tab-level-b' => $daftarTransportB, 'tab-level-c' => $daftarTransportC] as $tabId => $daftar)
                <div class="tab-pane fade" id="{{ $tabId }}">
                    <div class="table-responsive">
                        <table class="table" data-datatable data-kosong="Belum ada data.">
                            <thead>
                                <tr>
                                    <th>Kecamatan</th>
                                    <th>Desa/Kelurahan</th>
                                    <th>Moda Transportasi</th>
                                    <th class="text-end">Nilai PP</th>
                                    @if ($isAdmin)
                                        <th data-orderable="false">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daftar as $kecamatan => $itemsPerKecamatan)
                                    @foreach ($itemsPerKecamatan as $item)
                                        <tr>
                                            <td class="text-muted">{{ $kecamatan }}</td>
                                            <td class="fw-medium">{{ $item->nama_wilayah }}</td>
                                            <td><span class="badge-moda">@foreach ($ikonModa($item->moda_transportasi) as $ikon)<i class="ti {{ $ikon }}"></i>@endforeach{{ $item->moda_transportasi }}</span></td>
                                            <td class="text-end nominal" data-order="{{ (float) $item->nilai_pp }}">{{ $fmtRupiah($item->nilai_pp) }}</td>
                                            @if ($isAdmin)
                                                <td class="text-nowrap">
                                                    <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                                        data-bs-toggle="modal" data-bs-target="#modal-transport"
                                                        onclick='bukaModalEditTransport({{ json_encode($item) }})'>
                                                        <i class="ti ti-edit ti-sm"></i>
                                                    </button>
                                                    <form action="{{ route('rate.transport.destroy', $item->id_rate_transport) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, 'Hapus rate transport {{ $item->nama_wilayah }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus">
                                                            <i class="ti ti-trash ti-sm"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($isAdmin)
    {{-- Modal Rate Akomodasi --}}
    <div class="modal fade" id="modal-akomodasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-akomodasi" method="POST">
                    @csrf
                    <div id="form-akomodasi-method"></div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-akomodasi-title">Tambah Rate Akomodasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kecamatan</label>
                            <input type="text" id="input-akomodasi-kecamatan" name="nama_kecamatan" class="form-control" list="daftar-kecamatan-rate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tarif per Malam (Rp)</label>
                            <input type="number" id="input-akomodasi-tarif" name="tarif_per_malam" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Keterangan</label>
                            <input type="text" id="input-akomodasi-keterangan" name="keterangan" class="form-control" placeholder="mis. mengikuti kecamatan induk">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Rate Transport --}}
    <div class="modal fade" id="modal-transport" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-transport" method="POST">
                    @csrf
                    <div id="form-transport-method"></div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-transport-title">Tambah Rate Transport</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Level</label>
                            <select id="input-transport-level" name="level" class="form-select" required>
                                <option value="A">A &mdash; Kabupaten ke Kecamatan</option>
                                <option value="B">B &mdash; Kecamatan ke Desa/Kelurahan</option>
                                <option value="C">C &mdash; Kabupaten ke Desa/Kelurahan langsung</option>
                            </select>
                        </div>
                        <div class="mb-3" id="wrapper-transport-kecamatan">
                            <label class="form-label">Kecamatan Induk</label>
                            <input type="text" id="input-transport-kecamatan" name="kecamatan_induk" class="form-control" list="daftar-kecamatan-rate" placeholder="Kosongkan untuk Level A">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="label-transport-wilayah">Kecamatan</label>
                            <input type="text" id="input-transport-wilayah" name="nama_wilayah" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Moda Transportasi</label>
                            <input type="text" id="input-transport-moda" name="moda_transportasi" class="form-control" placeholder="mis. Ojek, Perahu Kecil, Speed Boat" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nilai PP (Rp)</label>
                            <input type="number" id="input-transport-nilai" name="nilai_pp" class="form-control" min="0" step="1000" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <datalist id="daftar-kecamatan-rate">
        @foreach ($daftarKecamatan as $kecamatan)
            <option value="{{ $kecamatan }}">
        @endforeach
    </datalist>
    @endif
@endsection

@if ($isAdmin)
@push('page-js')
    <script>
        // ----- Rate Akomodasi -----
        const formAkomodasi = document.getElementById('form-akomodasi');
        const formAkomodasiMethod = document.getElementById('form-akomodasi-method');
        const modalAkomodasiTitle = document.getElementById('modal-akomodasi-title');
        const storeAkomodasiUrl = '{{ route('rate.akomodasi.store') }}';
        const updateAkomodasiUrlTemplate = '{{ route('rate.akomodasi.update', ['akomodasi' => '__ID__']) }}';

        function bukaModalTambahAkomodasi() {
            formAkomodasi.reset();
            formAkomodasi.action = storeAkomodasiUrl;
            formAkomodasiMethod.innerHTML = '';
            modalAkomodasiTitle.textContent = 'Tambah Rate Akomodasi';
        }

        function bukaModalEditAkomodasi(data) {
            document.getElementById('input-akomodasi-kecamatan').value = data.nama_kecamatan || '';
            document.getElementById('input-akomodasi-tarif').value = data.tarif_per_malam || '';
            document.getElementById('input-akomodasi-keterangan').value = data.keterangan || '';
            formAkomodasi.action = updateAkomodasiUrlTemplate.replace('__ID__', data.id_rate_akomodasi);
            formAkomodasiMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            modalAkomodasiTitle.textContent = 'Edit Rate Akomodasi';
        }

        // ----- Rate Transport -----
        const formTransport = document.getElementById('form-transport');
        const formTransportMethod = document.getElementById('form-transport-method');
        const modalTransportTitle = document.getElementById('modal-transport-title');
        const storeTransportUrl = '{{ route('rate.transport.store') }}';
        const updateTransportUrlTemplate = '{{ route('rate.transport.update', ['transport' => '__ID__']) }}';
        const inputLevel = document.getElementById('input-transport-level');
        const wrapperKecamatan = document.getElementById('wrapper-transport-kecamatan');
        const inputKecamatan = document.getElementById('input-transport-kecamatan');
        const labelWilayah = document.getElementById('label-transport-wilayah');

        function sesuaikanFormTransport() {
            const level = inputLevel.value;
            if (level === 'A') {
                wrapperKecamatan.style.display = 'none';
                inputKecamatan.value = '';
                labelWilayah.textContent = 'Kecamatan';
            } else {
                wrapperKecamatan.style.display = '';
                labelWilayah.textContent = 'Desa/Kelurahan';
            }
        }
        inputLevel.addEventListener('change', sesuaikanFormTransport);

        function bukaModalTambahTransport() {
            formTransport.reset();
            formTransport.action = storeTransportUrl;
            formTransportMethod.innerHTML = '';
            modalTransportTitle.textContent = 'Tambah Rate Transport';
            sesuaikanFormTransport();
        }

        function bukaModalEditTransport(data) {
            inputLevel.value = data.level || 'A';
            inputKecamatan.value = data.kecamatan_induk || '';
            document.getElementById('input-transport-wilayah').value = data.nama_wilayah || '';
            document.getElementById('input-transport-moda').value = data.moda_transportasi || '';
            document.getElementById('input-transport-nilai').value = data.nilai_pp || '';
            formTransport.action = updateTransportUrlTemplate.replace('__ID__', data.id_rate_transport);
            formTransportMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            modalTransportTitle.textContent = 'Edit Rate Transport';
            sesuaikanFormTransport();
        }
    </script>
@endpush
@endif
