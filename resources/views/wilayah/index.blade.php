@extends('layouts.app')

@section('title', 'Kelola Wilayah')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kelola Wilayah</h4>
            <p class="text-muted mb-0">Kelola data master Kabupaten/Kota dan Desa/Kelurahan untuk dropdown Asal &amp; Tujuan di form perjalanan dinas.</p>
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

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="ti ti-bulb ti-sm mt-1"></i>
        <div>
            Kolom <strong>Dipilih</strong> menghitung berapa kali suatu wilayah dipakai sebagai Asal/Tujuan di perjalanan dinas.
            Wilayah yang paling sering dipilih akan otomatis muncul di bagian atas (Rekomendasi) pada dropdown form.
        </div>
    </div>

    <div class="row g-4">
        {{-- Kabupaten/Kota --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Kabupaten/Kota</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-kabkota" onclick="bukaModalTambahKabKota()">
                        <i class="ti ti-plus ti-sm me-1"></i> Tambah
                    </button>
                </div>
                <div class="table-responsive" style="max-height: 560px; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Provinsi</th>
                                <th>Nama</th>
                                <th class="text-end">Dipilih</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($daftarKabupatenKota as $provinsi => $items)
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $loop->first ? $provinsi : '' }}</td>
                                        <td>{{ $item->nama }}</td>
                                        <td class="text-end">
                                            <span class="badge {{ $item->jumlah_dipilih > 0 ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $item->jumlah_dipilih }}x</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                                data-bs-toggle="modal" data-bs-target="#modal-kabkota"
                                                onclick='bukaModalEditKabKota({{ json_encode(["id" => $item->id, "nama" => $item->nama, "provinsi" => $item->provinsi]) }})'>
                                                <i class="ti ti-edit ti-sm"></i>
                                            </button>
                                            <form action="{{ route('wilayah.kabupaten-kota.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, 'Hapus {{ $item->nama }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus">
                                                    <i class="ti ti-trash ti-sm"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Desa/Kelurahan --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Desa/Kelurahan (Kabupaten Murung Raya)</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-desa" onclick="bukaModalTambahDesa()">
                        <i class="ti ti-plus ti-sm me-1"></i> Tambah
                    </button>
                </div>
                <div class="table-responsive" style="max-height: 560px; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kecamatan</th>
                                <th>Jenis</th>
                                <th>Nama</th>
                                <th class="text-end">Dipilih</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($daftarDesa as $kecamatan => $items)
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $loop->first ? $kecamatan : '' }}</td>
                                        <td>{{ ucfirst($item->jenis) }}</td>
                                        <td>{{ $item->nama }}</td>
                                        <td class="text-end">
                                            <span class="badge {{ $item->jumlah_dipilih > 0 ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $item->jumlah_dipilih }}x</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                                data-bs-toggle="modal" data-bs-target="#modal-desa"
                                                onclick='bukaModalEditDesa({{ json_encode(["id" => $item->id, "kecamatan" => $item->kecamatan, "jenis" => $item->jenis, "nama" => $item->nama]) }})'>
                                                <i class="ti ti-edit ti-sm"></i>
                                            </button>
                                            <form action="{{ route('wilayah.desa.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, 'Hapus {{ $item->nama }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus">
                                                    <i class="ti ti-trash ti-sm"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Kabupaten/Kota --}}
    <div class="modal fade" id="modal-kabkota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-kabkota" method="POST">
                    @csrf
                    <div id="form-kabkota-method"></div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-kabkota-title">Tambah Kabupaten/Kota</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" id="input-kabkota-nama" name="nama" class="form-control" placeholder="mis. Kabupaten Barito Selatan" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Provinsi</label>
                            <input type="text" id="input-kabkota-provinsi" name="provinsi" class="form-control" list="daftar-provinsi" placeholder="mis. Kalimantan Tengah">
                            <datalist id="daftar-provinsi">
                                @foreach ($daftarProvinsi as $provinsi)
                                    <option value="{{ $provinsi }}">
                                @endforeach
                            </datalist>
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

    {{-- Modal Desa/Kelurahan --}}
    <div class="modal fade" id="modal-desa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-desa" method="POST">
                    @csrf
                    <div id="form-desa-method"></div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-desa-title">Tambah Desa/Kelurahan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kecamatan</label>
                            <input type="text" id="input-desa-kecamatan" name="kecamatan" class="form-control" list="daftar-kecamatan" placeholder="mis. Murung" required>
                            <datalist id="daftar-kecamatan">
                                @foreach ($daftarDesa->keys() as $kecamatan)
                                    <option value="{{ $kecamatan }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis</label>
                            <select id="input-desa-jenis" name="jenis" class="form-select" required>
                                <option value="desa">Desa</option>
                                <option value="kelurahan">Kelurahan</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nama</label>
                            <input type="text" id="input-desa-nama" name="nama" class="form-control" placeholder="mis. Beriwit" required>
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
@endsection

@push('page-js')
    <script>
        const formKabKota = document.getElementById('form-kabkota');
        const formKabKotaMethod = document.getElementById('form-kabkota-method');
        const modalKabKotaTitle = document.getElementById('modal-kabkota-title');
        const storeKabKotaUrl = '{{ route('wilayah.kabupaten-kota.store') }}';
        const updateKabKotaUrlTemplate = '{{ route('wilayah.kabupaten-kota.update', ['kabupatenKota' => '__ID__']) }}';

        function bukaModalTambahKabKota() {
            formKabKota.reset();
            formKabKota.action = storeKabKotaUrl;
            formKabKotaMethod.innerHTML = '';
            modalKabKotaTitle.textContent = 'Tambah Kabupaten/Kota';
        }

        function bukaModalEditKabKota(data) {
            document.getElementById('input-kabkota-nama').value = data.nama || '';
            document.getElementById('input-kabkota-provinsi').value = data.provinsi || '';
            formKabKota.action = updateKabKotaUrlTemplate.replace('__ID__', data.id);
            formKabKotaMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            modalKabKotaTitle.textContent = 'Edit Kabupaten/Kota';
        }

        const formDesa = document.getElementById('form-desa');
        const formDesaMethod = document.getElementById('form-desa-method');
        const modalDesaTitle = document.getElementById('modal-desa-title');
        const storeDesaUrl = '{{ route('wilayah.desa.store') }}';
        const updateDesaUrlTemplate = '{{ route('wilayah.desa.update', ['desa' => '__ID__']) }}';

        function bukaModalTambahDesa() {
            formDesa.reset();
            formDesa.action = storeDesaUrl;
            formDesaMethod.innerHTML = '';
            modalDesaTitle.textContent = 'Tambah Desa/Kelurahan';
        }

        function bukaModalEditDesa(data) {
            document.getElementById('input-desa-kecamatan').value = data.kecamatan || '';
            document.getElementById('input-desa-jenis').value = data.jenis || 'desa';
            document.getElementById('input-desa-nama').value = data.nama || '';
            formDesa.action = updateDesaUrlTemplate.replace('__ID__', data.id);
            formDesaMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            modalDesaTitle.textContent = 'Edit Desa/Kelurahan';
        }
    </script>
@endpush
