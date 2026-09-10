@extends('layouts.app')

@section('title', $judul)

@php
    $peranLabel = [
        'kepala_satker' => 'Kepala Satker',
        'ppk' => 'PPK',
        'bendahara' => 'Bendahara',
    ];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $judul }}</h4>
            <p class="text-muted mb-0">
                {{ $statusKepegawaian === 'pegawai' ? 'Kelola data pegawai BPS Kabupaten Murung Raya.' : 'Kelola data mitra statistik.' }}
            </p>
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

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Daftar {{ $statusKepegawaian === 'pegawai' ? 'Pegawai' : 'Mitra' }}</h5>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-label-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-file-spreadsheet ti-sm me-1"></i> Excel
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('pegawai-mitra.template', $statusKepegawaian) }}">
                                <i class="ti ti-download me-1"></i> Unduh Template
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal-import-pegawai-mitra">
                                <i class="ti ti-upload me-1"></i> Import Data
                            </a>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-pegawai-mitra" onclick="bukaModalTambah()">
                    <i class="ti ti-plus ti-sm me-1"></i> Tambah {{ $statusKepegawaian === 'pegawai' ? 'Pegawai' : 'Mitra' }}
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Pangkat</th>
                        <th>Golongan</th>
                        <th>Peran Pejabat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nip ?? '-' }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->jabatan ?? '-' }}</td>
                            <td>{{ $item->pangkat ?? '-' }}</td>
                            <td>{{ $item->golongan ?? '-' }}</td>
                            <td>{{ $item->peran_pejabat ? $peranLabel[$item->peran_pejabat] : '-' }}</td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                    data-bs-toggle="modal" data-bs-target="#modal-pegawai-mitra"
                                    onclick='bukaModalEdit(@json($item))'>
                                    <i class="ti ti-edit ti-sm"></i>
                                </button>
                                <form action="{{ route('pegawai-mitra.destroy', $item->id_pegawai_mitra) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, 'Hapus {{ $item->nama }}? Data yang masih dipakai di perjalanan dinas tidak bisa dihapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus">
                                        <i class="ti ti-trash ti-sm"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada data {{ $statusKepegawaian === 'pegawai' ? 'pegawai' : 'mitra' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah/Edit --}}
    <div class="modal fade" id="modal-pegawai-mitra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-pegawai-mitra" method="POST">
                    @csrf
                    <div id="form-pegawai-mitra-method"></div>
                    <input type="hidden" name="status_kepegawaian" value="{{ $statusKepegawaian }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-pegawai-mitra-title">Tambah {{ $statusKepegawaian === 'pegawai' ? 'Pegawai' : 'Mitra' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nama</label>
                                <input type="text" id="input-pm-nama" name="nama" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIP</label>
                                <input type="text" id="input-pm-nip" name="nip" class="form-control" placeholder="mis. 19990307 202104 1 001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan</label>
                                <input type="text" id="input-pm-jabatan" name="jabatan" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pangkat</label>
                                <input type="text" id="input-pm-pangkat" name="pangkat" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Golongan</label>
                                <input type="text" id="input-pm-golongan" name="golongan" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Peran Pejabat</label>
                                <select id="input-pm-peran_pejabat" name="peran_pejabat" class="form-select">
                                    <option value="">-</option>
                                    <option value="kepala_satker">Kepala Satker</option>
                                    <option value="ppk">PPK</option>
                                    <option value="bendahara">Bendahara</option>
                                </select>
                            </div>
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

    {{-- Modal Import Excel --}}
    <div class="modal fade" id="modal-import-pegawai-mitra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('pegawai-mitra.import', $statusKepegawaian) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import {{ $statusKepegawaian === 'pegawai' ? 'Data Pegawai' : 'Data Mitra' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Belum punya filenya? <a href="{{ route('pegawai-mitra.template', $statusKepegawaian) }}">Unduh template Excel</a> terlebih dahulu, isi datanya, baru unggah di sini.
                        </p>
                        <label class="form-label">File Excel (.xlsx)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            Baris dengan NIP yang sudah terdaftar akan diperbarui datanya, NIP baru/kosong akan ditambahkan sebagai data baru.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    <script>
        const formPegawaiMitra = document.getElementById('form-pegawai-mitra');
        const formPegawaiMitraMethod = document.getElementById('form-pegawai-mitra-method');
        const modalPegawaiMitraTitle = document.getElementById('modal-pegawai-mitra-title');
        const storeUrl = '{{ route('pegawai-mitra.store') }}';
        const updateUrlTemplate = '{{ route('pegawai-mitra.update', ['pegawaiMitra' => '__ID__']) }}';
        const statusKepegawaian = @json($statusKepegawaian);
        const labelJenis = statusKepegawaian === 'pegawai' ? 'Pegawai' : 'Mitra';

        function isiForm(data) {
            document.getElementById('input-pm-nama').value = data.nama || '';
            document.getElementById('input-pm-nip').value = data.nip || '';
            document.getElementById('input-pm-jabatan').value = data.jabatan || '';
            document.getElementById('input-pm-pangkat').value = data.pangkat || '';
            document.getElementById('input-pm-golongan').value = data.golongan || '';
            document.getElementById('input-pm-peran_pejabat').value = data.peran_pejabat || '';
        }

        function bukaModalTambah() {
            formPegawaiMitra.reset();
            document.getElementById('input-pm-peran_pejabat').value = '';
            formPegawaiMitra.action = storeUrl;
            formPegawaiMitraMethod.innerHTML = '';
            modalPegawaiMitraTitle.textContent = 'Tambah ' + labelJenis;
        }

        function bukaModalEdit(data) {
            isiForm(data);
            formPegawaiMitra.action = updateUrlTemplate.replace('__ID__', data.id_pegawai_mitra);
            formPegawaiMitraMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            modalPegawaiMitraTitle.textContent = 'Edit ' + labelJenis;
        }
    </script>
@endpush
