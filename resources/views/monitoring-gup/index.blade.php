@extends('layouts.app')

@section('title', 'Monitoring GUP')

@php
    $fmtRupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $fmtTanggal = fn ($tgl) => $tgl ? $tgl->format('d/m/Y') : '-';
    $fmtTanggalPanjang = fn ($tgl) => $tgl ? $tgl->locale('id')->translatedFormat('d F Y') : '-';
    $posisiBadge = ['PPK' => 'info', 'PPSPM' => 'primary', 'Bendahara' => 'success', 'Dikembalikan' => 'danger'];

    $totalNominal = $daftarGup->sum('nominal');
    $totalSpj = $daftarGup->sum('jumlah');
@endphp

@push('page-css')
    <style>
        .stat-gup {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            height: 100%;
            padding: 1.15rem 1.25rem;
            border-radius: 0.625rem;
            background: #fff;
            box-shadow: 0 4px 18px -8px rgba(47, 51, 73, 0.18);
        }
        .stat-gup .ikon {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
        }
        .stat-gup .angka { font-size: 1.35rem; font-weight: 800; color: #2f3349; line-height: 1.2; }
        .stat-gup .label { font-size: 0.8125rem; color: #6b7896; }
        .stat-gup.utama { color: #fff; background: linear-gradient(135deg, #2563EB 0%, #1B449C 100%); }
        .stat-gup.utama .ikon { color: #fff; background: rgba(255, 255, 255, 0.18); }
        .stat-gup.utama .angka { color: #fff; }
        .stat-gup.utama .label { color: rgba(255, 255, 255, 0.8); }

        .tanggal-gup { font-weight: 600; color: #2f3349; }
        .nominal { font-weight: 600; color: #2f3349; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .progres-bayar { min-width: 140px; }
        .progres-bayar .progress { height: 6px; }

        .menunggu-gup {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1.1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            border: 1px dashed rgba(255, 159, 67, 0.6);
            background: rgba(255, 159, 67, 0.08);
            color: #8a5a00;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Monitoring GUP</h4>
            <p class="text-muted mb-0">Rekap Ganti Uang Persediaan per tanggal GUP, dihitung dari SPJ berjenis pembayaran GUP di Monitoring SPJ.</p>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label for="pilih-tahun" class="text-muted small mb-0">Tahun</label>
            <select id="pilih-tahun" name="tahun" class="form-select" style="width: auto;" onchange="this.form.submit()">
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t }}" @selected($t === $tahun)>{{ $t }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-gup utama">
                <span class="ikon"><i class="ti ti-cash"></i></span>
                <div>
                    <div class="angka">{{ $fmtRupiah($totalNominal) }}</div>
                    <div class="label">Total Nominal GUP {{ $tahun }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-gup">
                <span class="ikon"><i class="ti ti-calendar-event"></i></span>
                <div>
                    <div class="angka">{{ $daftarGup->count() }}</div>
                    <div class="label">Kali GUP</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-gup">
                <span class="ikon"><i class="ti ti-file-check"></i></span>
                <div>
                    <div class="angka">{{ $totalSpj }}</div>
                    <div class="label">SPJ masuk GUP</div>
                </div>
            </div>
        </div>
    </div>

    @if ($menunggu->isNotEmpty())
        <div class="menunggu-gup">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-hourglass ti-md"></i>
                <span>
                    <b>{{ $menunggu->count() }} SPJ</b> berjenis pembayaran GUP belum dikelompokkan ke GUP mana pun
                    ({{ $fmtRupiah($menunggu->sum('nominal')) }}).
                </span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modal-rincian-gup"
                data-rincian="rincian-menunggu" data-judul="SPJ Menunggu Tanggal GUP">
                Lihat SPJ
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar GUP {{ $tahun }}</h5>
            <p class="text-muted small mb-0">Satu baris = satu kali GUP, yaitu kumpulan SPJ dengan Tanggal GUP yang sama.</p>
        </div>
        <div class="table-responsive">
            <table class="table" id="table-monitoring-gup" data-datatable data-kosong="Belum ada GUP pada tahun ini.">
                <thead>
                    <tr>
                        <th>GUP</th>
                        <th>Tanggal GUP</th>
                        <th>Tanggal Transfer</th>
                        <th class="text-end">Pajak</th>
                        <th>Jumlah SPJ</th>
                        <th>Nominal GUP</th>
                        <th>Status Bayar</th>
                        <th data-orderable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarGup as $gup)
                        @php
                            $persenBayar = $gup['jumlah'] ? (int) round($gup['sudah_dibayar'] / $gup['jumlah'] * 100) : 0;
                        @endphp
                        <tr>
                            <td class="fw-semibold text-nowrap" data-order="{{ sprintf('%02d-%03d', optional($gup['tanggal'])->month ?? 0, $gup['nomor']) }}">{{ $gup['label'] }}</td>
                            <td data-order="{{ optional($gup['tanggal'])->format('Y-m-d') }}">
                                <span class="tanggal-gup">{{ $fmtTanggalPanjang($gup['tanggal']) }}</span>
                            </td>
                            <td data-order="{{ optional($gup['tanggal_transfer'])->format('Y-m-d') }}">{{ $gup['tanggal_transfer'] ? $fmtTanggalPanjang($gup['tanggal_transfer']) : '-' }}</td>
                            <td class="text-end nominal" data-order="{{ (float) $gup['pajak'] }}">{{ $gup['pajak'] !== null ? $fmtRupiah($gup['pajak']) : '-' }}</td>
                            <td class="text-center">{{ $gup['jumlah'] }} SPJ</td>
                            <td class="text-end nominal" data-order="{{ $gup['nominal'] }}">{{ $fmtRupiah($gup['nominal']) }}</td>
                            <td data-order="{{ $persenBayar }}">
                                <div class="progres-bayar">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">{{ $gup['sudah_dibayar'] }}/{{ $gup['jumlah'] }} dibayar</span>
                                        <span class="fw-semibold {{ $persenBayar === 100 ? 'text-success' : 'text-primary' }}">{{ $persenBayar }}%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar {{ $persenBayar === 100 ? 'bg-success' : '' }}" style="width: {{ $persenBayar }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal" data-bs-target="#modal-rincian-gup"
                                    data-rincian="rincian-gup-{{ $gup['id'] }}"
                                    data-judul="{{ $gup['label'] }} &middot; {{ $fmtTanggalPanjang($gup['tanggal']) }}">
                                    <i class="ti ti-list-details ti-xs me-1"></i> Rincian
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Isi rincian tiap GUP (dipindah ke modal saat tombol "Rincian" diklik) --}}
    @php
        $kelompokRincian = $daftarGup->mapWithKeys(fn ($gup) => ['rincian-gup-' . $gup['id'] => $gup['spj']]);
        if ($menunggu->isNotEmpty()) {
            $kelompokRincian['rincian-menunggu'] = $menunggu;
        }
    @endphp
    @foreach ($kelompokRincian as $idRincian => $daftarSpj)
        <template id="{{ $idRincian }}">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Uraian</th>
                            <th>Penerima</th>
                            <th>Pembebanan</th>
                            <th>Nomor FP</th>
                            <th>Posisi SPJ</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftarSpj as $baris)
                            <tr>
                                <td class="text-nowrap">{{ $fmtTanggal($baris['tanggal']) }}</td>
                                <td style="min-width: 260px;">{{ $baris['uraian'] }}</td>
                                <td>{{ $baris['penerima'] }}</td>
                                <td class="text-nowrap small">{{ $baris['pembebanan'] }}</td>
                                <td class="text-nowrap">{{ $baris['nomor_fp'] ?: '-' }}</td>
                                <td>
                                    @if ($baris['spj']->posisi_spj)
                                        <span class="badge rounded-pill bg-label-{{ $posisiBadge[$baris['spj']->posisi_spj] ?? 'secondary' }}">{{ $baris['spj']->posisi_spj }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end nominal">{{ $fmtRupiah($baris['nominal']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-end">Total</th>
                            <th class="text-end nominal">{{ $fmtRupiah($daftarSpj->sum('nominal')) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </template>
    @endforeach

    <div class="modal fade" id="modal-rincian-gup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-rincian-gup-judul">Rincian GUP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-rincian-gup-isi"></div>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    <script>
        document.getElementById('modal-rincian-gup').addEventListener('show.bs.modal', function (e) {
            const tombol = e.relatedTarget;
            if (!tombol) return;
            const template = document.getElementById(tombol.dataset.rincian);
            document.getElementById('modal-rincian-gup-judul').textContent = tombol.dataset.judul;
            const isi = document.getElementById('modal-rincian-gup-isi');
            isi.innerHTML = '';
            if (template) isi.appendChild(template.content.cloneNode(true));
        });
    </script>
@endpush
