@extends('layouts.app')

@section('title', 'Monitoring SPJ')

@push('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.css') }}" />
    <style>
        #table-monitoring-spj_wrapper .dataTables_filter input {
            min-width: 220px;
        }

        #table-monitoring-spj thead th,
        #table-monitoring-spj_wrapper .dataTables_scrollHead thead th {
            text-transform: uppercase;
            font-size: var(--bs-body-font-size);
            letter-spacing: 0.4px;
            color: #fff;
            background-color: #1B449C;
            border-bottom: 2px solid #1B449C;
            white-space: nowrap;
            text-align: center;
            vertical-align: middle;
        }

        #table-monitoring-spj tbody tr:nth-child(even) {
            background-color: #f8f7fc;
        }

        #table-monitoring-spj tbody tr:hover {
            background-color: #eef0fb;
        }

        /* Teks isi tabel dibuat gelap (bawaan tema abu-abu, kurang terbaca). Badge tetap
           pakai warnanya sendiri karena kelas bg-label-* menimpa warna teks. */
        #table-monitoring-spj td {
            vertical-align: middle;
            color: #212529;
        }

        #table-monitoring-spj td.kolom-uraian {
            min-width: 350px;
        }

        #table-monitoring-spj .rekap-nomor {
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.8rem;
        }

        /* Modal scrollable: <form> membungkus header/body/footer, jadi form-nya yang
           dijadikan flex column supaya .modal-body bisa scroll dan footer tetap kelihatan. */
        .judul-isian-spj {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #1B449C;
            padding-bottom: 0.5rem;
            margin-bottom: 0.9rem;
            border-bottom: 1px solid #eef1f7;
        }

        .kotak-kelengkapan-spj {
            padding: 0.85rem 1rem;
            border-radius: 0.5rem;
            background: #f6f8fd;
            border: 1px solid #e6ebf6;
        }

        .modal-dialog-scrollable .modal-content > form {
            display: flex;
            flex-direction: column;
            max-height: 100%;
            overflow: hidden;
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
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Monitoring SPJ</h4>
        {{-- <p class="text-muted mb-0">
            Pemantauan pertanggungjawaban perjalanan dinas yang sudah selesai. Pembebanan, tanggal, uraian, nominal, dan nama penerima
            diambil otomatis dari data perjalanan dinas; kolom lainnya diisi oleh PPK / PPSPM / Bendahara.
        </p> --}}
    </div>

    @php
        $fmtTanggal = fn ($tgl) => $tgl ? $tgl->format('d/m/Y') : '';
        // Badge status Ya/Tidak; teksnya sama dengan pilihan di modal dan ikut ter-export ke Excel.
        $badgeStatus = fn ($nilai, $labelSudah, $labelBelum) => $nilai
            ? '<span class="badge rounded-pill bg-label-success"><i class="ti ti-circle-check ti-xs me-1"></i>' . $labelSudah . '</span>'
            : '<span class="badge rounded-pill bg-label-secondary"><i class="ti ti-clock ti-xs me-1"></i>' . $labelBelum . '</span>';
        $caraBayarBadge = ['tunai' => 'warning', 'transfer' => 'info'];
        $posisiBadge = ['PPK' => 'info', 'PPSPM' => 'primary', 'Bendahara' => 'success', 'Dikembalikan' => 'danger'];
    @endphp

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0"><i class="ti ti-clipboard-check me-1"></i> Daftar SPJ Perjalanan Dinas</h5>
            <div class="d-flex gap-2 flex-wrap">
                {{-- Dipindah ke sebelah tombol "Kolom" oleh atur-kolom.js (data-filter-tabel). --}}
                <div data-filter-tabel="table-monitoring-spj">
                    <select id="filter-posisi-spj" class="form-select" title="Filter posisi SPJ">
                        <option value="">Semua Posisi SPJ</option>
                        @foreach ($posisiSpj as $posisi)
                            <option value="{{ $posisi }}">{{ $posisi }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-filter-tabel="table-monitoring-spj">
                    <select id="filter-jenis-pembayaran" class="form-select" title="Filter jenis pembayaran">
                        <option value="">Semua Jenis Pembayaran</option>
                        @foreach ($jenisPembayaran as $jenis)
                            <option value="{{ $jenis }}">{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="monitoring-spj-export"></div>
            </div>
        </div>
        {{-- Bar aksi massal: tampil hanya saat ada baris dicentang, dipindah ke atas header tabel oleh JS di bawah --}}
        <div class="bulk-bar d-none" id="bulk-bar-spj">
            <span class="bulk-bar-info"><i class="ti ti-checks me-1"></i><span id="jumlah-terpilih">0</span> baris dipilih</span>
            <div class="d-flex gap-2 flex-wrap ms-2">
                <button type="button" class="btn btn-sm btn-warning" id="btn-bulk-edit" data-bs-toggle="modal" data-bs-target="#modal-bulk-spj">
                    <i class="ti ti-edit me-1"></i> Edit Terpilih
                </button>
                <button type="button" class="btn btn-sm btn-label-secondary" id="btn-batal-pilih-spj">
                    <i class="ti ti-x me-1"></i> Batal Pilih
                </button>
            </div>
        </div>
        <div class="card-datatable pt-0">
            <table class="table" id="table-monitoring-spj">
                <thead>
                    <tr>
                        <th class="text-center">
                            @if ($bisaIsi)
                                <input type="checkbox" class="form-check-input cek-semua-spj" title="Pilih semua baris yang tampil sesuai filter/pencarian">
                            @endif
                        </th>
                        <th>No</th>
                        <th>Posisi SPJ</th>
                        <th>Tanggal Diperiksa PPK</th>
                        <th>Nomor SPP/SPM</th>
                        <th>Tanggal SPP</th>
                        <th>Tanggal SPM</th>
                        <th>Nomor SP2D</th>
                        <th>Tanggal SP2D</th>
                        <th>Nomor FP</th>
                        <th>Pembebanan</th>
                        <th>Tanggal</th>
                        <th>Uraian</th>
                        <th>Nominal (Rp)</th>
                        <th>Nama Penerima</th>
                        <th>Keterangan</th>
                        <th>Status Pembayaran</th>
                        <th>Tunai/Transfer</th>
                        <th>Status SPBy</th>
                        <th>Status Kuitansi</th>
                        <th>Status DRPP</th>
                        <th>Jenis Pembayaran</th>
                        <th>Tanggal GUP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarPerjalananDinas as $item)
                        @php
                            $spj = $item->monitoringSpj;
                            $tanggalKuitansi = optional($item->kuitansi)->tanggal_kuitansi;
                        @endphp
                        <tr>
                            <td class="text-center">
                                @if ($bisaIsi)
                                    <input type="checkbox" class="form-check-input cek-baris-spj" value="{{ $item->id_perjalanan_dinas }}">
                                @endif
                            </td>
                            <td class="rekap-nomor">{{ $item->id_perjalanan_dinas }}</td>
                            <td>
                                @if ($spj?->posisi_spj)
                                    <span class="badge rounded-pill bg-label-{{ $posisiBadge[$spj->posisi_spj] ?? 'secondary' }}">{{ $spj->posisi_spj }}</span>
                                @endif
                            </td>
                            <td data-order="{{ optional($spj?->tanggal_diperiksa_ppk)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_diperiksa_ppk) }}</td>
                            <td class="rekap-nomor">{{ $spj?->no_spp_spm }}</td>
                            <td data-order="{{ optional($spj?->tanggal_spp)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_spp) }}</td>
                            <td data-order="{{ optional($spj?->tanggal_spm)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_spm) }}</td>
                            <td class="rekap-nomor">{{ $spj?->no_sp2d }}</td>
                            <td data-order="{{ optional($spj?->tanggal_sp2d)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_sp2d) }}</td>
                            {{-- Nomor FP diisi di Data Umum perjalanan dinas (no_form_permintaan). --}}
                            <td class="rekap-nomor">{{ $item->no_form_permintaan }}</td>
                            <td class="text-nowrap">{{ $item->pembebanan }}</td>
                            <td data-order="{{ optional($tanggalKuitansi)->format('Y-m-d') }}">{{ $fmtTanggal($tanggalKuitansi) }}</td>
                            <td class="kolom-uraian">{{ $item->perihal }}</td>
                            <td class="text-end text-nowrap" data-order="{{ $item->total_biaya }}">{{ number_format($item->total_biaya, 0, ',', '.') }}</td>
                            <td>{{ $item->pelaksana->nama ?? '-' }}</td>
                            <td>{{ $spj?->keterangan }}</td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_dibayar, 'Sudah Dibayar', 'Belum Dibayar') !!}</td>
                            <td>
                                @if ($spj?->cara_bayar)
                                    <span class="badge rounded-pill bg-label-{{ $caraBayarBadge[$spj->cara_bayar] ?? 'secondary' }}">{{ $caraBayar[$spj->cara_bayar] ?? $spj->cara_bayar }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_spby, 'Sudah Diterbitkan', 'Belum Diterbitkan') !!}</td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_kuitansi, 'Sudah Dibuat', 'Belum Dibuat') !!}</td>
                            <td class="text-nowrap">{!! $badgeStatus($spj?->sudah_drpp, 'Sudah Dibuat', 'Belum Dibuat') !!}</td>
                            <td>{{ $spj?->jenis_pembayaran }}</td>
                            <td data-order="{{ optional($spj?->tanggal_gup)->format('Y-m-d') }}">{{ $fmtTanggal($spj?->tanggal_gup) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('perjalanan-dinas.show', $item) }}" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Lihat Perjalanan Dinas">
                                    <i class="ti ti-eye ti-sm"></i>
                                </a>
                                @if ($bisaIsi)
                                    @php
                                        $payload = [
                                            'id' => $item->id_perjalanan_dinas,
                                            'penerima' => $item->pelaksana->nama ?? '-',
                                            'uraian' => $item->perihal,
                                            'tanggal_diperiksa_ppk' => optional($spj?->tanggal_diperiksa_ppk)->format('Y-m-d'),
                                            'no_spp_spm' => $spj?->no_spp_spm,
                                            'tanggal_spp' => optional($spj?->tanggal_spp)->format('Y-m-d'),
                                            'tanggal_spm' => optional($spj?->tanggal_spm)->format('Y-m-d'),
                                            'no_sp2d' => $spj?->no_sp2d,
                                            'tanggal_sp2d' => optional($spj?->tanggal_sp2d)->format('Y-m-d'),
                                            'keterangan' => $spj?->keterangan,
                                            'posisi_spj' => $spj?->posisi_spj,
                                            'sudah_dibayar' => (bool) $spj?->sudah_dibayar,
                                            'cara_bayar' => $spj?->cara_bayar,
                                            'sudah_spby' => (bool) $spj?->sudah_spby,
                                            'sudah_kuitansi' => (bool) $spj?->sudah_kuitansi,
                                            'sudah_drpp' => (bool) $spj?->sudah_drpp,
                                            'jenis_pembayaran' => $spj?->jenis_pembayaran,
                                            'tanggal_gup' => optional($spj?->tanggal_gup)->format('Y-m-d'),
                                        ];
                                    @endphp
                                    <button type="button" class="btn btn-icon btn-sm btn-text-warning rounded-pill" title="Isi Monitoring SPJ"
                                        data-bs-toggle="modal" data-bs-target="#modal-edit-spj"
                                        data-spj='@json($payload)' onclick="bukaModalSpj(this)">
                                        <i class="ti ti-edit ti-sm"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal isi kolom manual Monitoring SPJ (PPK / PPSPM / Bendahara) --}}
    <div class="modal fade" id="modal-edit-spj" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="form-edit-spj" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Edit Monitoring SPJ</h5>
                            <div class="text-muted small" id="spj-info"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('monitoring-spj._form-isian', ['bulk' => false, 'prefix' => 'spj'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal bulk edit: nilai yang diisi diterapkan ke semua baris terpilih --}}
    <div class="modal fade" id="modal-bulk-spj" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="form-bulk-spj" action="{{ route('monitoring-spj.bulk-update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div id="bulk-ids"></div>
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Edit <span id="bulk-jumlah">0</span> Baris Sekaligus</h5>
                            <div class="text-muted small">Hanya isian yang diisi yang diterapkan. Isian kosong, "Tetap", atau centang bertanda &ndash; tidak diubah.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('monitoring-spj._form-isian', ['bulk' => true, 'prefix' => 'bulk'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan ke Baris Terpilih</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-buttons/datatables-buttons.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jszip/jszip.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons/buttons.html5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-fixedcolumns/datatables.fixedcolumns.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.js') }}"></script>

    <script>
        const formEditSpj = document.getElementById('form-edit-spj');
        const updateSpjUrlTemplate = '{{ route('monitoring-spj.update', ['perjalananDinas' => '__ID__']) }}';

        function bukaModalSpj(tombol) {
            const data = JSON.parse(tombol.dataset.spj);
            formEditSpj.action = updateSpjUrlTemplate.replace('__ID__', data.id);
            document.getElementById('spj-info').textContent = data.penerima + ' — ' + (data.uraian || '');

            formEditSpj.querySelectorAll('input[name], select[name]').forEach(function (el) {
                if (!(el.name in data)) return;
                if (el.type === 'checkbox') {
                    el.checked = !!data[el.name];
                } else {
                    el.value = data[el.name] ?? '';
                }
            });
            kelengkapanEdit.sinkron();
        }

        // Kelengkapan (Pembayaran/SPBy/Kuitansi/DRPP) + "Centang Semua", sama untuk modal Edit
        // dan Edit Terpilih. Tunai/Transfer baru bisa diisi kalau Pembayaran dicentang; kalau
        // centangnya dilepas, isiannya dikosongkan (server juga mengosongkannya).
        // Di Edit Terpilih centangnya 3 keadaan: – (indeterminate) = tidak diubah; begitu
        // diklik, nilainya (1/0) dikirim lewat input tersembunyi di sebelahnya.
        // SPJ yang Posisi-nya "Dikembalikan" tidak bisa dibayar: centang Pembayaran
        // dilepas & dikunci (server menerapkan aturan yang sama).
        const POSISI_DIKEMBALIKAN = @json(\App\Models\MonitoringSpj::POSISI_DIKEMBALIKAN);

        function pasangKelengkapan(form, bulk) {
            const cekStatus = Array.from(form.querySelectorAll('.cek-status-spj'));
            const cekSemua = form.querySelector('.cek-centang-semua');
            const cekPembayaran = form.querySelector('.cek-status-spj[data-field="sudah_dibayar"]');
            const selectCaraBayar = form.querySelector('select[name="cara_bayar"]');
            const selectPosisi = form.querySelector('select[name="posisi_spj"]');
            let pembayaranDikunci = false;

            function tandaiDiubah(c) {
                c.indeterminate = false;
                if (bulk) {
                    const tersembunyi = c.nextElementSibling;
                    tersembunyi.value = c.checked ? '1' : '0';
                    tersembunyi.disabled = false;
                }
            }

            function sinkron() {
                const dikembalikan = selectPosisi.value === POSISI_DIKEMBALIKAN;
                if (dikembalikan) {
                    cekPembayaran.checked = false;
                    tandaiDiubah(cekPembayaran);
                    cekPembayaran.disabled = true;
                    pembayaranDikunci = true;
                } else if (pembayaranDikunci) {
                    // Posisi diganti dari "Dikembalikan": buka lagi; di Edit Terpilih kembali "tidak diubah".
                    cekPembayaran.disabled = false;
                    pembayaranDikunci = false;
                    if (bulk) {
                        cekPembayaran.indeterminate = true;
                        cekPembayaran.nextElementSibling.value = '';
                        cekPembayaran.nextElementSibling.disabled = true;
                    }
                }
                cekPembayaran.title = dikembalikan ? 'SPJ yang dikembalikan ke pembuat belum bisa dibayar' : '';

                const aktif = cekStatus.filter(function (c) { return !c.disabled; });
                cekSemua.checked = aktif.every(function (c) { return c.checked && !c.indeterminate; });
                const sudahBayar = cekPembayaran.checked && !cekPembayaran.indeterminate;
                selectCaraBayar.disabled = !sudahBayar;
                if (!sudahBayar) selectCaraBayar.value = '';
            }

            cekStatus.forEach(function (c) {
                c.addEventListener('change', function () { tandaiDiubah(c); sinkron(); });
            });
            cekSemua.addEventListener('change', function () {
                cekStatus.forEach(function (c) {
                    if (c.disabled) return;
                    c.checked = cekSemua.checked;
                    tandaiDiubah(c);
                });
                sinkron();
            });
            selectPosisi.addEventListener('change', sinkron);

            function reset() {
                cekPembayaran.disabled = false;
                pembayaranDikunci = false;
                cekStatus.forEach(function (c) {
                    c.checked = false;
                    c.indeterminate = bulk;
                    if (bulk) {
                        c.nextElementSibling.value = '';
                        c.nextElementSibling.disabled = true;
                    }
                });
                sinkron();
            }

            return { sinkron: sinkron, reset: reset };
        }

        const kelengkapanEdit = pasangKelengkapan(formEditSpj, false);
        const formBulkSpj = document.getElementById('form-bulk-spj');
        const kelengkapanBulk = pasangKelengkapan(formBulkSpj, true);

        document.addEventListener('DOMContentLoaded', function () {
            const tabelSpj = $('#table-monitoring-spj').DataTable({
                scrollX: true,
                autoWidth: false,
                fixedColumns: {
                    rightColumns: 1,
                },
                // Kolom 1 ("No" = id_perjalanan_dinas) descending = terbaru di atas.
                // Kolom 0 = checkbox pilih baris.
                order: [[1, 'desc']],
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, -1] },
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Cari data...',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                    emptyTable: 'Belum ada perjalanan dinas berstatus selesai.',
                },
                dom: '<"row mx-1 mt-3 align-items-center"' +
                    '<"col-md-6"<"me-3"l>>' +
                    '<"col-md-6 d-flex justify-content-md-end"f>' +
                    '>' +
                    't' +
                    '<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 25,
                lengthMenu: [10, 25, 50, 100],
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="ti ti-file-spreadsheet me-1"></i> Export Excel',
                        className: 'btn btn-label-primary',
                        title: 'Monitoring SPJ',
                        exportOptions: { columns: ':not(:first-child):not(:last-child)' },
                    },
                ],
                initComplete: function () {
                    window.SimpelAturKolom.pasang(this.api());
                    this.api().buttons().container().appendTo('#monitoring-spj-export');
                },
            });

            // Index kolom sesuai urutan header: 2 = Posisi SPJ, 21 = Jenis Pembayaran.
            document.getElementById('filter-posisi-spj').addEventListener('change', function () {
                tabelSpj.column(2).search(this.value, false, false).draw();
            });
            document.getElementById('filter-jenis-pembayaran').addEventListener('change', function () {
                tabelSpj.column(21).search(this.value, false, false).draw();
            });

            // ----- Bulk edit. Semua baris tetap ada di DOM DataTables (client-side), jadi
            // centang di halaman lain ikut terhitung. "Pilih semua" = semua baris yang lolos
            // filter/pencarian saat ini, lintas halaman. Pakai delegasi di document karena
            // scrollX bikin DataTables mengkloning header (checkbox-nya bukan elemen asli). -----
            const barBulkSpj = document.getElementById('bulk-bar-spj');
            // Taruh bar tepat di atas header tabel (sebelum area scroll DataTables).
            const scrollSpj = document.querySelector('#table-monitoring-spj_wrapper .dataTables_scroll');
            if (scrollSpj) scrollSpj.parentNode.insertBefore(barBulkSpj, scrollSpj);

            function cekbokTerpilih() {
                return $(tabelSpj.rows().nodes()).find('.cek-baris-spj:checked').toArray();
            }

            function cekbokTersaring() {
                return $(tabelSpj.rows({ search: 'applied' }).nodes()).find('.cek-baris-spj').toArray();
            }

            function perbaruiStatusPilihan() {
                const jumlah = cekbokTerpilih().length;
                document.getElementById('jumlah-terpilih').textContent = jumlah;
                barBulkSpj.classList.toggle('d-none', jumlah === 0);

                const tersaring = cekbokTersaring();
                const semuaTercentang = tersaring.length > 0 && tersaring.every(function (c) { return c.checked; });
                $(".cek-semua-spj").prop('checked', semuaTercentang);
            }

            document.getElementById('btn-batal-pilih-spj').addEventListener('click', function () {
                cekbokTerpilih().forEach(function (c) { c.checked = false; });
                perbaruiStatusPilihan();
            });

            $(document).on("change", ".cek-semua-spj", function () {
                const centang = this.checked;
                cekbokTersaring().forEach(function (c) { c.checked = centang; });
                perbaruiStatusPilihan();
            });
            $(document).on('change', '.cek-baris-spj', perbaruiStatusPilihan);
            tabelSpj.on('draw', perbaruiStatusPilihan);

            document.getElementById('modal-bulk-spj').addEventListener('show.bs.modal', function () {
                const terpilih = cekbokTerpilih();
                document.getElementById('bulk-jumlah').textContent = terpilih.length;
                document.getElementById('bulk-ids').innerHTML = terpilih.map(function (c) {
                    return '<input type="hidden" name="ids[]" value="' + parseInt(c.value, 10) + '">';
                }).join('');
                formBulkSpj.querySelectorAll('input:not([type=hidden]):not([type=checkbox]), select').forEach(function (el) {
                    el.value = '';
                });
                kelengkapanBulk.reset();
            });
        });
    </script>
@endpush
