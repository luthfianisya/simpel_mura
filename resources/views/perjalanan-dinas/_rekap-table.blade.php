{{-- Partial tabel rekap + modal Lihat/Copy/Export, dipakai oleh dashboard, "Semua Perjalanan Dinas", dan "Perjalanan Dinas Saya" --}}
@push('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/dokumen-perjadin.css') }}" />
    <style>
        #table-rekap-perjadin_wrapper .dataTables_filter input {
            min-width: 220px;
        }

        #table-rekap-perjadin_wrapper .dt-action-buttons {
            margin-bottom: 0.75rem;
        }

        #table-rekap-perjadin td:nth-child(5),
        #table-rekap-perjadin th:nth-child(5) {
            min-width: 350px;
        }
    </style>
@endpush

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="ti ti-table me-1"></i> {{ $cardTitle ?? 'Rekap Perjalanan Dinas' }}</h5>
        <div id="rekap-perjadin-export"></div>
    </div>
    <div class="card-datatable pt-0">
        <table class="table" id="table-rekap-perjadin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pemohon</th>
                    <th>Tanggal Permohonan</th>
                    <th>Nama</th>
                    <th>Tugas</th>
                    <th>Daerah Asal</th>
                    <th>Daerah Tujuan</th>
                    <th>Angkutan</th>
                    <th>Tanggal Pelaksanaan</th>
                    <th>MAK</th>
                    <th>No Surat Tugas</th>
                    <th>No SPD</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($daftarPerjalananDinas as $item)
                    <tr>
                        <td>{{ $item->id_perjalanan_dinas }}</td>
                        <td>{{ $item->pemohon->nama ?? '-' }}</td>
                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                        <td>{{ $item->pelaksana->nama ?? '-' }}</td>
                        <td>{{ $item->perihal }}</td>
                        <td>{{ $item->desa_asal }}, {{ $item->kabupaten_asal }}</td>
                        <td>{{ $item->desa_tujuan }}, {{ $item->kabupaten_tujuan }}</td>
                        <td>{{ $item->angkutan ?? '-' }}</td>
                        <td>
                            {{ $item->tanggal_mulai->format('d/m/Y') }}
                            @if ($item->tanggal_mulai->ne($item->tanggal_selesai))
                                s.d. {{ $item->tanggal_selesai->format('d/m/Y') }}
                            @endif
                        </td>
                        <td>{{ $item->pembebanan }}</td>
                        <td>{{ $item->no_surat_tugas }}</td>
                        <td>{{ $item->no_spd ?? '-' }}</td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Lihat" onclick="openDokumenModal({{ $item->id_perjalanan_dinas }})">
                                <i class="ti ti-eye ti-sm"></i>
                            </button>
                            <a href="{{ route('perjalanan-dinas.create', ['jenis' => $item->jenis_perjadin, 'from' => $item->id_perjalanan_dinas]) }}"
                                class="btn btn-icon btn-sm btn-text-secondary rounded-pill" title="Copy jadi draft baru">
                                <i class="ti ti-copy ti-sm"></i>
                            </a>
                            <button type="button" class="btn btn-icon btn-sm btn-text-success rounded-pill" title="Export" onclick="openDokumenModal({{ $item->id_perjalanan_dinas }})">
                                <i class="ti ti-download ti-sm"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Lihat/Export dokumen perjalanan dinas --}}
<div class="modal fade" id="modal-dokumen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="modal-dokumen-title">Dokumen Perjalanan Dinas</h5>
                <div class="dropdown ms-auto me-2">
                    <button class="btn btn-sm btn-label-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" id="btn-export-word"><i class="ti ti-file-type-doc me-1"></i> Word (.doc)</a></li>
                        <li><a class="dropdown-item" href="#" id="btn-export-excel"><i class="ti ti-file-type-xls me-1"></i> Excel (.xls)</a></li>
                        <li><a class="dropdown-item" href="#" id="btn-export-pdf"><i class="ti ti-file-type-pdf me-1"></i> PDF (Print)</a></li>
                    </ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills mb-3" id="dokumen-tabs" role="tablist"></ul>
                <div id="dokumen-tab-content"></div>
            </div>
        </div>
    </div>
</div>

