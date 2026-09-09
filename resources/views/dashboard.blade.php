@extends('layouts.app')

@section('title', 'Dashboard')

@push('page-css')
    <style>
        .card-welcome {
            background: linear-gradient(135deg, #696cff 0%, #8f92ff 100%);
            overflow: hidden;
        }

        .card-welcome .welcome-illustration {
            position: absolute;
            bottom: 0;
            right: 1.5rem;
            height: 130px;
        }

        .card-stat .avatar-initial {
            width: 3rem;
            height: 3rem;
        }

        .card-jenis-perjadin {
            transition: all 0.25s ease;
            border: 1px solid #e7e7e9;
        }

        .card-jenis-perjadin:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1.5rem rgba(67, 89, 113, 0.15);
            border-color: transparent;
        }

        .card-jenis-perjadin .avatar-initial {
            width: 3.5rem;
            height: 3.5rem;
        }

        .btn-buat-perjadin {
            transition: all 0.2s ease;
        }

        .card-jenis-perjadin:hover .btn-buat-perjadin {
            letter-spacing: 0.3px;
        }

        .btn-buat-perjadin i {
            transition: transform 0.2s ease;
        }

        .card-jenis-perjadin:hover .btn-buat-perjadin i {
            transform: translateX(3px);
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
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Welcome banner --}}
    <div class="card card-welcome text-white mb-4 position-relative">
        <div class="card-body p-4 p-md-5" style="max-width: 65%;">
            <h4 class="fw-bold text-white mb-1">Selamat datang, {{ auth()->user()->name }} 👋</h4>
            <p class="text-white-50 mb-0">Pilih jenis perjalanan dinas di bawah untuk mulai membuat dokumen SPJ baru.</p>
        </div>
        <img src="{{ asset('assets/img/illustrations/boy-with-laptop-light.png') }}" alt="Ilustrasi"
            class="welcome-illustration d-none d-md-block">
    </div>

    {{-- Ringkasan statistik --}}
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded-circle bg-label-dark">
                            <i class="ti ti-briefcase ti-md"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $statistikPerjadin['total'] }}</h5>
                        <small class="text-muted">Total Perjadin</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="ti ti-plane-departure ti-md"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $statistikPerjadin['biasa'] }}</h5>
                        <small class="text-muted">Perjadin Biasa</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded-circle bg-label-success">
                            <i class="ti ti-clock ti-md"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $statistikPerjadin['dalam_kota_kurang_8_jam'] }}</h5>
                        <small class="text-muted">Dalam Kota &le; 8 Jam</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded-circle bg-label-warning">
                            <i class="ti ti-clock ti-md"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $statistikPerjadin['dalam_kota_lebih_8_jam'] }}</h5>
                        <small class="text-muted">Dalam Kota &gt; 8 Jam</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Buat perjalanan dinas baru --}}
    <h5 class="mb-3">Buat Perjalanan Dinas Baru</h5>
    <div class="row g-4 mb-4">
        {{-- Card 1: Perjalanan Dinas Biasa --}}
        <div class="col-md-4">
            <div class="card card-jenis-perjadin h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="avatar avatar-lg mb-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="ti ti-plane-departure ti-lg"></i>
                        </span>
                    </div>
                    <h5 class="card-title mb-1">Perjalanan Dinas Biasa</h5>
                    <p class="text-muted mb-3">Perjalanan dinas ke luar kota, memerlukan Surat Tugas dan SPD lengkap.</p>
                    <span class="badge bg-label-primary d-inline-block mb-3">SPD wajib</span>
                    <a href="{{ route('perjalanan-dinas.create', ['jenis' => 'biasa']) }}"
                        class="btn btn-primary btn-buat-perjadin mt-auto d-inline-flex align-items-center justify-content-center gap-1">
                        Buat <i class="ti ti-arrow-right ti-sm"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 2: Dalam Kota <= 8 Jam --}}
        <div class="col-md-4">
            <div class="card card-jenis-perjadin h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="avatar avatar-lg mb-3">
                        <span class="avatar-initial rounded-circle bg-label-success">
                            <i class="ti ti-clock ti-lg"></i>
                        </span>
                    </div>
                    <h5 class="card-title mb-1">Dalam Kota &le; 8 Jam</h5>
                    <p class="text-muted mb-3">Perjalanan dinas dalam kota dengan durasi maksimal 8 jam, tanpa SPD.</p>
                    <span class="badge bg-label-success d-inline-block mb-3">Tanpa SPD</span>
                    <a href="{{ route('perjalanan-dinas.create', ['jenis' => 'dalam_kota_kurang_8_jam']) }}"
                        class="btn btn-success btn-buat-perjadin mt-auto d-inline-flex align-items-center justify-content-center gap-1">
                        Buat <i class="ti ti-arrow-right ti-sm"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 3: Dalam Kota > 8 Jam --}}
        <div class="col-md-4">
            <div class="card card-jenis-perjadin h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="avatar avatar-lg mb-3">
                        <span class="avatar-initial rounded-circle bg-label-warning">
                            <i class="ti ti-clock ti-lg"></i>
                        </span>
                    </div>
                    <h5 class="card-title mb-1">Dalam Kota &gt; 8 Jam</h5>
                    <p class="text-muted mb-3">Perjalanan dinas dalam kota lebih dari 8 jam, memerlukan SPD.</p>
                    <span class="badge bg-label-warning d-inline-block mb-3">SPD wajib</span>
                    <a href="{{ route('perjalanan-dinas.create', ['jenis' => 'dalam_kota_lebih_8_jam']) }}"
                        class="btn btn-warning btn-buat-perjadin mt-auto d-inline-flex align-items-center justify-content-center gap-1">
                        Buat <i class="ti ti-arrow-right ti-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('perjalanan-dinas._rekap-table')
@endsection
