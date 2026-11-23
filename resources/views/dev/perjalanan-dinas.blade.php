@extends('layouts.app')

@section('title', '(dev) Semua Perjalanan Dinas')

@section('content')
    <style>
        .badge-jenis-biasa { background-color: #e0f5f3; color: #0f8f84; }
        .badge-jenis-kurang { background-color: #efe9fb; color: #6f42c1; }
        .badge-jenis-lebih { background-color: #fde8f1; color: #c2185b; }
        .badge-jenis-lain { background-color: #eceef1; color: #6f6b7d; }
        .table .dropdown-menu { z-index: 1060; }
    </style>

    <div class="mb-4">
        <h4 class="fw-bold mb-1">(dev) Semua Perjalanan Dinas</h4>
        <p class="text-muted mb-0">Halaman uji coba khusus administrator, tidak tampil untuk pengguna lain. Saat ini untuk mencoba export dokumen ke Word (.docx).</p>
    </div>

    <div class="alert alert-warning d-flex gap-2" role="alert">
        <i class="ti ti-flask ti-sm mt-1"></i>
        <div>Fitur Word masih tahap uji coba: tata letaknya disusun mengikuti tampilan PDF, mungkin masih ada yang belum rapi. Catat bagian yang perlu diperbaiki.</div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table" data-datatable data-kosong="Belum ada perjalanan dinas.">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Surat Tugas</th>
                        <th>Pelaksana</th>
                        <th>Perihal</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Word</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarPerjalananDinas as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-nowrap">{{ $item->no_surat_tugas ?: '-' }}</td>
                            <td>{{ $item->pelaksana->nama ?? '-' }}</td>
                            <td style="min-width: 280px;">{{ $item->perihal ?: '-' }}</td>
                            <td class="text-nowrap">{{ optional($item->tanggal_mulai)->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                <span class="badge rounded-pill badge-jenis-{{ ['biasa' => 'biasa', 'dalam_kota_kurang_8_jam' => 'kurang', 'dalam_kota_lebih_8_jam' => 'lebih'][$item->jenis_perjadin] ?? 'lain' }}">
                                    {{ ['biasa' => 'Biasa', 'dalam_kota_kurang_8_jam' => 'Dalam Kota < 8 Jam', 'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam'][$item->jenis_perjadin] ?? $item->jenis_perjadin }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-label-{{ $item->status_draft === 'selesai' ? 'success' : 'warning' }}">{{ $item->status_draft === 'selesai' ? 'Selesai' : 'Draft' }}</span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle text-nowrap" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                        <i class="ti ti-file-word ti-xs me-1"></i> Word
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item fw-semibold" href="{{ route('dev.perjalanan-dinas.word', [$item, 'semua']) }}">
                                                <i class="ti ti-files ti-xs me-1"></i> Gabung jadi 1 file <small class="text-muted">(tanpa Surat Tugas &amp; SPD)</small>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @foreach (\App\Services\DokumenWord::jenisTersedia($item) as $kunci => $label)
                                            <li><a class="dropdown-item" href="{{ route('dev.perjalanan-dinas.word', [$item, $kunci]) }}">{{ $label }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