@push('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons/datatables-buttons.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jszip/jszip.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pdfmake/pdfmake.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons/buttons.html5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons/buttons.print.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-fixedcolumns/datatables.fixedcolumns.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/js/dokumen-perjadin.js') }}"></script>

    <script>
        window.dokumenDataByRow = @json($dokumenDataById);

        document.addEventListener('DOMContentLoaded', function () {
            $('#table-rekap-perjadin').DataTable({
                scrollX: true,
                autoWidth: false,
                fixedColumns: {
                    rightColumns: 1,
                },
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { width: '350px', targets: 4 },
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Cari data...',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                    emptyTable: 'Belum ada data perjalanan dinas.',
                },
                dom: '<"row mx-1 mt-3 align-items-center"' +
                    '<"col-md-6"<"me-3"l>>' +
                    '<"col-md-6 d-flex justify-content-md-end"f>' +
                    '>' +
                    't' +
                    '<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 10,
                lengthMenu: [10, 25, 50, 75, 100],
                buttons: [
                    {
                        extend: 'collection',
                        className: 'btn btn-label-primary dropdown-toggle me-2',
                        text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                        buttons: [
                            {
                                extend: 'print',
                                text: '<i class="ti ti-printer me-1"></i>Print',
                                className: 'dropdown-item',
                                customize: function (win) {
                                    $(win.document.body)
                                        .css('color', '#697a8d')
                                        .css('border-color', '#dbdade')
                                        .css('background-color', '#fff');
                                    $(win.document.body)
                                        .find('table')
                                        .addClass('compact')
                                        .css('color', 'inherit')
                                        .css('border-color', 'inherit')
                                        .css('background-color', 'inherit');
                                },
                            },
                            {
                                extend: 'csv',
                                text: '<i class="ti ti-file-text me-1"></i>Csv',
                                className: 'dropdown-item',
                            },
                            {
                                extend: 'excel',
                                text: 'Excel',
                                className: 'dropdown-item',
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="ti ti-file-description me-1"></i>Pdf',
                                className: 'dropdown-item',
                            },
                            {
                                extend: 'copy',
                                text: '<i class="ti ti-copy me-1"></i>Copy',
                                className: 'dropdown-item',
                            },
                        ],
                    },
                ],
                initComplete: function () {
                    this.api().buttons().container().appendTo('#rekap-perjadin-export');
                },
            });

            // ----- Modal Lihat/Export dokumen -----
            const modalEl = document.getElementById('modal-dokumen');
            const dokumenModal = new bootstrap.Modal(modalEl);
            const tabsEl = document.getElementById('dokumen-tabs');
            const contentEl = document.getElementById('dokumen-tab-content');
            let currentDocs = {};
            let currentActiveKey = null;

            window.openDokumenModal = function (id) {
                const data = window.dokumenDataByRow[id];
                if (!data) return;

                const butuhSpd = data.jenis_perjadin === 'biasa' || data.jenis_perjadin === 'dalam_kota_lebih_8_jam';

                currentDocs = { 'surat-tugas': { label: 'Surat Tugas', html: DokumenPerjadin.renderSuratTugas(data) } };
                if (butuhSpd) {
                    currentDocs['spd'] = { label: 'SPD', html: DokumenPerjadin.renderSpd(data) };
                }
                currentDocs['rincian'] = { label: 'Rincian Biaya', html: DokumenPerjadin.renderRincianBiaya(data) };
                currentDocs['pengeluaran'] = { label: 'Pengeluaran Riil', html: DokumenPerjadin.renderPengeluaranRiil(data) };
                currentDocs['kuitansi'] = { label: 'Kuitansi', html: DokumenPerjadin.renderKuitansi(data) };
                currentDocs['pernyataan'] = { label: 'Pernyataan', html: DokumenPerjadin.renderPernyataan(data) };
                currentDocs['laporan'] = { label: 'Laporan', html: DokumenPerjadin.renderLaporan(data) };

                tabsEl.innerHTML = '';
                contentEl.innerHTML = '';

                let first = true;
                Object.keys(currentDocs).forEach(function (key) {
                    const doc = currentDocs[key];

                    const li = document.createElement('li');
                    li.className = 'nav-item';
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'nav-link' + (first ? ' active' : '');
                    btn.dataset.key = key;
                    btn.textContent = doc.label;
                    btn.addEventListener('click', function () {
                        tabsEl.querySelectorAll('.nav-link').forEach(function (b) { b.classList.remove('active'); });
                        btn.classList.add('active');
                        contentEl.querySelectorAll('.dokumen-preview').forEach(function (p) { p.classList.add('d-none'); });
                        const pane = contentEl.querySelector('[data-key="' + key + '"]');
                        if (pane) pane.classList.remove('d-none');
                        currentActiveKey = key;
                    });
                    li.appendChild(btn);
                    tabsEl.appendChild(li);

                    const pane = document.createElement('div');
                    pane.className = 'dokumen-preview dokumen-a4' + (first ? '' : ' d-none');
                    pane.dataset.key = key;
                    pane.innerHTML = doc.html;
                    contentEl.appendChild(pane);

                    first = false;
                });

                currentActiveKey = Object.keys(currentDocs)[0];
                document.getElementById('modal-dokumen-title').textContent = 'Dokumen — ' + (data.no_surat_tugas || data.perihal || '-');

                dokumenModal.show();
            };

            function activeDokumen() {
                const doc = currentDocs[currentActiveKey];
                return doc || { html: '', label: 'Dokumen' };
            }

            document.getElementById('btn-export-word').addEventListener('click', function (e) {
                e.preventDefault();
                const doc = activeDokumen();
                DokumenPerjadin.exportAsWord(doc.html, doc.label.replace(/\s+/g, '_'));
            });
            document.getElementById('btn-export-excel').addEventListener('click', function (e) {
                e.preventDefault();
                const doc = activeDokumen();
                DokumenPerjadin.exportAsExcel(doc.html, doc.label.replace(/\s+/g, '_'));
            });
            document.getElementById('btn-export-pdf').addEventListener('click', function (e) {
                e.preventDefault();
                const doc = activeDokumen();
                DokumenPerjadin.exportAsPdf(doc.html, doc.label, '{{ asset("assets/css/dokumen-perjadin.css") }}', 'A4');
            });
        });
    </script>
@endpush
