@extends('layouts.app')

@section('title', 'Rate & Dokumen Pendukung')

@php
    $fmtRupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $isAdmin = auth()->user()->role === 'administrator';
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Rate & Dokumen Pendukung</h4>
            <p class="text-muted mb-0">Besaran biaya rate akomodasi &amp; transport dalam kota, serta dokumen referensi (SK, nota, peraturan) sesuai ketentuan yang berlaku.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-label-secondary d-inline-flex align-items-center gap-1">
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

    {{-- Rate Akomodasi --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-0">Rate Akomodasi Dalam Kota</h5>
                <p class="text-muted small mb-0">Tarif akomodasi per malam per kecamatan.</p>
            </div>
            @if ($isAdmin)
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-akomodasi" onclick="bukaModalTambahAkomodasi()">
                    <i class="ti ti-plus ti-sm me-1"></i> Tambah
                </button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kecamatan</th>
                        <th class="text-end">Tarif / Malam</th>
                        <th>Keterangan</th>
                        @if ($isAdmin)
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarAkomodasi as $item)
                        <tr>
                            <td>{{ $item->nama_kecamatan }}</td>
                            <td class="text-end">{{ $fmtRupiah($item->tarif_per_malam) }}</td>
                            <td>{{ $item->keterangan ?? '-' }}</td>
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
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 4 : 3 }}" class="text-center text-muted py-4">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rate Transport --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Rate Transport Dalam Kota</h5>
                <p class="text-muted small mb-0">A: Kabupaten &rarr; Kecamatan &middot; B: Kecamatan &rarr; Desa/Kel. &middot; C: Kabupaten &rarr; Desa/Kel. langsung.</p>
            </div>
            @if ($isAdmin)
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-transport" onclick="bukaModalTambahTransport()">
                    <i class="ti ti-plus ti-sm me-1"></i> Tambah
                </button>
            @endif
        </div>
        <div class="card-body pb-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-level-a">A</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-level-b">B</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-level-c">C</button></li>
            </ul>
        </div>
        <div class="tab-content">
            {{-- Level A --}}
            <div class="tab-pane fade show active" id="tab-level-a">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kecamatan</th>
                                <th>Moda Transportasi</th>
                                <th class="text-end">Nilai PP</th>
                                @if ($isAdmin)
                                    <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($daftarTransportA as $item)
                                <tr>
                                    <td>{{ $item->nama_wilayah }}</td>
                                    <td>{{ $item->moda_transportasi }}</td>
                                    <td class="text-end">{{ $fmtRupiah($item->nilai_pp) }}</td>
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
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdmin ? 4 : 3 }}" class="text-center text-muted py-4">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Level B & C: dikelompokkan per kecamatan --}}
            @foreach (['tab-level-b' => $daftarTransportB, 'tab-level-c' => $daftarTransportC] as $tabId => $daftar)
                <div class="tab-pane fade" id="{{ $tabId }}">
                    <div class="table-responsive" style="max-height: 560px; overflow-y: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kecamatan</th>
                                    <th>Desa/Kelurahan</th>
                                    <th>Moda Transportasi</th>
                                    <th class="text-end">Nilai PP</th>
                                    @if ($isAdmin)
                                        <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($daftar as $kecamatan => $itemsPerKecamatan)
                                    @foreach ($itemsPerKecamatan as $item)
                                        <tr>
                                            <td>{{ $loop->first ? $kecamatan : '' }}</td>
                                            <td>{{ $item->nama_wilayah }}</td>
                                            <td>{{ $item->moda_transportasi }}</td>
                                            <td class="text-end">{{ $fmtRupiah($item->nilai_pp) }}</td>
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
                                @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 5 : 4 }}" class="text-center text-muted py-4">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Dokumen Pendukung --}}
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Dokumen Pendukung</h5>
            <p class="text-muted small mb-0">SK, nota, dan peraturan terkait pertanggungjawaban perjalanan dinas.</p>
        </div>
        <div class="list-group list-group-flush">
            @foreach ($daftarDokumenPendukung as $key => $dokumen)
                <a href="{{ route('dokumen-pendukung.download', $key) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                    <i class="ti ti-file-download ti-lg text-primary"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $dokumen['label'] }}</div>
                        <div class="text-muted small">{{ $dokumen['keterangan'] }}</div>
                    </div>
                    <i class="ti ti-download ti-sm text-muted"></i>
                </a>
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
