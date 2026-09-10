{{-- Partial tabel rekap + modal Lihat/Copy/Export, dipakai oleh dashboard, "Semua Perjalanan Dinas", dan "Perjalanan Dinas Saya" --}}
@push('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/dokumen-perjadin.css') }}?v={{ filemtime(public_path('assets/css/dokumen-perjadin.css')) }}" />
    <style>
        #table-rekap-perjadin_wrapper .dataTables_filter input {
            min-width: 220px;
        }

        #table-rekap-perjadin_wrapper .dt-action-buttons {
            margin-bottom: 0.75rem;
        }

        #table-rekap-perjadin td:nth-child(7),
        #table-rekap-perjadin th:nth-child(7) {
            min-width: 350px;
        }

        #table-rekap-perjadin thead th {
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.4px;
            color: #566a7f;
            background-color: #eef0f7;
            border-bottom: 2px solid #dfe3f0;
            white-space: nowrap;
        }

        #table-rekap-perjadin tbody tr:nth-child(even) {
            background-color: #f8f7fc;
        }

        #table-rekap-perjadin tbody tr:hover {
            background-color: #eef0fb;
        }

        #table-rekap-perjadin td {
            vertical-align: middle;
        }

        #table-rekap-perjadin .rekap-nomor {
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.8rem;
        }

        #table-rekap-perjadin .rekap-orang {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
