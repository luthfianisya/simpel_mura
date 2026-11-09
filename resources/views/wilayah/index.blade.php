@extends('layouts.app')

@section('title', 'Kelola Wilayah')

@push('page-css')
    <style>
        .tab-wilayah { display: flex; flex-wrap: wrap; gap: 0.75rem; border: 0; }
        .tab-wilayah .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 240px;
            padding: 0.7rem 0.9rem;
            border: 1px solid #e3e7f1;
            border-radius: 0.5rem;
            background: #fff;
            color: #4b5a7c;
            text-align: left;
            transition: all 0.15s ease;
        }
        .tab-wilayah .nav-link:hover { border-color: rgba(37, 99, 235, 0.4); color: #2563eb; }
        .tab-wilayah .nav-link .ikon {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
        }
        .tab-wilayah .nav-link .judul { display: block; font-weight: 600; font-size: 0.9rem; }
        .tab-wilayah .nav-link small { color: #8a94ad; }
        .tab-wilayah .nav-link.active {
            border-color: #2563eb;
            background: rgba(37, 99, 235, 0.06);
            color: #2563eb;
            box-shadow: 0 6px 14px -8px rgba(37, 99, 235, 0.6);
        }
        .tab-wilayah .nav-link.active .ikon { color: #fff; background: #2563eb; }
        @media (max-width: 575.98px) {
            .tab-wilayah .nav-item, .tab-wilayah .nav-link { width: 100%; min-width: 0; }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kelola Wilayah</h4>
            <p class="text-muted mb-0">Kelola data master Kabupaten/Kota dan Desa/Kelurahan untuk dropdown Asal &amp; Tujuan di form perjalanan dinas.</p>
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

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="ti ti-bulb ti-sm mt-1"></i>
        <div>
            Kolom <strong>Dipilih</strong> menghitung berapa kali suatu wilayah dipakai sebagai Asal/Tujuan di perjalanan dinas.
            Wilayah yang paling sering dipilih akan otomatis muncul di bagian atas (Rekomendasi) pada dropdown form.
        </div>
    </div>

    {{-- Satu kartu lebar penuh dengan tab, supaya tabel, pencarian, dan pagination tidak
         berdesakan seperti saat dua tabel ditaruh berdampingan. --}}
    <div class="card">
        <div class="card-header pb-0">
            <ul class="nav tab-wilayah" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-kabkota" role="tab">
                        <span class="ikon"><i class="ti ti-building-community"></i></span>
                        <span>
                            <span class="judul">Kabupaten/Kota</span>
                            <small>{{ $daftarKabupatenKota->flatten()->count() }} wilayah &middot; {{ $daftarKabupatenKota->count() }} provinsi</small>
                        </span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-desa" role="tab">
                        <span class="ikon"><i class="ti ti-home-2"></i></span>
                        <span>
                            <span class="judul">Desa/Kelurahan</span>
                            <small>{{ $daftarDesa->flatten()->count() }} wilayah &middot; {{ $daftarDesa->count() }} kecamatan di Murung Raya</small>
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content p-0">
            {{-- Kabupaten/Kota --}}
            <div class="tab-pane fade show active" id="tab-kabkota" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-4 pt-4">
                    <div>
                        <h5 class="mb-0">Kabupaten/Kota</h5>
                        <p class="text-muted small mb-0">Pilihan Asal &amp; Tujuan untuk perjalanan dinas biasa (antar kabupaten/kota).</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-kabkota" onclick="bukaModalTambahKabKota()">
                        <i class="ti ti-plus ti-sm me-1"></i> Tambah Kabupaten/Kota
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table" data-datatable data-kosong="Belum ada data.">
                        <thead>
                            <tr>
                                <th>Provinsi</th>
                                <th>Nama</th>
                                <th class="text-end">Dipilih</th>
                                <th data-orderable="false">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarKabupatenKota as $provinsi => $items)
                                @foreach ($items as $item)
                                    <tr>
                                        {{-- Diisi di setiap baris supaya tetap benar saat diurutkan, dicari, atau pindah halaman. --}}
                                        <td class="text-muted">{{ $provinsi }}</td>
                                        <td class="fw-medium">{{ $item->nama }}</td>
                                        <td class="text-end" data-order="{{ $item->jumlah_dipilih }}">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Desa/Kelurahan --}}
            <div class="tab-pane fade" id="tab-desa" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-4 pt-4">
                    <div>
                        <h5 class="mb-0">Desa/Kelurahan (Kabupaten Murung Raya)</h5>
                        <p class="text-muted small mb-0">Pilihan Asal &amp; Tujuan untuk perjalanan dinas dalam kota.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-desa" onclick="bukaModalTambahDesa()">
                        <i class="ti ti-plus ti-sm me-1"></i> Tambah Desa/Kelurahan
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table" data-datatable data-kosong="Belum ada data.">
                        <thead>
                            <tr>
                                <th>Kecamatan</th>
                                <th>Jenis</th>
                                <th>Nama</th>
                                <th class="text-end">Dipilih</th>
                                <th data-orderable="false">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarDesa as $kecamatan => $items)
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="text-muted">{{ $kecamatan }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $item->jenis === 'kelurahan' ? 'bg-label-info' : 'bg-label-primary' }}">{{ ucfirst($item->jenis) }}</span>
                                        </td>
                                        <td class="fw-medium">{{ $item->nama }}</td>
                                        <td class="text-end" data-order="{{ $item->jumlah_dipilih }}">
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
                            @endforeach
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
        // Ingat tab terakhir (mis. setelah tambah/edit desa, halaman kembali ke tab Desa).
        (function () {
            const KUNCI = 'simpel.wilayah.tab';
            try {
                const tersimpan = localStorage.getItem(KUNCI);
                const tombol = tersimpan && document.querySelector('.tab-wilayah [data-bs-target="' + tersimpan + '"]');
                if (tombol) bootstrap.Tab.getOrCreateInstance(tombol).show();
            } catch (e) {}
            document.querySelectorAll('.tab-wilayah [data-bs-toggle="tab"]').forEach(function (tombol) {
                tombol.addEventListener('shown.bs.tab', function () {
                    try { localStorage.setItem(KUNCI, tombol.dataset.bsTarget); } catch (e) {}
                    if (window.jQuery && jQuery.fn.dataTable) jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            });
        })();

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
