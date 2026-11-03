@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $jenisPerjadin = [
        ['jenis' => 'biasa', 'judul' => 'Perjalanan Dinas Biasa', 'ikon' => 'ti-plane-departure', 'warna' => 'primary', 'spd' => 'SPD wajib'],
        ['jenis' => 'dalam_kota_kurang_8_jam', 'judul' => 'Dalam Kota &le; 8 Jam', 'ikon' => 'ti-clock', 'warna' => 'success', 'spd' => 'Tanpa SPD'],
        ['jenis' => 'dalam_kota_lebih_8_jam', 'judul' => 'Dalam Kota &gt; 8 Jam', 'ikon' => 'ti-map-pin', 'warna' => 'warning', 'spd' => 'SPD wajib'],
    ];

    $total = max($statistikPerjadin['total'], 1);
    $kartuJumlah = [
        ['label' => 'Perjalanan Dinas Biasa', 'nilai' => $statistikPerjadin['biasa'], 'ikon' => 'ti-plane-departure', 'warna' => 'primary'],
        ['label' => 'Dalam Kota ≤ 8 Jam', 'nilai' => $statistikPerjadin['dalam_kota_kurang_8_jam'], 'ikon' => 'ti-clock', 'warna' => 'success'],
        ['label' => 'Dalam Kota > 8 Jam', 'nilai' => $statistikPerjadin['dalam_kota_lebih_8_jam'], 'ikon' => 'ti-map-pin', 'warna' => 'warning'],
    ];

    // Alur SPJ berurutan (dipisah panah); "Dikembalikan" keluar dari alur, jadi
    // dipisah garis putus-putus.
    $alurSpj = [
        'Belum Diproses' => ['ikon' => 'ti-hourglass', 'warna' => 'secondary'],
        'PPK' => ['ikon' => 'ti-file-search', 'warna' => 'primary'],
        'PPSPM' => ['ikon' => 'ti-signature', 'warna' => 'info'],
        'Bendahara' => ['ikon' => 'ti-wallet', 'warna' => 'success'],
        'Dikembalikan' => ['ikon' => 'ti-arrow-back-up', 'warna' => 'danger'],
    ];
    $totalSpj = array_sum($statistikPosisiSpj);
@endphp