@endpush

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="ti ti-table me-1"></i> {{ $cardTitle ?? 'Rekap Perjalanan Dinas' }}</h5>
        {{-- <div id="rekap-perjadin-export"></div> --}}
        <div class="d-flex gap-2">
            <select id="filter-jenis-perjadin" class="form-select" style="width: auto;">
                <option value="">Semua Jenis Perjadin</option>
                <option value="Biasa">Biasa</option>
                <option value="Dalam Kota ≤8 Jam">Dalam Kota ≤8 Jam</option>
                <option value="Dalam Kota >8 Jam">Dalam Kota &gt;8 Jam</option>
            </select>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-plus ti-sm me-1"></i> Buat Dokumen
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('perjalanan-dinas.create', ['jenis' => 'biasa']) }}">
                            Perjalanan Dinas Biasa
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('perjalanan-dinas.create', ['jenis' => 'dalam_kota_kurang_8_jam']) }}">
                            Dalam Kota ≤ 8 Jam
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('perjalanan-dinas.create', ['jenis' => 'dalam_kota_lebih_8_jam']) }}">
                            Dalam Kota &gt; 8 Jam
                        </a>
                    </li>
                </ul>
            </div>
            <div class="dropdown">
                <button class="btn btn-label-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-file-spreadsheet ti-sm me-1"></i> Import Excel
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('perjalanan-dinas.import-template') }}">
                            <i class="ti ti-download me-1"></i> Unduh Template
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal-import-perjalanan-dinas">
                            <i class="ti ti-upload me-1"></i> Import Data
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-datatable pt-0">
        <table class="table" id="table-rekap-perjadin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Status</th>
                    <th>Jenis Perjadin</th>
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
                        <td class="rekap-nomor">{{ $item->id_perjalanan_dinas }}</td>
                        <td>
                            @if ($item->status_draft === 'selesai')
                                <span class="badge rounded-pill bg-label-success"><i class="ti ti-circle-check ti-xs me-1"></i>Selesai</span>
                            @else
                                <span class="badge rounded-pill bg-label-warning"><i class="ti ti-pencil ti-xs me-1"></i>Draft</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-label-{{ ['biasa' => 'info', 'dalam_kota_kurang_8_jam' => 'secondary', 'dalam_kota_lebih_8_jam' => 'primary'][$item->jenis_perjadin] ?? 'secondary' }}">
                                {{ $item->jenis_perjadin_label }}
                            </span>
                        </td>
                        <td>
                            <div class="rekap-orang">
                                {{-- <span class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-{{ ['primary', 'success', 'info', 'warning', 'danger'][crc32($item->pemohon->nama ?? '?') % 5] }}">{{ strtoupper(substr($item->pemohon->nama ?? '-', 0, 1)) }}</span></span> --}}
                                <span>{{ $item->pemohon->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="rekap-orang">
                                {{-- <span class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-{{ ['primary', 'success', 'info', 'warning', 'danger'][crc32($item->pelaksana->nama ?? '?') % 5] }}">{{ strtoupper(substr($item->pelaksana->nama ?? '-', 0, 1)) }}</span></span> --}}
                                <span>{{ $item->pelaksana->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $item->perihal }}</td>
                        <td>{{ $item->desa_asal }}{{ $item->kabupaten_asal !== $item->kabupaten_tujuan ? ', ' . $item->kabupaten_asal : '' }}</td>
                        <td>{{ $item->desa_tujuan }}{{ $item->kabupaten_asal !== $item->kabupaten_tujuan ? ', ' . $item->kabupaten_tujuan : '' }}</td>
                        <td>{{ $item->angkutan ?? '-' }}</td>
                        <td>
                            @if ($item->tanggal_mulai)
                                {{ $item->tanggal_mulai->format('d/m/Y') }}
                                @if ($item->tanggal_selesai && $item->tanggal_mulai->ne($item->tanggal_selesai))
                                    s.d. {{ $item->tanggal_selesai->format('d/m/Y') }}
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $item->pembebanan }}</td>
                        <td><span class="badge bg-label-secondary rekap-nomor">{{ $item->no_surat_tugas }}</span></td>
                        <td>
                            @if ($item->no_spd)
                                <span class="badge bg-label-secondary rekap-nomor">{{ $item->no_spd }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('perjalanan-dinas.show', $item) }}" class="btn btn-icon btn-sm btn-text-primary rounded-pill" title="Lihat">
                                <i class="ti ti-eye ti-sm"></i>
                            </a>
                            <a href="{{ route('perjalanan-dinas.create', ['jenis' => $item->jenis_perjadin, 'from' => $item->id_perjalanan_dinas]) }}"
                                class="btn btn-icon btn-sm btn-text-secondary rounded-pill" title="Copy jadi draft baru">
                                <i class="ti ti-copy ti-sm"></i>
                            </a>
                            <button type="button" class="btn btn-icon btn-sm btn-text-success rounded-pill" title="Export" onclick="openDokumenModal({{ $item->id_perjalanan_dinas }})">
                                <i class="ti ti-download ti-sm"></i>
                            </button>
                            @if ($item->bisaDiubahOleh(auth()->user()->id_pegawai_mitra ?? null))
                                <a href="{{ route('perjalanan-dinas.edit', $item) }}" class="btn btn-icon btn-sm btn-text-warning rounded-pill" title="Edit">
                                    <i class="ti ti-edit ti-sm"></i>
                                </a>
                                <form action="{{ route('perjalanan-dinas.destroy', $item) }}" method="POST" class="d-inline"
                                    onsubmit="return confirmSubmit(this, 'Hapus draft perjalanan dinas ini? Tindakan ini tidak bisa dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Hapus">
                                        <i class="ti ti-trash ti-sm"></i>
                                    </button>
                                </form>
                            @elseif ($item->status_draft === 'selesai' && $item->dimilikiOleh(auth()->user()->id_pegawai_mitra ?? null))
                                <form action="{{ route('perjalanan-dinas.buka-kembali', $item) }}" method="POST" class="d-inline"
                                    onsubmit="return confirmSubmit(this, 'Buka kembali perjalanan dinas ini untuk diedit? Status akan kembali jadi draft.', { icon: 'question', confirmButtonColor: '#696cff' });">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-icon btn-sm btn-text-warning rounded-pill" title="Buka Kembali untuk Diedit">
                                        <i class="ti ti-lock-open ti-sm"></i>
                                    </button>
                                </form>
                            @endif
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <ul class="nav nav-pills mb-0" id="dokumen-tabs" role="tablist"></ul>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-label-primary" id="btn-export-pdf-satu">
                                <i class="ti ti-file-type-pdf me-1"></i> Export
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-export-pdf-semua">
                                <i class="ti ti-files me-1"></i> Export All
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-preview-semua">
                        <i class="ti ti-stack-2 me-1"></i> Preview All
                    </button>
                </div>
                <div id="dokumen-tab-content"></div>
                <div id="dokumen-preview-all" class="dokumen-preview-pages d-none"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Import banyak Perjalanan Dinas sekaligus dari Excel --}}
<div class="modal fade" id="modal-import-perjalanan-dinas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('perjalanan-dinas.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Data Perjalanan Dinas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Belum punya filenya? <a href="{{ route('perjalanan-dinas.import-template') }}">Unduh template Excel</a> terlebih dahulu, isi datanya (1 baris = 1 perjalanan dinas), baru unggah di sini. Dokumentasi (foto/file) tidak ikut diimpor — lengkapi satu per satu lewat menu Edit setelah data masuk.
                    </p>
                    <div class="mb-0">
                        <label class="form-label">File Excel (.xlsx)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-upload ti-sm me-1"></i> Import</button>
                </div>
            </form>
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
    <script src="{{ asset('assets/js/dokumen-perjadin.js') }}?v={{ filemtime(public_path('assets/js/dokumen-perjadin.js')) }}"></script>

    <script>
        window.dokumenDataByRow = @json($dokumenDataById);

        document.addEventListener('DOMContentLoaded', function () {
            const tabelRekap = $('#table-rekap-perjadin').DataTable({
                scrollX: true,
                autoWidth: false,
                fixedColumns: {
                    rightColumns: 1,
                },
                // Data dari server sudah diurutkan terbaru duluan (lihat ->latest() di
                // controller), tapi DataTables tetap butuh "order" eksplisit — tanpa ini
                // dia default sort kolom pertama (No) menaik, jadi yang PALING LAMA malah
                // tampil di atas. Kolom 0 ("No" = id_perjalanan_dinas) descending ekuivalen
                // dengan terbaru di atas karena ID auto-increment.
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { width: '350px', targets: 6 },
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

            // ----- Filter kolom "Jenis Perjadin" — substring biasa (bukan regex exact
            // match) supaya tidak keganggu spasi/baris baru di sekitar teks badge;
            // aman dipakai karena ketiga labelnya cukup beda tidak saling tumpang
            // tindih ("Biasa" vs "Dalam Kota ≤8 Jam" vs "Dalam Kota >8 Jam"). Kolom
            // index 2 sesuai urutan header (No, Status, Jenis Perjadin, ...). -----
            document.getElementById('filter-jenis-perjadin').addEventListener('change', function () {
                tabelRekap.column(2).search(this.value, false, false).draw();
            });

            // ----- Modal Lihat/Export dokumen -----
            const modalEl = document.getElementById('modal-dokumen');
            const dokumenModal = new bootstrap.Modal(modalEl);
            const tabsEl = document.getElementById('dokumen-tabs');
            const contentEl = document.getElementById('dokumen-tab-content');
            const previewAllEl = document.getElementById('dokumen-preview-all');
            const btnPreviewSemua = document.getElementById('btn-preview-semua');
            const btnExportPdfSatu = document.getElementById('btn-export-pdf-satu');
            let currentDocs = {};
            let currentActiveKey = null;
            let currentData = null;

            // Nama file export: "{nomor surat tugas}-{nama pelaksana}-{nama dokumen}",
            // mis. "1122-Luthfiani Nur Aisyah, S.Tr.Stat.-Kuitansi". Nomornya diambil
            // angkanya saja dari format "B-{angka}/62130/KU.340/{tahun}" biar ringkas.
            function sanitasiNamaFile(teks) {
                return (teks || '').replace(/[\\/:*?"<>|]/g, '-').trim();
            }

            function namaFileDokumen(labelDokumen) {
                const noSuratTugas = (currentData && currentData.no_surat_tugas) || '';
                const angkaMatch = noSuratTugas.match(/^B-(\d+)/);
                const nomor = angkaMatch ? angkaMatch[1] : (sanitasiNamaFile(noSuratTugas) || 'TanpaNomor');
                const namaPelaksana = (currentData && currentData.pelaksana && currentData.pelaksana.nama) || 'TanpaNama';
                return sanitasiNamaFile(nomor + '-' + namaPelaksana + '-' + labelDokumen);
            }

            function paperClassFor(key) {
                if (key === 'surat-tugas') return 'dokumen-f4 dokumen-watermark-pratinjau';
                if (key === 'spd') return 'dokumen-f4';
                return 'dokumen-a4' + (key === 'rincian' ? ' dokumen-rincian-biaya' : '');
            }

            function paperSizeFor(key) {
                return (key === 'surat-tugas' || key === 'spd') ? 'f4' : 'a4';
            }

            // Kelas tambahan yang cuma dipakai buat nentuin margin dokumen tertentu (lihat
            // dokumen-perjadin.css) — Perincian Biaya khusus 1,27cm, dokumen A4 lain 2,54cm.
            function extraClassFor(key) {
                if (key === 'rincian') return 'dokumen-rincian-biaya';
                if (key === 'surat-tugas') return 'dokumen-watermark-pratinjau';
                return '';
            }

            function tampilkanTabDokumen(key) {
                tabsEl.querySelectorAll('.nav-link').forEach(function (b) { b.classList.toggle('active', b.dataset.key === key); });
                btnPreviewSemua.classList.remove('active');
                previewAllEl.classList.add('d-none');
                contentEl.classList.remove('d-none');
                contentEl.querySelectorAll('.dokumen-preview-pages').forEach(function (p) { p.classList.add('d-none'); });
                const pane = contentEl.querySelector('[data-key="' + key + '"]');
                if (pane) {
                    pane.classList.remove('d-none');
                    DokumenPerjadin.applyPageMinHeights(pane);
                }
                currentActiveKey = key;
                btnExportPdfSatu.disabled = false;
            }

            function tampilkanPreviewSemua() {
                previewAllEl.innerHTML = Object.keys(currentDocs).map(function (key) {
                    return DokumenPerjadin.renderPaginated(currentDocs[key].html, paperClassFor(key));
                }).join('<div class="mb-4"></div>');
                tabsEl.querySelectorAll('.nav-link').forEach(function (b) { b.classList.remove('active'); });
                btnPreviewSemua.classList.add('active');
                contentEl.classList.add('d-none');
                previewAllEl.classList.remove('d-none');
                DokumenPerjadin.applyPageMinHeights(previewAllEl);
                currentActiveKey = null;
                btnExportPdfSatu.disabled = true;
            }

            window.openDokumenModal = function (id) {
                const data = window.dokumenDataByRow[id];
                if (!data) return;
                currentData = data;

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
                previewAllEl.innerHTML = '';
                previewAllEl.classList.add('d-none');
                contentEl.classList.remove('d-none');

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
                        tampilkanTabDokumen(key);
                    });
                    li.appendChild(btn);
                    tabsEl.appendChild(li);

                    const pane = document.createElement('div');
                    pane.className = 'dokumen-preview-pages' + (first ? '' : ' d-none');
                    pane.dataset.key = key;
                    pane.innerHTML = DokumenPerjadin.renderPaginated(doc.html, paperClassFor(key));
                    contentEl.appendChild(pane);

                    first = false;
                });

                currentActiveKey = Object.keys(currentDocs)[0];
                btnExportPdfSatu.disabled = false;
                document.getElementById('modal-dokumen-title').textContent = 'Dokumen — ' + (data.no_surat_tugas || data.perihal || '-');

                dokumenModal.show();
                modalEl.addEventListener('shown.bs.modal', function onShown() {
                    modalEl.removeEventListener('shown.bs.modal', onShown);
                    const activePane = contentEl.querySelector('[data-key="' + currentActiveKey + '"]');
                    if (activePane) DokumenPerjadin.applyPageMinHeights(activePane);
                });
            };

            btnPreviewSemua.addEventListener('click', function () {
                if (btnPreviewSemua.classList.contains('active')) {
                    tampilkanTabDokumen(Object.keys(currentDocs)[0]);
                } else {
                    tampilkanPreviewSemua();
                }
            });

            const btnExportPdfSemua = document.getElementById('btn-export-pdf-semua');
            const cssUrlDokumen = '{{ asset("assets/css/dokumen-perjadin.css") }}?v={{ filemtime(public_path("assets/css/dokumen-perjadin.css")) }}';
            const exportPdfUrl = '{{ route("dokumen.export-pdf") }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Kunci kedua tombol Export + kasih spinner selagi PDF-nya digenerate di
            // server (Chrome headless butuh beberapa detik per dokumen), supaya tidak
            // diklik dobel dan user tahu prosesnya lagi jalan.
            function kunciTombolExport(tombolAktif) {
                [btnExportPdfSatu, btnExportPdfSemua].forEach(function (b) { b.disabled = true; });
                const labelAsli = tombolAktif.innerHTML;
                tombolAktif.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengekspor...';
                return function selesai() {
                    [btnExportPdfSatu, btnExportPdfSemua].forEach(function (b) { b.disabled = false; });
                    tombolAktif.innerHTML = labelAsli;
                };
            }

            async function eksporSatuDokumen(key) {
                const doc = currentDocs[key];
                if (!doc) return;
                await DokumenPerjadin.exportAsPdfHd(
                    doc.html, namaFileDokumen(doc.label), cssUrlDokumen,
                    paperSizeFor(key), extraClassFor(key), exportPdfUrl, csrfToken
                );
            }

            // "Export" — PDF dokumen di tab yang lagi aktif saja (nonaktif saat mode
            // Preview All, karena tidak ada 1 dokumen tunggal yang aktif). HD (dibikin
            // Chrome headless di server, bukan capture), langsung ke-download tanpa
            // dialog print.
            btnExportPdfSatu.addEventListener('click', async function () {
                const selesai = kunciTombolExport(btnExportPdfSatu);
                try {
                    await eksporSatuDokumen(currentActiveKey);
                } catch (e) {
                    swalError('Gagal export PDF: ' + e.message);
                } finally {
                    selesai();
                }
            });

            // "Export All" — tiap dokumen di-export satu-satu (berurutan, bukan
            // paralel, biar server headless-nya tidak kebanjiran) lalu langsung
            // ke-download sebagai PDF terpisah, TANPA dibundel jadi zip.
            btnExportPdfSemua.addEventListener('click', async function () {
                const selesai = kunciTombolExport(btnExportPdfSemua);
                try {
                    for (const key of Object.keys(currentDocs)) {
                        await eksporSatuDokumen(key);
                    }
                } catch (e) {
                    swalError('Gagal export PDF: ' + e.message);
                } finally {
                    selesai();
                }
            });
        });
    </script>
@endpush
