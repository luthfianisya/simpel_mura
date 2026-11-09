@extends('layouts.app')

@section('title', 'Penomoran SPD')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Penomoran SPD</h4>
            <p class="text-muted mb-0">Atur nomor pertama SPD tiap tahun. Format nomor: <span class="fw-medium">{{ $contohFormat }}</span>.</p>
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
        <i class="ti ti-info-circle ti-sm mt-1"></i>
        <div>
            Sistem selalu memberi <strong>nomor terkecil yang belum terpakai</strong> mulai dari nomor pertama yang diatur di sini,
            jadi tidak ada nomor dobel maupun bolong. Nomor di bawah nomor pertama tidak akan pernah dipakai.
            SPD yang sudah bernomor tidak ikut berubah saat pengaturan ini diubah.
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Pengaturan per Tahun</h5>
                <p class="text-muted small mb-0">Tahun yang belum diatur otomatis mulai dari nomor 1.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-penomoran" onclick="bukaModalTambahPenomoran()">
                <i class="ti ti-plus ti-sm me-1"></i> Tambah Tahun
            </button>
        </div>
        <div class="table-responsive px-4 pb-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tahun</th>
                        <th class="text-end">Dimulai dari Nomor</th>
                        <th class="text-end">SPD Terpakai</th>
                        <th class="text-end">Nomor Terakhir</th>
                        <th>Nomor Berikutnya</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarPenomoran as $baris)
                        <tr>
                            <td class="fw-semibold">
                                {{ $baris['tahun'] }}
                                @if ($baris['tahun'] === (int) now()->year)
                                    <span class="badge rounded-pill bg-label-primary ms-1">Tahun ini</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">{{ $baris['mulai'] }}</td>
                            <td class="text-end">{{ $baris['jumlah_terpakai'] }}</td>
                            <td class="text-end">{{ $baris['terakhir'] ?? '-' }}</td>
                            <td><span class="badge bg-label-success fs-6">{{ $baris['berikutnya'] }}</span></td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                    data-bs-toggle="modal" data-bs-target="#modal-penomoran"
                                    onclick='bukaModalEditPenomoran({{ json_encode([
                                        "url" => $baris["model"]->exists ? route("penomoran-spd.update", $baris["model"]) : null,
                                        "tahun" => $baris["tahun"],
                                        "mulai" => $baris["mulai"],
                                    ]) }})'>
                                    <i class="ti ti-edit ti-sm"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal tambah / edit --}}
    <div class="modal fade" id="modal-penomoran" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-penomoran" method="POST" action="{{ route('penomoran-spd.store') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    {{-- Diisi saat submit, supaya modal bisa dibuka lagi ke tujuan yang sama kalau validasi gagal. --}}
                    <input type="hidden" name="_aksi" value="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="judul-modal-penomoran">Tambah Penomoran SPD</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="input-tahun">Tahun</label>
                            <input type="number" id="input-tahun" name="tahun" class="form-control" min="2000" max="2100" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="input-nomor-mulai">Nomor SPD pertama</label>
                            <input type="number" id="input-nomor-mulai" name="nomor_mulai" class="form-control" min="1" required>
                        </div>
                        <div class="form-text">
                            Contoh: isi <strong>276</strong> supaya SPD tahun ini dimulai dari
                            <span id="contoh-nomor">276/62130/KU.340/{{ now()->year }}</span>.
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
        const formPenomoran = document.getElementById('form-penomoran');
        const inputTahun = document.getElementById('input-tahun');
        const inputMulai = document.getElementById('input-nomor-mulai');
        const URL_TAMBAH = @json(route('penomoran-spd.store'));

        function perbaruiContoh() {
            document.getElementById('contoh-nomor').textContent =
                (inputMulai.value || '276') + '/62130/KU.340/' + (inputTahun.value || new Date().getFullYear());
        }
        inputTahun.addEventListener('input', perbaruiContoh);
        inputMulai.addEventListener('input', perbaruiContoh);

        function bukaModalTambahPenomoran() {
            document.getElementById('judul-modal-penomoran').textContent = 'Tambah Penomoran SPD';
            formPenomoran.action = URL_TAMBAH;
            formPenomoran.querySelector('input[name=_method]').value = 'POST';
            inputTahun.readOnly = false;
            inputTahun.value = new Date().getFullYear() + 1;
            inputMulai.value = 1;
            perbaruiContoh();
        }

        // Tahun yang belum pernah diatur (baris tahun berjalan bawaan) disimpan sebagai data baru.
        function bukaModalEditPenomoran(data) {
            document.getElementById('judul-modal-penomoran').textContent = 'Ubah Penomoran SPD ' + data.tahun;
            formPenomoran.action = data.url || URL_TAMBAH;
            formPenomoran.querySelector('input[name=_method]').value = data.url ? 'PUT' : 'POST';
            inputTahun.value = data.tahun;
            inputTahun.readOnly = true;
            inputMulai.value = data.mulai;
            perbaruiContoh();
        }

        formPenomoran.addEventListener('submit', function () {
            formPenomoran.querySelector('input[name=_aksi]').value = formPenomoran.action;
        });

        @if ($errors->any())
            // Buka lagi modalnya kalau validasi gagal, dengan isian & tujuan sebelumnya.
            document.addEventListener('DOMContentLoaded', function () {
                const aksi = @json(old('_aksi'));
                const ubah = @json(old('_method')) === 'PUT';
                if (aksi) formPenomoran.action = aksi;
                formPenomoran.querySelector('input[name=_method]').value = ubah ? 'PUT' : 'POST';
                inputTahun.readOnly = ubah;
                inputTahun.value = @json(old('tahun'));
                inputMulai.value = @json(old('nomor_mulai'));
                perbaruiContoh();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-penomoran')).show();
            });
        @endif
    </script>
@endpush
