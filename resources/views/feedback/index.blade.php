@extends('layouts.app')

@section('title', 'Feedback')

@push('page-css')
    <style>
        /* Latar halaman biru muda, hanya saat form feedback pengguna tampil (admin tetap latar biasa). */
        body:has(.feedback-wadah),
        body:has(.feedback-wadah) .layout-wrapper,
        body:has(.feedback-wadah) .layout-page,
        body:has(.feedback-wadah) .content-wrapper {
            background-color: #e8f0fe !important;
        }
        .feedback-wadah {
            display: flex;
            justify-content: center;
            padding: 1.5rem 0 2rem;
        }
        .feedback-kartu {
            width: 100%;
            max-width: 460px;
            padding: 2.5rem 2.25rem 2.25rem;
            text-align: center;
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 18px 40px -18px rgba(27, 68, 156, 0.35);
        }
        .feedback-kartu h3 {
            font-weight: 800;
            color: #1B449C;
            line-height: 1.25;
            margin-bottom: 0.75rem;
        }
        .feedback-kartu .feedback-sub { color: #6b7896; margin-bottom: 1.5rem; }

        .feedback-kartu textarea,
        #modal-kirim-feedback textarea {
            background: #f3f6fd;
            border: 1px solid transparent;
            border-radius: 0.5rem;
            padding: 0.75rem 0.9rem;
            resize: vertical;
        }
        .feedback-kartu textarea:focus,
        #modal-kirim-feedback textarea:focus {
            background: #fff;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .feedback-kartu .btn-primary { padding: 0.7rem; font-weight: 600; }
    </style>
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @unless ($isAdmin)
        <div class="feedback-wadah">
            <form class="feedback-kartu" action="{{ route('feedback.store') }}" method="POST">
                @csrf
                <h3>FEEDBACK SIMPEL</h3>
                <p class="feedback-sub">
                    Kami terus berupaya meningkatkan SIMPEL. Sampaikan kendala, saran, atau
                    tanggapan Anda terkait sistem.
                </p>

                <textarea name="isi" class="form-control mb-3" rows="5" maxlength="5000" required
                    placeholder="Apa yang bisa kami perbaiki? Ceritakan kendala atau saran Anda...">{{ old('isi') }}</textarea>

                <button type="submit" class="btn btn-primary w-100">Kirim Feedback</button>
            </form>
        </div>
    @else
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Feedback</h4>
        <p class="text-muted mb-0">
            Rekap kendala, saran, dan pertanyaan dari seluruh pengguna. Isi tindak lanjut dan tandai jika sudah selesai.
        </p>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-4 mb-4">
        @foreach ([
            ['label' => 'Total Feedback', 'nilai' => $ringkasan['total'], 'ikon' => 'ti-message-report', 'warna' => 'primary'],
            ['label' => 'Belum Selesai', 'nilai' => $ringkasan['belum'], 'ikon' => 'ti-clock', 'warna' => 'warning'],
            ['label' => 'Selesai', 'nilai' => $ringkasan['selesai'], 'ikon' => 'ti-circle-check', 'warna' => 'success'],
        ] as $kotak)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="avatar">
                            <span class="avatar-initial rounded-circle bg-label-{{ $kotak['warna'] }}">
                                <i class="ti {{ $kotak['ikon'] }} ti-md"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $kotak['nilai'] }}</h5>
                            <small class="text-muted">{{ $kotak['label'] }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0">Rekap Feedback</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-kirim-feedback">
                <i class="ti ti-send ti-sm me-1"></i> Kirim Feedback
            </button>
            {{-- Dipindah ke sebelah tombol "Atur Kolom" oleh atur-kolom.js (data-filter-tabel). --}}
            <div data-filter-tabel="table-feedback">
                <select id="filter-status-feedback" title="Filter status">
                    <option value="">Semua Status</option>
                    <option value="Belum Selesai">Belum Selesai</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table" id="table-feedback" data-datatable data-kosong="Belum ada feedback.">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pengirim</th>
                        <th>Uraian</th>
                        <th>Status</th>
                        <th>Tindak Lanjut</th>
                        <th data-orderable="false">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarFeedback as $item)
                        <tr>
                            <td class="text-nowrap" data-order="{{ $item->created_at->format('Y-m-d H:i:s') }}">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $item->pengirim->name ?? '(akun dihapus)' }}</td>
                            <td style="min-width: 280px;">
                                @if ($item->judul)
                                    <div class="fw-semibold">{{ $item->judul }}</div>
                                @endif
                                <div style="white-space: pre-line;">{{ $item->isi }}</div>
                            </td>
                            <td class="text-nowrap" data-search="{{ $item->selesai ? 'Selesai' : 'Belum Selesai' }}">
                                @if ($item->selesai)
                                    <span class="badge rounded-pill bg-label-success"><i class="ti ti-circle-check ti-xs me-1"></i>Selesai</span>
                                @else
                                    <span class="badge rounded-pill bg-label-warning"><i class="ti ti-clock ti-xs me-1"></i>Belum Selesai</span>
                                @endif
                            </td>
                            <td style="min-width: 240px;">
                                @if ($item->tindak_lanjut)
                                    <div style="white-space: pre-line;">{{ $item->tindak_lanjut }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                                @if ($item->ditindaklanjuti_pada)
                                    <div class="text-muted small mt-1">
                                        {{ $item->penindakLanjut->name ?? 'Administrator' }}, {{ $item->ditindaklanjuti_pada->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @php
                                    $payload = [
                                        'id' => $item->id_feedback,
                                        'judul' => $item->judul ?: \Illuminate\Support\Str::limit($item->isi, 60),
                                        'pengirim' => $item->pengirim->name ?? '(akun dihapus)',
                                        'tindak_lanjut' => $item->tindak_lanjut,
                                        'selesai' => $item->selesai,
                                    ];
                                @endphp
                                <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Tindak Lanjut"
                                    data-bs-toggle="modal" data-bs-target="#modal-tindak-lanjut"
                                    data-feedback='@json($payload)' onclick="bukaModalTindakLanjut(this)">
                                    <i class="ti ti-edit ti-sm"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Kirim Feedback (pengguna biasa memakai form di atas) --}}
    <div class="modal fade" id="modal-kirim-feedback" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('feedback.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Kirim Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea name="isi" class="form-control" rows="5" maxlength="5000" required
                            placeholder="Apa yang bisa kami perbaiki? Ceritakan kendala atau saran Anda...">{{ old('isi') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-send ti-sm me-1"></i> Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        {{-- Modal Tindak Lanjut (administrator) --}}
        <div class="modal fade" id="modal-tindak-lanjut" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="form-tindak-lanjut" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title mb-0">Tindak Lanjut Feedback</h5>
                                <div class="text-muted small" id="tindak-lanjut-info"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Tindak Lanjut / Solusi</label>
                                <textarea name="tindak_lanjut" id="input-tindak-lanjut" class="form-control" rows="5" maxlength="5000"
                                    placeholder="Tuliskan solusi atau langkah yang sudah dilakukan."></textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label d-block">Status</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="selesai" id="status-belum" value="0">
                                    <label class="form-check-label" for="status-belum">Belum Selesai</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="selesai" id="status-selesai" value="1">
                                    <label class="form-check-label" for="status-selesai">Selesai</label>
                                </div>
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
    @endunless
@endsection

@push('page-js')
    <script>
        @if ($isAdmin)
            const formTindakLanjut = document.getElementById('form-tindak-lanjut');
            const tindakLanjutUrlTemplate = '{{ route('feedback.tindak-lanjut', ['feedback' => '__ID__']) }}';

            function bukaModalTindakLanjut(tombol) {
                const data = JSON.parse(tombol.dataset.feedback);
                formTindakLanjut.action = tindakLanjutUrlTemplate.replace('__ID__', data.id);
                document.getElementById('tindak-lanjut-info').textContent = data.pengirim + ' — ' + data.judul;
                document.getElementById('input-tindak-lanjut').value = data.tindak_lanjut || '';
                document.getElementById(data.selesai ? 'status-selesai' : 'status-belum').checked = true;
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('filter-status-feedback').addEventListener('change', function () {
                    // Kolom Status (index 3). Pencocokan persis supaya "Selesai" tidak ikut menangkap "Belum Selesai".
                    const nilai = this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '';
                    $('#table-feedback').DataTable().column(3).search(nilai, true, false).draw();
                });

                @if ($errors->any())
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-kirim-feedback')).show();
                @endif
            });
        @endif
    </script>
@endpush