@push('page-css')
    <style>
        .dash {
            --biru-tua: #1B449C;
            --biru: #2563EB;
            --rgb-primary: 37, 99, 235;
            --rgb-success: 40, 199, 111;
            --rgb-warning: 255, 159, 67;
            --rgb-info: 0, 186, 209;
            --rgb-danger: 234, 84, 85;
            --rgb-secondary: 130, 134, 139;
        }
        .dash .w-primary { --w: var(--rgb-primary); }
        .dash .w-success { --w: var(--rgb-success); }
        .dash .w-warning { --w: var(--rgb-warning); }
        .dash .w-info { --w: var(--rgb-info); }
        .dash .w-danger { --w: var(--rgb-danger); }
        .dash .w-secondary { --w: var(--rgb-secondary); }

        /* ---------- Hero + CTA ---------- */
        .dash-hero {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
            padding: 2rem 2rem 1.75rem;
            margin-bottom: 1.75rem;
            color: #fff;
            background: linear-gradient(72.47deg, #1B449C 22.16%, #3D5EE1 76.47%);
            box-shadow: 0 18px 40px -20px rgba(27, 68, 156, 0.7);
        }
        .dash-hero .hiasan { position: absolute; border-radius: 50%; pointer-events: none; }
        .dash-hero .h-1 { width: 360px; height: 360px; top: -190px; right: -80px; background: rgba(255, 255, 255, 0.08); }
        .dash-hero .h-2 { width: 220px; height: 220px; bottom: -120px; left: 28%; background: rgba(255, 255, 255, 0.05); }
        .dash-hero .h-3 { width: 70px; height: 70px; top: 26px; right: 30%; border: 2px solid rgba(255, 255, 255, 0.18); }
        .dash-hero .h-titik {
            position: absolute;
            top: 22px;
            right: 22px;
            width: 130px;
            height: 80px;
            border-radius: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.3) 2px, transparent 2px);
            background-size: 16px 16px;
        }
        .dash-hero .tanggal {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.7rem;
            border-radius: 0.375rem;
            background: rgba(255, 255, 255, 0.14);
            font-size: 0.8125rem;
            margin-bottom: 0.9rem;
        }
        .dash-hero h3 { color: #fff; font-weight: 800; margin-bottom: 0.35rem; }
        .dash-hero .sub { color: rgba(255, 255, 255, 0.78); margin-bottom: 1.6rem; }
        .dash-hero .label-cta {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 0.75rem;
        }

        .kartu-cta {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            height: 100%;
            padding: 1rem 1.1rem;
            border-radius: 0.625rem;
            background: #fff;
            color: #2f3349;
            overflow: hidden;
            box-shadow: 0 10px 24px -14px rgba(10, 25, 70, 0.6);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kartu-cta::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: rgb(var(--w));
        }
        .kartu-cta:hover {
            color: #2f3349;
            transform: translateY(-4px);
            box-shadow: 0 18px 32px -14px rgba(10, 25, 70, 0.7);
        }
        .kartu-cta .ikon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            color: #fff;
            background: rgb(var(--w));
            box-shadow: 0 6px 14px -4px rgba(var(--w), 0.6);
        }
        .kartu-cta .judul { font-weight: 700; font-size: 0.975rem; line-height: 1.3; }
        .kartu-cta .ket { font-size: 0.8125rem; color: #6b7896; }
        .kartu-cta .lencana {
            display: inline-block;
            margin-top: 0.3rem;
            padding: 0.1rem 0.45rem;
            border-radius: 0.25rem;
            font-size: 0.6875rem;
            font-weight: 600;
            color: rgb(var(--w));
            background: rgba(var(--w), 0.12);
        }
        .kartu-cta .tombol {
            margin-left: auto;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.5rem 0.85rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #fff;
            background: rgb(var(--w));
            box-shadow: 0 6px 14px -6px rgba(var(--w), 0.8);
        }
        .kartu-cta .tombol i { transition: transform 0.2s ease; }
        .kartu-cta:hover .tombol i { transform: translateX(4px); }

        /* ---------- Judul bagian ---------- */
        .judul-bagian { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .judul-bagian h5 { margin: 0; font-weight: 700; }
        .judul-bagian small { color: #6b7896; }

        /* ---------- Jumlah perjalanan dinas ---------- */
        .stat-total {
            position: relative;
            overflow: hidden;
            height: 100%;
            padding: 1.35rem 1.4rem;
            border-radius: 0.625rem;
            color: #fff;
            background: linear-gradient(135deg, #2563EB 0%, #1B449C 100%);
            box-shadow: 0 12px 26px -14px rgba(27, 68, 156, 0.8);
        }
        .stat-total::after {
            content: "";
            position: absolute;
            width: 140px;
            height: 140px;
            right: -40px;
            bottom: -50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        .stat-total .ikon { font-size: 1.6rem; opacity: 0.9; }
        .stat-total .angka { font-size: 2.4rem; font-weight: 800; line-height: 1.1; margin-top: 0.6rem; color: #fff; }
        .stat-total .label { color: rgba(255, 255, 255, 0.82); font-size: 0.875rem; }

        .stat-jenis {
            height: 100%;
            padding: 1.25rem 1.3rem;
            border-radius: 0.625rem;
            background: #fff;
            box-shadow: 0 4px 18px -8px rgba(47, 51, 73, 0.18);
        }
        .stat-jenis .atas { display: flex; align-items: center; justify-content: space-between; }
        .stat-jenis .ikon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: rgb(var(--w));
            background: rgba(var(--w), 0.13);
        }
        .stat-jenis .persen { font-size: 0.8125rem; font-weight: 600; color: rgb(var(--w)); }
        .stat-jenis .angka { font-size: 1.75rem; font-weight: 800; color: #2f3349; margin-top: 0.75rem; line-height: 1.1; }
        .stat-jenis .label { font-size: 0.8125rem; color: #6b7896; }
        .stat-jenis .bar { height: 6px; border-radius: 3px; background: rgba(var(--w), 0.14); margin-top: 0.85rem; overflow: hidden; }
        .stat-jenis .bar span { display: block; height: 100%; border-radius: 3px; background: rgb(var(--w)); }

        /* ---------- Posisi SPJ (alur) ---------- */
        .kartu-alur {
            padding: 1.5rem;
            border-radius: 0.625rem;
            background: #fff;
            box-shadow: 0 4px 18px -8px rgba(47, 51, 73, 0.18);
        }
        .alur-spj { display: flex; align-items: stretch; gap: 0.5rem; }
        .alur-langkah {
            flex: 1;
            text-align: center;
            padding: 1.1rem 0.75rem;
            border-radius: 0.625rem;
            background: rgba(var(--w), 0.07);
            border: 1px solid rgba(var(--w), 0.18);
        }
        .alur-langkah .ikon {
            width: 46px;
            height: 46px;
            margin: 0 auto 0.6rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #fff;
            background: rgb(var(--w));
            box-shadow: 0 6px 14px -6px rgba(var(--w), 0.8);
        }
        .alur-langkah .angka { font-size: 1.6rem; font-weight: 800; color: #2f3349; line-height: 1.1; }
        .alur-langkah .label { font-size: 0.8125rem; color: #6b7896; font-weight: 500; }
        .alur-panah { display: flex; align-items: center; color: #b4bdd3; font-size: 1.3rem; }

        .alur-pemisah { width: 1px; margin: 0 0.5rem; border-left: 1px dashed #cfd6e6; }
        .alur-total { margin-top: 1rem; text-align: right; font-size: 0.8125rem; color: #6b7896; }

        @media (max-width: 767.98px) {
            .dash-hero { padding: 1.5rem 1.25rem; }
            .alur-spj { flex-direction: column; }
            .alur-panah { justify-content: center; transform: rotate(90deg); }
            .alur-pemisah { width: auto; height: 1px; margin: 0.5rem 0; border-left: 0; border-top: 1px dashed #cfd6e6; }
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
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-triangle me-1"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dash">
        {{-- Hero: sapaan + CTA buat perjalanan dinas --}}
        <div class="dash-hero">
            <span class="hiasan h-1"></span>
            <span class="hiasan h-2"></span>
            <span class="hiasan h-3"></span>
            <span class="hiasan h-titik d-none d-md-block"></span>

            <div class="position-relative">
                <span class="tanggal"><i class="ti ti-calendar"></i>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                <h3>Selamat datang, {{ auth()->user()->name }} 👋</h3>
                <p class="sub">Mulai susun dokumen SPJ dengan memilih jenis perjalanan dinas di bawah ini.</p>

                <div class="label-cta">Buat Perjalanan Dinas Baru</div>
                <div class="row g-3">
                    @foreach ($jenisPerjadin as $kartu)
                        <div class="col-md-4">
                            <a href="{{ route('perjalanan-dinas.create', ['jenis' => $kartu['jenis']]) }}" class="kartu-cta w-{{ $kartu['warna'] }}">
                                <span class="ikon"><i class="ti {{ $kartu['ikon'] }}"></i></span>
                                <span class="d-block">
                                    <span class="judul d-block">{!! $kartu['judul'] !!}</span>
                                    <span class="lencana">{{ $kartu['spd'] }}</span>
                                </span>
                                <span class="tombol">Buat <i class="ti ti-arrow-right"></i></span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Jumlah perjalanan dinas --}}
        <div class="judul-bagian">
            <h5>Jumlah Perjalanan Dinas</h5>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-total">
                    <i class="ti ti-briefcase ikon"></i>
                    <div class="angka">{{ $statistikPerjadin['total'] }}</div>
                    <div class="label">Total Perjalanan Dinas</div>
                </div>
            </div>
            @foreach ($kartuJumlah as $kotak)
                @php($persen = $statistikPerjadin['total'] ? round($kotak['nilai'] / $total * 100) : 0)
                <div class="col-6 col-xl-3">
                    <div class="stat-jenis w-{{ $kotak['warna'] }}">
                        <div class="atas">
                            <span class="ikon"><i class="ti {{ $kotak['ikon'] }}"></i></span>
                            <span class="persen">{{ $persen }}%</span>
                        </div>
                        <div class="angka">{{ $kotak['nilai'] }}</div>
                        <div class="label">{{ $kotak['label'] }}</div>
                        <div class="bar"><span style="width: {{ $persen }}%"></span></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Posisi SPJ (perjalanan dinas yang sudah selesai, lihat Monitoring SPJ) --}}
        <div class="judul-bagian">
            <h5>Posisi SPJ</h5>
            <a href="{{ route('monitoring-spj.index') }}" class="small fw-semibold d-inline-flex align-items-center gap-1">
                Lihat Monitoring SPJ <i class="ti ti-arrow-right ti-xs"></i>
            </a>
        </div>
        <div class="kartu-alur mb-4">
            <div class="alur-spj">
                @foreach ($alurSpj as $posisi => $gaya)
                    @if ($posisi === 'Dikembalikan')
                        <div class="alur-pemisah"></div>
                    @elseif (! $loop->first)
                        <div class="alur-panah"><i class="ti ti-chevron-right"></i></div>
                    @endif
                    <div class="alur-langkah w-{{ $gaya['warna'] }}">
                        <div class="ikon"><i class="ti {{ $gaya['ikon'] }}"></i></div>
                        <div class="angka">{{ $statistikPosisiSpj[$posisi] ?? 0 }}</div>
                        <div class="label">{{ $posisi }}</div>
                    </div>
                @endforeach
            </div>
            <div class="alur-total">Total {{ $totalSpj }} SPJ dari perjalanan dinas yang sudah selesai</div>
        </div>
    </div>
@endsection
