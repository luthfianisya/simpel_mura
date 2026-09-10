@extends('layouts.app')

@section('title', 'Kelola Akun')

@php
    $roleBadge = ['administrator' => 'bg-label-danger', 'pegawai' => 'bg-label-info'];
    $roleLabel = ['administrator' => 'Administrator', 'pegawai' => 'Pegawai (User)'];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kelola Akun</h4>
            <p class="text-muted mb-0">Kelola akun pengguna beserta hak akses perannya (Admin/Pegawai).</p>
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
            <h5 class="mb-0">Daftar Akun</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-akun" {{ $daftarPegawaiBelumPunyaAkun->isEmpty() ? 'disabled title="Semua pegawai sudah punya akun"' : '' }}>
                <i class="ti ti-plus ti-sm me-1"></i> Tambah Akun
            </button>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Pegawai Terhubung</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarUser as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->name }}</td>
                            <td><code>{{ $item->username ?? '-' }}</code></td>
                            <td>
                                @if ($item->pegawaiMitra)
                                    {{ $item->pegawaiMitra->jabatan ?? '-' }}
                                    @if ($item->pegawaiMitra->nip)
                                        <div class="text-muted small">NIP. {{ $item->pegawaiMitra->nip }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $roleBadge[$item->role] ?? 'bg-label-secondary' }}">
                                    {{ $roleLabel[$item->role] ?? ucfirst($item->role) }}
                                </span>
                            </td>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @php
                                    $editPayload = json_encode($item->only(['id', 'name', 'username', 'role']));
                                    $resetPayload = json_encode(['id' => $item->id, 'name' => $item->name]);
                                @endphp
                                <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Edit"
                                    data-bs-toggle="modal" data-bs-target="#modal-edit-akun"
                                    onclick='bukaModalEdit({{ $editPayload }})'>
                                    <i class="ti ti-edit ti-sm"></i>
                                </button>
                                <button type="button" class="btn btn-icon btn-sm btn-text-warning rounded-pill" title="Reset Password"
                                    data-bs-toggle="modal" data-bs-target="#modal-reset-password"
                                    onclick='bukaModalReset({{ $resetPayload }})'>
                                    <i class="ti ti-key ti-sm"></i>
                                </button>
                                <form action="{{ route('user.toggle-aktif', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, '{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $item->name }}?', { icon: 'question', confirmButtonColor: '#696cff' });">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-icon btn-sm {{ $item->is_active ? 'btn-text-secondary' : 'btn-text-success' }} rounded-pill" title="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="ti {{ $item->is_active ? 'ti-lock' : 'ti-lock-open' }} ti-sm"></i>
                                    </button>
                                </form>
                                <form action="{{ route('user.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirmSubmit(this, 'Hapus akun {{ $item->name }}? Data pegawai tidak ikut terhapus.');">
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
                            <td colspan="7" class="text-center text-muted py-4">Belum ada akun.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah Akun --}}
    <div class="modal fade" id="modal-tambah-akun" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('user.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pegawai</label>
                            <select name="id_pegawai_mitra" class="form-select" required>
                                <option value="">Pilih pegawai...</option>
                                @foreach ($daftarPegawaiBelumPunyaAkun as $pegawai)
                                    <option value="{{ $pegawai->id_pegawai_mitra }}">{{ $pegawai->nama }} @if ($pegawai->jabatan) &mdash; {{ $pegawai->jabatan }} @endif</option>
                                @endforeach
                            </select>
                            <div class="form-text">Hanya pegawai yang belum memiliki akun yang muncul di sini.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="mis. fia" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Peran</label>
                            <select name="role" class="form-select" required>
                                <option value="pegawai">Pegawai (User)</option>
                                <option value="administrator">Administrator</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Password</label>
                            <input type="text" name="password" class="form-control" placeholder="Kosongkan untuk password default">
                            <div class="form-text">Jika dikosongkan, password default "password" akan digunakan.</div>
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

    {{-- Modal Edit Akun --}}
    <div class="modal fade" id="modal-edit-akun" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-edit-akun" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" id="edit-akun-name" class="form-control" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" id="edit-akun-username" name="username" class="form-control" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Peran</label>
                            <select id="edit-akun-role" name="role" class="form-select" required>
                                <option value="pegawai">Pegawai (User)</option>
                                <option value="administrator">Administrator</option>
                            </select>
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

    {{-- Modal Reset Password --}}
    <div class="modal fade" id="modal-reset-password" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-reset-password" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Reset Password <span id="reset-akun-name"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Password Baru</label>
                        <input type="text" name="password" class="form-control" placeholder="Kosongkan untuk password default">
                        <div class="form-text">Jika dikosongkan, password akan direset menjadi "password".</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    <script>
        const formEditAkun = document.getElementById('form-edit-akun');
        const updateUrlTemplate = '{{ route('user.update', ['user' => '__ID__']) }}';

        function bukaModalEdit(data) {
            document.getElementById('edit-akun-name').value = data.name || '';
            document.getElementById('edit-akun-username').value = data.username || '';
            document.getElementById('edit-akun-role').value = data.role || 'pegawai';
            formEditAkun.action = updateUrlTemplate.replace('__ID__', data.id);
        }

        const formResetPassword = document.getElementById('form-reset-password');
        const resetUrlTemplate = '{{ route('user.reset-password', ['user' => '__ID__']) }}';

        function bukaModalReset(data) {
            document.getElementById('reset-akun-name').textContent = data.name || '';
            formResetPassword.action = resetUrlTemplate.replace('__ID__', data.id);
        }
    </script>
@endpush
