@extends('layouts.app')

@section('title', 'Perjalanan Dinas Saya')

@section('content')
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Perjalanan Dinas Saya</h4>
        <p class="text-muted mb-0">Rekap perjalanan dinas yang Anda ajukan sebagai pemohon.</p>
    </div>

    @if (!$pegawaiId)
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle ti-lg mt-1"></i>
            <div>
                <div class="fw-semibold mb-1">Akun Anda belum terhubung ke data pegawai</div>
                <p class="mb-0">
                    Lengkapi data pegawai (nama, jabatan, pangkat, golongan) di halaman
                    <a href="{{ route('profile.edit') }}" class="alert-link">Profil</a> untuk mulai membuat dan melihat perjalanan dinas Anda.
                </p>
            </div>
        </div>
    @else
        @include('perjalanan-dinas._rekap-table', ['cardTitle' => 'Perjalanan Dinas Saya'])
    @endif
@endsection
