@extends('layouts.app')

@section('title', 'Profil Saya')

@php
    $roleBadge = match ($user->role) {
        'administrator' => 'bg-label-danger',
        'pegawai' => 'bg-label-info',
        default => 'bg-label-secondary',
    };
    $roleLabel = match ($user->role) {
        'administrator' => 'Administrator',
        'pegawai' => 'Pegawai',
        default => ucfirst($user->role),
    };
@endphp

@section('content')
    @if (session('status') === 'profile-updated')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Profil berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('status') === 'password-updated')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Password berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('status') === 'pegawai-updated')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Data pegawai berhasil disimpan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('status') === 'pegawai-required')
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            Lengkapi data pegawai Anda terlebih dahulu (nama, jabatan, pangkat, golongan) sebelum bisa membuat perjalanan dinas.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Header profil --}}
    <div class="card mb-4">
        <div class="card-body d-flex align-items-center gap-4 flex-wrap">
            <div class="avatar avatar-xl">
                <span class="avatar-initial rounded-circle bg-label-primary" style="font-size: 1.75rem;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
            </div>
            <div class="flex-grow-1">
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-2">
                    <code>{{ $user->username }}</code>
                    @if ($user->pegawaiMitra?->nip)
                        &middot; NIP. {{ $user->pegawaiMitra->nip }}
                    @endif
                </p>
                <span class="badge {{ $roleBadge }}">{{ $roleLabel }}</span>
                @if ($user->pegawaiMitra?->jabatan)
                    <span class="badge bg-label-secondary">{{ $user->pegawaiMitra->jabatan }}</span>
                @endif
                @if ($user->pegawaiMitra && ($user->pegawaiMitra->pangkat || $user->pegawaiMitra->golongan))
                    <span class="badge bg-label-secondary">{{ trim($user->pegawaiMitra->pangkat . ' ' . ($user->pegawaiMitra->golongan ? '/ ' . $user->pegawaiMitra->golongan : '')) }}</span>
                @endif
                @if (!$user->pegawaiMitra)
                    <span class="badge bg-label-warning">Belum terhubung ke data pegawai</span>
                @endif
            </div>
            <div class="text-muted small text-md-end">
                Bergabung sejak<br>
                <span class="fw-semibold text-body">{{ $user->created_at->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-1">Informasi Profil</h5>
                    <p class="text-muted small mb-4">Perbarui nama dan email akun Anda.</p>
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $user->name) }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-1">Data Pegawai</h5>
                    <p class="text-muted small mb-4">
                        @if ($user->pegawaiMitra)
                            Perbarui data kepegawaian Anda.
                        @else
                            Akun Anda belum terhubung ke data pegawai. Isi data di bawah untuk menautkannya.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('profile.pegawai.update') }}">
                        @csrf
                        @method('patch')
                        <div class="mb-3">
                            <label for="pegawai_nip" class="form-label">NIP</label>
                            <input type="text" id="pegawai_nip" name="nip" class="form-control @error('nip') is-invalid @enderror"
                                value="{{ old('nip', $user->pegawaiMitra->nip ?? '') }}">
                            @error('nip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="pegawai_nama" class="form-label">Nama Pegawai</label>
                            <input type="text" id="pegawai_nama" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama', $user->pegawaiMitra->nama ?? $user->name) }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="pegawai_jabatan" class="form-label">Jabatan</label>
                            <input type="text" id="pegawai_jabatan" name="jabatan" class="form-control @error('jabatan') is-invalid @enderror"
                                value="{{ old('jabatan', $user->pegawaiMitra->jabatan ?? '') }}">
                            @error('jabatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="pegawai_pangkat" class="form-label">Pangkat</label>
                                <input type="text" id="pegawai_pangkat" name="pangkat" class="form-control @error('pangkat') is-invalid @enderror"
                                    value="{{ old('pangkat', $user->pegawaiMitra->pangkat ?? '') }}">
                                @error('pangkat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="pegawai_golongan" class="form-label">Golongan</label>
                                <input type="text" id="pegawai_golongan" name="golongan" class="form-control @error('golongan') is-invalid @enderror"
                                    value="{{ old('golongan', $user->pegawaiMitra->golongan ?? '') }}" placeholder="mis. III/a">
                                @error('golongan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Data Pegawai</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-1">Ubah Password</h5>
                    <p class="text-muted small mb-4">Gunakan password yang panjang dan acak agar akun tetap aman.</p>
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password"
                                class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                autocomplete="current-password">
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" id="password" name="password"
                                class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                autocomplete="new-password">
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                                autocomplete="new-password">
                            @error('password_confirmation', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Ubah Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4 border-danger">
        <div class="card-body">
            <h5 class="mb-1 text-danger">Hapus Akun</h5>
            <p class="text-muted small mb-3">Setelah akun dihapus, seluruh data terkait akan hilang permanen. Tindakan ini tidak dapat dibatalkan.</p>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-hapus-akun">
                Hapus Akun
            </button>
        </div>
    </div>

    <div class="modal fade" id="modal-hapus-akun" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Hapus Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Masukkan password Anda untuk mengonfirmasi penghapusan akun secara permanen.</p>
                        <input type="password" name="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Password">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus Akun Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->userDeletion->isNotEmpty())
        @push('page-js')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    new bootstrap.Modal(document.getElementById('modal-hapus-akun')).show();
                });
            </script>
        @endpush
    @endif
@endsection
