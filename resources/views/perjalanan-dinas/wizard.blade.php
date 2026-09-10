@extends('layouts.app')

@section('title', $mode === 'edit' ? 'Ubah Perjalanan Dinas' : ($mode === 'view' ? 'Lihat Perjalanan Dinas' : 'Buat Perjalanan Dinas'))

@php
    $jenisLabel = match ($jenis) {
        'biasa' => 'Perjalanan Dinas Biasa',
        'dalam_kota_kurang_8_jam' => 'Dalam Kota ≤ 8 Jam',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        default => 'Perjalanan Dinas',
    };
    $halamanLabel = match ($mode) {
        'edit' => 'Ubah Perjalanan Dinas',
        'view' => 'Lihat Perjalanan Dinas',
        default => 'Buat Perjalanan Dinas',
    };
@endphp

@push('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/dokumen-perjadin.css') }}?v={{ filemtime(public_path('assets/css/dokumen-perjadin.css')) }}" />
    <style>
        .bs-stepper .bs-stepper-content {
            padding: 1.5rem 0 0;
        }

        /* Jenis "> 8 jam"/"biasa" punya 7 step (termasuk SPD) — di layar sempit baris
           step ini bisa lebih lebar dari kartunya. Biar tidak keluar layout, jadikan
           scrollable ke samping alih-alih overflow/terpotong. */
        #wizard-stepper .bs-stepper-header {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            position: sticky;
            top: 3.875rem;
            z-index: 5;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        #wizard-stepper .bs-stepper-header .step,
        #wizard-stepper .bs-stepper-header .line {
            flex-shrink: 0;
        }

        .wizard-form-card {
            border: 1px solid #e7e7e9;
        }

        /* top disamakan dengan tinggi navbar + header stepper yang sticky, biar preview
           tidak nyangkut di belakangnya. max-height + overflow-y bikin panel preview
           punya scrollbar sendiri kalau dokumennya lebih tinggi dari sisa layar. */
        .preview-sticky {
            position: sticky;
            top: calc(3.875rem + 3.75rem);
            max-height: calc(100vh - 3.875rem - 3.75rem - 1rem);
            overflow-y: auto;
        }

        /* Judul + tab dokumen + tombol Export di modal Review dibekukan di atas
           (sticky ke .modal-body yang scroll-nya sendiri, lihat modal-dialog-scrollable),
           biar tetap kelihatan & bisa diklik walau preview dokumennya discroll panjang.
           .modal-body padding-top-nya 1.5rem (24px) — padding itu tetap bagian dari area
           scroll (konten yang discroll tetap lewat situ), jadi kalau sticky-nya cuma
           "top:0" bakal nyisain celah 24px di atas toolbar tempat dokumen yang discroll
           masih keintip. Margin negatif + padding sebesar padding parent "menarik" balok
           toolbar sampai ke tepi asli area scroll, jadi latar putihnya benar-benar nutup
           semua, bukan cuma teksnya doang. */
        #modal-review-toolbar-sticky {
            position: sticky;
            top: -1.5rem;
            z-index: 20;
            background: #fff;
            padding-top: 1.5rem;
            margin-top: -1.5rem;
            padding-bottom: 1rem;
            margin-bottom: 0.25rem;
            border-bottom: 1px solid #dfe3f0;
        }

        .btn-remove-row {
            line-height: 1;
        }

        #pengeluaran-rows > div.pengeluaran-row:last-child {
            border-bottom: none !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        /* Tombol "x" di pojok kanan atas tiap thumbnail Dokumentasi, untuk menghapus
           file yang sudah dipilih/diunggah tanpa perlu buka ulang dialog file. */
        .dokumentasi-thumb {
            padding-top: 1.1rem !important;
        }

        .dokumentasi-hapus {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            padding: 0;
            border: none;
            border-radius: 50%;
            background: #dc3545;
            color: #fff;
            font-size: 0.65rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .dokumentasi-hapus:hover {
            background: #bb2d3b;
        }

        /* Tanda bintang merah field wajib (cuma wajib saat "Selesai & Simpan", lihat
           isDataWajibLengkap — draft boleh kosong, tapi tetap ditandai dari awal biar
           user tahu apa yang bakal diminta lengkap nanti). */
        .required-mark {
            color: #dc3545;
            margin-left: 0.15rem;
        }

        /* Pesan error di bawah field wajib yang masih kosong — baru muncul setelah
           field-nya "disentuh" (blur/ganti nilai), bukan langsung pas halaman dibuka,
           supaya form baru tidak langsung penuh tulisan merah dari awal. */
        .field-error {
            display: none;
            color: #dc3545;
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        .field-error.show {
            display: block;
        }

        /* Step yang semua field wajibnya sudah lengkap: nomornya tetap kelihatan (bukan
           diganti ikon), cuma warnanya jadi gaya "label" hijau muda (sama seperti
           .bg-label-success di tema ini) — bukan hijau solid, biar tidak terlalu
           mencolok dibanding step yang lagi aktif (biru solid). */
        #wizard-stepper .step.step-complete .bs-stepper-circle {
            background-color: #dff7e9 !important;
            color: #28c76f !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-1">{{ $halamanLabel }}</h4>
            <p class="text-muted mb-0">{{ $jenisLabel }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-label-secondary d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left ti-sm"></i> Kembali
            </a>
            @if ($mode !== 'view' && $pemohonPegawai)
                <button type="button" id="btn-simpan-draft" class="btn btn-warning"><i class="ti ti-device-floppy ti-sm me-1"></i> Simpan Draft</button>
                <button type="button" id="btn-selesai" class="btn btn-primary"><i class="ti ti-circle-check ti-sm me-1"></i> Selesai</button>
            @endif
            @if ($mode === 'view' && $perjalananDinas && $perjalananDinas->status_draft === 'selesai' && $perjalananDinas->dimilikiOleh(auth()->user()->id_pegawai_mitra))
                <form action="{{ route('perjalanan-dinas.buka-kembali', $perjalananDinas) }}" method="POST" onsubmit="return confirm('Buka kembali perjalanan dinas ini untuk diedit? Status akan kembali jadi draft.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-warning"><i class="ti ti-lock-open ti-sm me-1"></i> Buka Kembali untuk Diedit</button>
                </form>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Periksa kembali data yang diisi:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($mode !== 'view' && !$pemohonPegawai)
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle ti-lg mt-1"></i>
            <div>
                <div class="fw-semibold mb-1">Akun Anda belum terhubung ke data pegawai</div>
                <p class="mb-0">
                    Pemohon perjalanan dinas otomatis diambil dari identitas pegawai Anda. Lengkapi data pegawai
                    (nama, jabatan, pangkat, golongan) di halaman
                    <a href="{{ route('profile.edit') }}" class="alert-link">Profil</a> terlebih dahulu sebelum membuat perjalanan dinas.
                </p>
            </div>
        </div>
    @else
    <form id="wizard-form" method="POST"
        action="{{ $mode === 'edit' ? route('perjalanan-dinas.update', $perjalananDinas) : route('perjalanan-dinas.store') }}"
        enctype="multipart/form-data" novalidate data-mode="{{ $mode }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif
        <input type="hidden" name="jenis_perjadin" value="{{ $jenis }}">
        <input type="hidden" id="input-status_draft" name="status_draft" value="draft_sesi1">

        <div class="bs-stepper wizard-numbered" id="wizard-stepper">
            <div class="bs-stepper-header" role="tablist">
                @php($stepNo = 0)
                <div class="step" data-target="#step-umum">
                    <button type="button" class="step-trigger" role="tab">
                        <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Data Umum</span>
                            <span class="bs-stepper-subtitle">Surat Tugas</span>
                        </span>
                    </button>
                </div>
                <div class="line"></div>
                @if ($butuhSpd)
                    <div class="step" data-target="#step-spd">
                        <button type="button" class="step-trigger" role="tab">
                            <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">SPD</span>
                                <span class="bs-stepper-subtitle">Surat Perjalanan</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"></div>
                @endif
                <div class="step" data-target="#step-rincian">
                    <button type="button" class="step-trigger" role="tab">
                        <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Rincian Biaya</span>
                            <span class="bs-stepper-subtitle">Rencana biaya</span>
                        </span>
                    </button>
                </div>
                <div class="line"></div>
                <div class="step" data-target="#step-pengeluaran">
                    <button type="button" class="step-trigger" role="tab">
                        <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Pengeluaran Riil</span>
                            <span class="bs-stepper-subtitle">Realisasi biaya</span>
                        </span>
                    </button>
                </div>
                <div class="line"></div>
                <div class="step" data-target="#step-kuitansi">
                    <button type="button" class="step-trigger" role="tab">
                        <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Kuitansi</span>
                            <span class="bs-stepper-subtitle">Pembayaran</span>
                        </span>
                    </button>
                </div>
                <div class="line"></div>
                <div class="step" data-target="#step-pernyataan">
                    <button type="button" class="step-trigger" role="tab">
                        <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Surat Pernyataan</span>
                            <span class="bs-stepper-subtitle">Kondisi khusus</span>
                        </span>
                    </button>
                </div>
                <div class="line"></div>
                <div class="step" data-target="#step-laporan">
                    <button type="button" class="step-trigger" role="tab">
                        <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Laporan</span>
                            <span class="bs-stepper-subtitle">&amp; Dokumentasi</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="bs-stepper-content">
                {{-- STEP: DATA UMUM & SURAT TUGAS --}}
                <div id="step-umum" class="content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card wizard-form-card">
                                <div class="card-body">
                                    <h6 class="mb-3">Data Umum &amp; Surat Tugas</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">No. Surat Tugas<span class="required-mark">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">B-</span>
                                                <input type="text" id="input-no_surat_tugas_angka" class="form-control" inputmode="numeric" pattern="[0-9]*" placeholder="mis. 1082" value="{{ $noSuratTugasAngka }}">
                                                <span class="input-group-text" id="suffix-no_surat_tugas">/62130/KU.340/{{ now()->year }}</span>
                                            </div>
                                            <input type="hidden" id="input-no_surat_tugas" name="no_surat_tugas" value="{{ $oldNoSuratTugas }}">
                                            <div class="field-error" id="err-input-no_surat_tugas">Nomor surat tugas wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Surat Tugas<span class="required-mark">*</span></label>
                                            <input type="text" id="input-tanggal_surat_tugas" name="tanggal_surat_tugas" class="form-control" autocomplete="off" placeholder="dd-mm-yyyy" value="{{ old('tanggal_surat_tugas') }}" required>
                                            <div class="field-error" id="err-input-tanggal_surat_tugas">Tanggal surat tugas wajib diisi.</div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Jenis Kegiatan<span class="required-mark">*</span></label>
                                            <select id="input-jenis_kegiatan" name="id_jenis_kegiatan" class="form-select" required>
                                                <option value="" disabled {{ old('id_jenis_kegiatan') ? '' : 'selected' }}>Pilih jenis kegiatan</option>
                                                @foreach ($jenisKegiatanList as $jk)
                                                    <option value="{{ $jk->id_jenis_kegiatan }}"
                                                        data-uraian-tugas="{{ $jk->template_uraian_tugas }}"
                                                        data-perihal="{{ $jk->template_perihal }}"
                                                        data-uraian-laporan="{{ $jk->template_uraian_laporan }}"
                                                        data-mak="{{ $jk->saran_mak_default }}"
                                                        {{ old('id_jenis_kegiatan') == $jk->id_jenis_kegiatan ? 'selected' : '' }}>
                                                        {{ $jk->nama_kegiatan }}
                                                    </option>
                                                @endforeach
                                                <option value="__lainnya__">Lainnya...</option>
                                            </select>
                                            @if ($jenisKegiatanList->isEmpty())
                                                <div class="form-text text-warning">Belum ada data jenis kegiatan di master data.</div>
                                            @endif
                                            <div class="field-error" id="err-input-jenis_kegiatan">Jenis kegiatan wajib dipilih.</div>
                                            <div class="input-group mt-2" id="wrapper-jenis_kegiatan_lainnya" style="display: none;">
                                                <input type="text" id="input-jenis_kegiatan_lainnya" class="form-control" placeholder="Sebutkan jenis kegiatan">
                                                <button type="button" id="btn-tambah-jenis_kegiatan" class="btn btn-outline-primary">Tambah</button>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Perihal<span class="required-mark">*</span></label>
                                            <input type="text" id="input-perihal" name="perihal" class="form-control" value="{{ old('perihal') }}" required>
                                            <div class="field-error" id="err-input-perihal">Perihal wajib diisi &mdash; periksa lagi walau sudah terisi otomatis dari Jenis Kegiatan (lengkapi bagian "[]" dengan nama survei).</div>
                                        </div>
                                        {{-- <div class="col-md-12">
                                            <label class="form-label">Uraian Tugas</label>
                                            <textarea id="input-uraian_tugas" name="uraian_tugas" class="form-control" rows="2">{{ old('uraian_tugas') }}</textarea>
                                        </div> --}}
                                        <div class="col-md-4">
                                            <label class="form-label">Pelaksana<span class="required-mark">*</span></label>
                                            <select id="input-jenis_pelaksana" class="form-select">
                                                <option value="" disabled {{ $jenisPelaksanaLama ? '' : 'selected' }}>Pilih pelaksana</option>
                                                <option value="pegawai" {{ $jenisPelaksanaLama === 'pegawai' ? 'selected' : '' }}>Pegawai BPS</option>
                                                <option value="mitra" {{ $jenisPelaksanaLama === 'mitra' ? 'selected' : '' }}>Mitra</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Nama Pelaksana<span class="required-mark">*</span></label>
                                            <div id="wrapper-pelaksana_pegawai" style="display: none;">
                                                <select id="input-pelaksana_pegawai" class="form-select select2">
                                                    <option value="">Pilih pegawai</option>
                                                    @foreach ($pegawaiList as $pm)
                                                        <option value="{{ $pm->id_pegawai_mitra }}"
                                                            data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}"
                                                            {{ $jenisPelaksanaLama === 'pegawai' && $idPelaksanaLama == $pm->id_pegawai_mitra ? 'selected' : '' }}>
                                                            {{ $pm->nama }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="wrapper-pelaksana_mitra" style="display: none;">
                                                <select id="input-pelaksana_mitra" class="form-select select2">
                                                    <option value="">Pilih mitra</option>
                                                    @foreach ($mitraList as $pm)
                                                        <option value="{{ $pm->id_pegawai_mitra }}"
                                                            data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}"
                                                            {{ $jenisPelaksanaLama === 'mitra' && $idPelaksanaLama == $pm->id_pegawai_mitra ? 'selected' : '' }}>
                                                            {{ $pm->nama }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <input type="hidden" id="input-pelaksana" name="id_pegawai_mitra_pelaksana" value="{{ $idPelaksanaLama }}">
                                            <div class="field-error" id="err-input-pelaksana">Pelaksana wajib dipilih.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Mulai &ndash; Selesai<span class="required-mark">*</span></label>
                                            <input type="text" id="input-rentang_tanggal" class="form-control" autocomplete="off" placeholder="Pilih rentang tanggal">
                                            <input type="hidden" id="input-tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}">
                                            <input type="hidden" id="input-tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}">
                                            <div class="field-error" id="err-input-tanggal_mulai">Tanggal mulai &ndash; selesai wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Kuitansi</label>
                                            <input type="text" id="input-tanggal_kuitansi" name="tanggal_kuitansi" class="form-control" autocomplete="off" value="{{ old('tanggal_kuitansi') ?: ($mode !== 'view' ? now()->format('Y-m-d') : '') }}">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Pembebanan (Akun/MAK)<span class="required-mark">*</span></label>
                                            <input type="text" id="input-pembebanan" name="pembebanan" class="form-control" value="{{ old('pembebanan') }}" placeholder="mis. GG.2902.BMA.006.530.B.524113" required>
                                            <div class="field-error" id="err-input-pembebanan">Pembebanan (Akun/MAK) wajib diisi &mdash; periksa lagi walau sudah terisi otomatis dari Jenis Kegiatan.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tingkat Biaya Perjadin</label>
                                            <select id="input-tingkat_biaya" class="form-select">
                                                <option value="">-</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C" selected>C</option>
                                            </select>
                                        </div>
                                        @unless ($jenis === 'biasa')
                                        <div class="col-md-6">
                                            <label class="form-label">Desa/Kel. Asal<span class="required-mark">*</span></label>
                                            <select id="input-desa_asal_pilih" class="select2 form-select" multiple>
                                                @if (count($rekomendasiDesa))
                                                    <optgroup label="Rekomendasi (paling sering dipilih)">
                                                        @foreach ($rekomendasiDesa as $nama)
                                                            <option value="{{ $nama }}">{{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                @foreach ($kecamatanMurungRaya as $kecamatan => $wilayah)
                                                    <optgroup label="Kec. {{ $kecamatan }}">
                                                        @foreach ($wilayah['kelurahan'] as $nama)
                                                            <option value="Kelurahan {{ $nama }}">Kelurahan {{ $nama }}</option>
                                                        @endforeach
                                                        @foreach ($wilayah['desa'] as $nama)
                                                            <option value="Desa {{ $nama }}">Desa {{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                            <input type="hidden" id="input-desa_asal" name="desa_asal" value="{{ old('desa_asal') }}">
                                            <div class="field-error" id="err-input-desa_asal">Desa/Kel. asal wajib dipilih.</div>
                                        </div>
                                        @else
                                            <input type="hidden" id="input-desa_asal" name="desa_asal" value="">
                                        @endunless
                                        <div class="col-md-6">
                                            <label class="form-label">Kabupaten/Kota Asal<span class="required-mark">*</span></label>
                                            <select id="input-kabupaten_asal_pilih" class="select2 form-select" @if ($isDalamKota) disabled @endif>
                                                <option value="" {{ $defaultKabupatenAsal === '' ? 'selected' : '' }}>-- Pilih Kabupaten/Kota --</option>
                                                @if (count($rekomendasiKabupatenKota))
                                                    <optgroup label="Rekomendasi (paling sering dipilih)">
                                                        @foreach ($rekomendasiKabupatenKota as $nama)
                                                            <option value="{{ $nama }}" {{ $nama === $defaultKabupatenAsal ? 'selected' : '' }}>{{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                @foreach ($kabupatenKotaIndonesia as $provinsi => $daftarNama)
                                                    <optgroup label="{{ $provinsi }}">
                                                        @foreach ($daftarNama as $nama)
                                                            <option value="{{ $nama }}" {{ $nama === $defaultKabupatenAsal ? 'selected' : '' }}>{{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                            <input type="hidden" id="input-kabupaten_asal" name="kabupaten_asal" value="{{ $defaultKabupatenAsal }}">
                                            <div class="field-error" id="err-input-kabupaten_asal">Kabupaten/Kota asal wajib dipilih.</div>
                                        </div>
                                        @unless ($jenis === 'biasa')
                                        <div class="col-md-6">
                                            <label class="form-label">Desa/Kel. Tujuan<span class="required-mark">*</span></label>
                                            <select id="input-desa_tujuan_pilih" class="select2 form-select" multiple>
                                                @if (count($rekomendasiDesa))
                                                    <optgroup label="Rekomendasi (paling sering dipilih)">
                                                        @foreach ($rekomendasiDesa as $nama)
                                                            <option value="{{ $nama }}">{{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                @foreach ($kecamatanMurungRaya as $kecamatan => $wilayah)
                                                    <optgroup label="Kec. {{ $kecamatan }}">
                                                        @foreach ($wilayah['kelurahan'] as $nama)
                                                            <option value="Kelurahan {{ $nama }}">Kelurahan {{ $nama }}</option>
                                                        @endforeach
                                                        @foreach ($wilayah['desa'] as $nama)
                                                            <option value="Desa {{ $nama }}">Desa {{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                            <input type="hidden" id="input-desa_tujuan" name="desa_tujuan" value="{{ old('desa_tujuan') }}">
                                            <div class="field-error" id="err-input-desa_tujuan">Desa/Kel. tujuan wajib dipilih.</div>
                                        </div>
                                        @else
                                            <input type="hidden" id="input-desa_tujuan" name="desa_tujuan" value="">
                                        @endunless
                                        <div class="col-md-6">
                                            <label class="form-label">Kabupaten/Kota Tujuan<span class="required-mark">*</span></label>
                                            <select id="input-kabupaten_tujuan_pilih" class="select2 form-select" @if ($isDalamKota) disabled @endif>
                                                <option value="" {{ $defaultKabupatenTujuan === '' ? 'selected' : '' }}>-- Pilih Kabupaten/Kota --</option>
                                                @if (count($rekomendasiKabupatenKota))
                                                    <optgroup label="Rekomendasi (paling sering dipilih)">
                                                        @foreach ($rekomendasiKabupatenKota as $nama)
                                                            <option value="{{ $nama }}" {{ $nama === $defaultKabupatenTujuan ? 'selected' : '' }}>{{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                @foreach ($kabupatenKotaIndonesia as $provinsi => $daftarNama)
                                                    <optgroup label="{{ $provinsi }}">
                                                        @foreach ($daftarNama as $nama)
                                                            <option value="{{ $nama }}" {{ $nama === $defaultKabupatenTujuan ? 'selected' : '' }}>{{ $nama }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                            <input type="hidden" id="input-kabupaten_tujuan" name="kabupaten_tujuan" value="{{ $defaultKabupatenTujuan }}">
                                            <div class="field-error" id="err-input-kabupaten_tujuan">Kabupaten/Kota tujuan wajib dipilih.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Penandatangan ST</label>
                                            <select id="input-penandatangan_st" name="id_penandatangan_st" class="form-select">
                                                <option value="">-</option>
                                                @foreach ($pegawaiMitraList as $pm)
                                                    <option value="{{ $pm->id_pegawai_mitra }}"
                                                        data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}" data-peran="{{ $pm->peran_pejabat }}"
                                                        {{ old('id_penandatangan_st') == $pm->id_pegawai_mitra ? 'selected' : '' }}>{{ $pm->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">PPK</label>
                                            <select id="input-ppk" name="id_ppk" class="form-select">
                                                <option value="">-</option>
                                                @foreach ($pegawaiMitraList as $pm)
                                                    <option value="{{ $pm->id_pegawai_mitra }}"
                                                        data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}" data-peran="{{ $pm->peran_pejabat }}"
                                                        {{ old('id_ppk') == $pm->id_pegawai_mitra ? 'selected' : '' }}>{{ $pm->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bendahara</label>
                                            <select id="input-bendahara" name="id_bendahara" class="form-select">
                                                <option value="">-</option>
                                                @foreach ($pegawaiMitraList as $pm)
                                                    <option value="{{ $pm->id_pegawai_mitra }}"
                                                        data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}" data-peran="{{ $pm->peran_pejabat }}"
                                                        {{ old('id_bendahara') == $pm->id_pegawai_mitra ? 'selected' : '' }}>{{ $pm->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                {{-- <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div> --}}
                                <div id="preview-surat-tugas" class="dokumen-preview-pages"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP: SPD --}}
                @if ($butuhSpd)
                    <div id="step-spd" class="content">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="card wizard-form-card">
                                    <div class="card-body">
                                        <h6 class="mb-3">Surat Perjalanan Dinas (SPD)</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">No. SPD<span class="required-mark">*</span></label>
                                                <div class="input-group">
                                                    <input type="text" id="input-no_spd" name="no_spd" class="form-control" value="{{ old('no_spd') }}" required>
                                                    <button type="button" id="btn-ambil-nomor-spd" class="btn btn-outline-primary">Ambil Nomor</button>
                                                </div>
                                                <div class="field-error" id="err-input-no_spd">Nomor SPD wajib diisi.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tanggal SPD<span class="required-mark">*</span></label>
                                                <input type="text" id="input-tanggal_spd" name="tanggal_spd" class="form-control" autocomplete="off" value="{{ old('tanggal_spd') ?: ($mode !== 'view' ? now()->format('Y-m-d') : '') }}" required>
                                                <div class="field-error" id="err-input-tanggal_spd">Tanggal SPD wajib diisi.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Alat Angkut<span class="required-mark">*</span></label>
                                                <select id="input-angkutan_pilih" class="form-select">
                                                    <option value="">-- Pilih Alat Angkut --</option>
                                                    <option value="Kendaraan Umum">Kendaraan Umum</option>
                                                    <option value="Kendaraan Air">Kendaraan Air</option>
                                                    <option value="Kendaraan Darat">Kendaraan Darat</option>
                                                    <option value="__lainnya__">Lainnya...</option>
                                                </select>
                                                <input type="hidden" id="input-angkutan" name="angkutan" value="{{ old('angkutan') }}" required>
                                                <div class="field-error" id="err-input-angkutan">Alat angkut wajib diisi.</div>
                                            </div>
                                            <div class="col-md-6" id="wrapper-angkutan_lainnya" style="display: none;">
                                                <label class="form-label">Sebutkan Alat Angkut</label>
                                                <input type="text" id="input-angkutan_lainnya" class="form-control" placeholder="mis. Kendaraan Umum">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-4">
                                            <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                            <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="preview-sticky">
                                    {{-- <div class="alert alert-warning py-2 px-3 small mb-2">
                                        <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                    </div> --}}
                                    <div id="preview-spd" class="dokumen-preview-pages"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- STEP: RINCIAN BIAYA --}}
                <div id="step-rincian" class="content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card wizard-form-card">
                                <div class="card-body">
                                    <h6 class="mb-3">Rincian Biaya (Rencana)</h6>
                                    <div class="row g-2 align-items-center mb-1">
                                        <div class="col-4"></div>
                                        <div class="col-3"><label class="form-label mb-0 small text-muted" for="input-rincian_hari-uang_harian">Jumlah Hari</label></div>
                                        <div class="col-5"><label class="form-label mb-0 small text-muted" for="input-rincian-uang_harian">Rate per Hari/Malam (Rp)</label></div>
                                    </div>
                                    <div class="row g-2 align-items-center mb-1">
                                        <div class="col-4"><label class="form-label mb-0" for="input-rincian-uang_harian">Uang Harian<span class="required-mark">*</span></label></div>
                                        <div class="col-3"><input type="number" min="0" step="1" id="input-rincian_hari-uang_harian" name="rincian_hari[uang_harian]" class="form-control rincian-input" value="{{ old('rincian_hari.uang_harian', '') }}"></div>
                                        <div class="col-5"><input type="number" min="0" step="1000" id="input-rincian-uang_harian" name="rincian[uang_harian]" class="form-control rincian-input" value="{{ old('rincian.uang_harian', 0) }}"></div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-4"></div>
                                        <div class="col-8"><div id="hint-rekomendasi-uang-harian" class="form-text mb-0"></div></div>
                                    </div>
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-4"><label class="form-label mb-0" for="input-rincian-transport">Transport</label></div>
                                        <div class="col-3"><input type="number" min="0" step="1" id="input-rincian_hari-transport" name="rincian_hari[transport]" class="form-control rincian-input" value="{{ old('rincian_hari.transport', '') }}"></div>
                                        <div class="col-5"><input type="number" min="0" step="1000" id="input-rincian-transport" name="rincian[transport]" class="form-control rincian-input" value="{{ old('rincian.transport', 0) }}"></div>
                                    </div>
                                    <div class="row g-2 align-items-center mb-0">
                                        <div class="col-4"><label class="form-label mb-0" for="input-rincian-penginapan">Penginapan</label></div>
                                        <div class="col-3"><input type="number" min="0" step="1" id="input-rincian_hari-penginapan" name="rincian_hari[penginapan]" class="form-control rincian-input" value="{{ old('rincian_hari.penginapan', '') }}"></div>
                                        <div class="col-5"><input type="number" min="0" step="1000" id="input-rincian-penginapan" name="rincian[penginapan]" class="form-control rincian-input" value="{{ old('rincian.penginapan', 0) }}"></div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-4"></div>
                                        <div class="col-8"><div id="hint-rekomendasi-akomodasi" class="form-text mb-0"></div></div>
                                    </div>
                                    <div class="form-text">Isi rate per hari/malam (bukan total) — totalnya dihitung otomatis (rate &times; jumlah hari/malam) dan langsung kelihatan di preview dokumen. Jumlah hari/malam bisa berbeda-beda per komponen (mis. penginapan cuma 3 malam meski perjalanan 5 hari) — kosongkan kalau ikut lama perjalanan dinas.</div>
                                    <div class="field-error" id="err-rincian-total">Uang Harian wajib diisi (lebih dari 0).</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                {{-- <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div> --}}
                                <div id="preview-rincian-biaya" class="dokumen-preview-pages"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP: PENGELUARAN RIIL --}}
                <div id="step-pengeluaran" class="content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card wizard-form-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="mb-0">Pengeluaran Riil</h6>
                                        <button type="button" id="btn-add-pengeluaran" class="btn btn-sm btn-label-primary"><i class="ti ti-plus ti-sm"></i> Tambah</button>
                                    </div>
                                    <div id="pengeluaran-rows"></div>
                                    <div id="pengeluaran-empty" class="text-muted small">Belum ada pengeluaran riil. Boleh diisi belakangan setelah perjalanan selesai.</div>
                                    <div id="warning-transport-agregat" class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mt-2 mb-0 d-none">
                                        <i class="ti ti-alert-triangle ti-sm"></i>
                                        <div class="small"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                {{-- <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div> --}}
                                <div id="preview-pengeluaran-riil" class="dokumen-preview-pages"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP: KUITANSI --}}
                <div id="step-kuitansi" class="content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="alert alert-info py-2 px-3 small mb-0">
                                <i class="ti ti-info-circle me-1"></i> Tanggal Kuitansi diisi di step Data Umum, di samping Tanggal Mulai &ndash; Selesai.
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                {{-- <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div> --}}
                                <div id="preview-kuitansi" class="dokumen-preview-pages"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP: SURAT PERNYATAAN --}}
                <div id="step-pernyataan" class="content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card wizard-form-card">
                                <div class="card-body">
                                    <h6 class="mb-3">Surat Pernyataan</h6>
                                    <div class="form-check form-check-success custom-option custom-option-basic mb-2">
                                        <label class="form-check-label custom-option-content" for="input-pernyataan-tidak_pakai_kendaraan_dinas">
                                            <input class="form-check-input pernyataan-checkbox" type="checkbox" id="input-pernyataan-tidak_pakai_kendaraan_dinas" name="pernyataan[tidak_pakai_kendaraan_dinas]" value="1" {{ old('pernyataan.tidak_pakai_kendaraan_dinas') ? 'checked' : '' }}>
                                            <span class="custom-option-header">
                                                <span class="h6 mb-0">Tidak Menggunakan Kendaraan Dinas</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-success custom-option custom-option-basic mb-2">
                                        <label class="form-check-label custom-option-content" for="input-pernyataan-tidak_menginap_hotel">
                                            <input class="form-check-input pernyataan-checkbox" type="checkbox" id="input-pernyataan-tidak_menginap_hotel" name="pernyataan[tidak_menginap_hotel]" value="1" {{ old('pernyataan.tidak_menginap_hotel') ? 'checked' : '' }}>
                                            <span class="custom-option-header">
                                                <span class="h6 mb-0">Tidak Menginap di Hotel/Akomodasi</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-success custom-option custom-option-basic mb-0">
                                        <label class="form-check-label custom-option-content" for="input-pernyataan-keterlambatan">
                                            <input class="form-check-input pernyataan-checkbox" type="checkbox" id="input-pernyataan-keterlambatan" name="pernyataan[keterlambatan]" value="1" {{ old('pernyataan.keterlambatan') ? 'checked' : '' }}>
                                            <span class="custom-option-header">
                                                <span class="h6 mb-0">Keterlambatan Pengajuan Tagihan</span>
                                            </span>
                                        </label>
                                        <div id="pernyataan-keterlambatan-keterangan-wrap" class="mt-2" {{ old('pernyataan.keterlambatan') ? '' : 'hidden' }}>
                                            <textarea class="form-control" id="input-pernyataan-keterlambatan_keterangan" name="pernyataan_keterlambatan_keterangan" rows="2" placeholder="Alasan keterlambatan">{{ old('pernyataan_keterlambatan_keterangan') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                {{-- <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div> --}}
                                <div id="preview-pernyataan" class="dokumen-preview-pages"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP: LAPORAN & DOKUMENTASI --}}
                <div id="step-laporan" class="content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card wizard-form-card">
                                <div class="card-body">
                                    <h6 class="mb-3">Laporan Perjalanan Dinas</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-12">
                                            <label class="form-label">Kesimpulan Hasil Kegiatan<span class="required-mark">*</span></label>
                                            <textarea id="input-kesimpulan" name="kesimpulan_hasil_kegiatan" class="form-control" rows="3">{{ old('kesimpulan_hasil_kegiatan') }}</textarea>
                                            <div class="field-error" id="err-input-kesimpulan">Kesimpulan hasil kegiatan wajib diisi.</div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Tindak Lanjut<span class="required-mark">*</span></label>
                                            <textarea id="input-tindak_lanjut" name="tindak_lanjut" class="form-control" rows="2">{{ old('tindak_lanjut') }}</textarea>
                                            <div class="field-error" id="err-input-tindak_lanjut">Tindak lanjut wajib diisi.</div>
                                        </div>
                                    </div>
                                    <h6 class="mb-3">Dokumentasi<span class="required-mark">*</span></h6>
                                    <input type="file" id="input-dokumentasi" name="dokumentasi[]" class="form-control" multiple accept="image/*,.pdf">
                                    <div id="dokumentasi-preview-list" class="d-flex flex-wrap gap-2 mt-3"></div>
                                    <div class="field-error" id="err-laporan-dokumentasi">Minimal 1 file dokumentasi wajib diunggah.</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                {{-- <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div> --}}
                                <div id="preview-laporan" class="dokumen-preview-pages"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Modal Review sebelum Simpan Draft / Selesai — sekalian preview gabungan semua
         dokumen (persis kaya hasil Export), biar user bisa cek hasil akhirnya dulu
         sebelum benar-benar disimpan, tanpa harus buka menu Export terpisah. --}}
    <div class="modal fade" id="modal-review-simpan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">Review Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-review-status"></div>
                    <div id="modal-review-summary"></div>
                    <hr class="my-4">
                    <div id="modal-review-toolbar-sticky">
                        <h6 class="text-muted mb-3">Preview Dokumen</h6>
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <ul class="nav nav-pills mb-0" id="modal-review-tabs" role="tablist"></ul>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-label-primary" id="btn-review-export-satu">
                                        <i class="ti ti-file-type-pdf me-1"></i> Export
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" id="btn-review-export-semua">
                                        <i class="ti ti-files me-1"></i> Export All
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-review-preview-semua">
                                <i class="ti ti-stack-2 me-1"></i> Preview All
                            </button>
                        </div>
                    </div>
                    <div id="modal-review-tab-content"></div>
                    <div id="modal-review-preview-all" class="dokumen-preview-pages d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="modal-review-confirm" class="btn btn-primary"><i class="ti ti-device-floppy ti-sm me-1"></i> <span id="modal-review-confirm-label">Simpan</span></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Template baris berulang (dipakai oleh JS, bukan ditampilkan langsung) --}}
    <script type="text/template" id="pengeluaran-row-template">
        <div class="row g-2 mb-3 pb-3 border-bottom pengeluaran-row">
            <div class="col-12">
                <label class="form-label small mb-1">Uraian</label>
                <textarea name="pengeluaran[__INDEX__][uraian]" class="form-control pengeluaran-input pengeluaran-uraian" rows="2" placeholder="mis. Transportasi darat dari Beriwit ke Juking Supan PP"></textarea>
            </div>
            <div class="col-5">
                <label class="form-label small mb-1">Jenis</label>
                <select name="pengeluaran[__INDEX__][jenis]" class="form-select pengeluaran-input">
                    <option value="transportasi">Biaya Transportasi</option>
                    <option value="akomodasi">Biaya Akomodasi</option>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label small mb-1">Nominal (Rp)</label>
                <input type="number" min="0" step="1000" name="pengeluaran[__INDEX__][nominal]" class="form-control pengeluaran-input">
            </div>
            <div class="col-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-remove-row w-100 px-0" title="Hapus"><i class="ti ti-trash ti-sm"></i></button>
            </div>
        </div>
    </script>

    @endif
@endsection

@if ($pemohonPegawai)
@push('page-js')
    <script src="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/js/forms-selects.js') }}"></script>
    <script src="{{ asset('assets/js/dokumen-perjadin.js') }}?v={{ filemtime(public_path('assets/js/dokumen-perjadin.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('wizard-form');

            // ----- Rate Akomodasi & Rate Transport (SK KPA Nomor 13 & 14 Tahun 2026) -----
            // dipakai untuk rekomendasi nilai Penginapan & peringatan Pengeluaran Riil di bawah.
            const rateAkomodasiByKecamatan = @json($rateAkomodasiByKecamatan);
            const rateTransportMaxByWilayah = @json($rateTransportMaxByWilayah);
            const desaKeKecamatan = @json($desaKeKecamatan);
            const stepperEl = document.getElementById('wizard-stepper');
            const stepper = new Stepper(stepperEl, { linear: false, animation: true });

            document.querySelectorAll('.btn-wizard-next').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    stepper.next();
                });
            });

            document.querySelectorAll('.btn-wizard-prev').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    stepper.previous();
                });
            });

            // Sengaja tombolnya bertipe button biasa (bukan submit) supaya form ini tidak
            // punya elemen submit sama sekali — kalau ada, browser bisa submit form secara
            // implisit saat Enter ditekan di select/input manapun (mis. habis pilih opsi di
            // dropdown Rincian Biaya), padahal user belum klik "Simpan Draft"/"Selesai".
            //
            // Sebelum benar-benar submit, tampilkan modal review dulu — isi wajib (Data
            // Umum, SPD kalau perlu, Rincian Biaya) harus lengkap sebelum tombol konfirmasi
            // di modal itu aktif, baik untuk draft maupun selesai.
            const reviewModalEl = document.getElementById('modal-review-simpan');
            const reviewModal = reviewModalEl ? new bootstrap.Modal(reviewModalEl) : null;
            const statusDraftInput = document.getElementById('input-status_draft');
            let pendingStatusDraft = 'draft_sesi1';

            function isDataWajibLengkap(statusDraft) {
                // Simpan Draft sengaja tanpa batasan sama sekali — boleh kosong semua, supaya
                // user bisa menyimpan progres kapan saja. Kelengkapan hanya diwajibkan saat
                // "Selesai & Simpan". Dipakai bareng dengan penanda hijau di stepper (lihat
                // stepConfigs/isStepComplete di bawah) — SATU sumber kebenaran yang sama,
                // supaya modal review tidak pernah bilang "lengkap" padahal stepper-nya
                // sendiri masih ada yang belum hijau (atau sebaliknya).
                if (statusDraft !== 'selesai') return true;
                return isStepComplete('step-umum') && isStepComplete('step-spd')
                    && isStepComplete('step-rincian') && isStepComplete('step-laporan');
            }

            function openReviewModal(statusDraft) {
                pendingStatusDraft = statusDraft;
                renderReview();
                const lengkap = isDataWajibLengkap(statusDraft);
                document.getElementById('modal-review-status').innerHTML = statusDraft !== 'selesai'
                    ? '<div class="alert alert-secondary d-flex align-items-center gap-2 mb-3"><i class="ti ti-device-floppy"></i> Draft bisa disimpan kapan saja, walau belum semua data terisi.</div>'
                    : (lengkap
                        ? '<div class="alert alert-success d-flex align-items-center gap-2 mb-3"><i class="ti ti-circle-check"></i> Semua data wajib sudah lengkap.</div>'
                        : '<div class="alert alert-warning d-flex align-items-center gap-2 mb-3"><i class="ti ti-alert-triangle"></i> Ada data wajib yang belum lengkap. Lengkapi dulu sebelum menyimpan.</div>');
                const confirmBtn = document.getElementById('modal-review-confirm');
                confirmBtn.disabled = !lengkap;
                confirmBtn.classList.remove('btn-warning', 'btn-primary');
                confirmBtn.classList.add(statusDraft === 'selesai' ? 'btn-primary' : 'btn-warning');
                document.getElementById('modal-review-confirm-label').textContent =
                    statusDraft === 'selesai' ? 'Selesai & Simpan' : 'Simpan sebagai Draft';
                if (reviewModal) reviewModal.show();
            }

            const btnSimpanDraft = document.getElementById('btn-simpan-draft');
            const btnSelesai = document.getElementById('btn-selesai');
            if (btnSimpanDraft) btnSimpanDraft.addEventListener('click', function () { openReviewModal('draft_sesi1'); });
            if (btnSelesai) btnSelesai.addEventListener('click', function () { openReviewModal('selesai'); });

            const modalReviewConfirm = document.getElementById('modal-review-confirm');
            if (modalReviewConfirm) {
                modalReviewConfirm.addEventListener('click', function () {
                    statusDraftInput.value = pendingStatusDraft;
                    if (reviewModal) reviewModal.hide();
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                });
            }

            // ----- No. Surat Tugas: format otomatis B-{angka}/62130/KU.340/{tahun} -----
            const noSuratTugasAngkaInput = document.getElementById('input-no_surat_tugas_angka');
            const noSuratTugasHidden = document.getElementById('input-no_surat_tugas');
            const tanggalSuratTugasInput = document.getElementById('input-tanggal_surat_tugas');
            const suffixNoSuratTugas = document.getElementById('suffix-no_surat_tugas');

            function updateNoSuratTugas() {
                const angka = (noSuratTugasAngkaInput.value || '').trim();
                const tahun = (tanggalSuratTugasInput.value || '').slice(0, 4) || String(new Date().getFullYear());
                const suffix = '/62130/KU.340/' + tahun;
                suffixNoSuratTugas.textContent = suffix;
                if (angka) {
                    noSuratTugasHidden.value = 'B-' + angka + suffix;
                } else {
                    noSuratTugasHidden.value = '';
                }
                noSuratTugasHidden.dispatchEvent(new Event('input', { bubbles: true }));
            }
            noSuratTugasAngkaInput.addEventListener('input', updateNoSuratTugas);
            tanggalSuratTugasInput.addEventListener('change', updateNoSuratTugas);
            updateNoSuratTugas();

            // ----- Semua tanggal pakai Flatpickr (dulu tanggal tunggal pakai input
            // type="date" bawaan browser, rentang pakai Bootstrap Daterangepicker —
            // disatukan biar tampilan & UX kalendernya konsisten di seluruh wizard). -----
            const flatpickrLocaleId = {
                weekdays: {
                    shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                },
                months: {
                    shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                },
                rangeSeparator: ' s/d ',
                firstDayOfWeek: 1,
            };

            // Tanggal tunggal: input aslinya (dikirim ke server) format Y-m-d, tapi yang
            // ditampilkan ke user format d-m-Y lewat altInput — trik standar Flatpickr
            // biar tampilannya enak dibaca tanpa mengubah format data yang disimpan.
            function pasangFlatpickrTunggal(id) {
                const el = document.getElementById(id);
                if (!el) return;
                flatpickr(el, {
                    locale: flatpickrLocaleId,
                    altInput: true,
                    altFormat: 'd-m-Y',
                    dateFormat: 'Y-m-d',
                    allowInput: true,
                });
            }
            pasangFlatpickrTunggal('input-tanggal_surat_tugas');
            pasangFlatpickrTunggal('input-tanggal_spd');
            pasangFlatpickrTunggal('input-tanggal_kuitansi');

            // Tanggal Mulai - Selesai: 1 kotak Flatpickr mode "range", hasilnya
            // disinkronkan ke 2 input hidden (tanggal_mulai/tanggal_selesai) yang
            // sebenarnya dikirim ke server & dipakai di seluruh script ini.
            (function hubungkanRentangTanggal() {
                const rangeEl = document.getElementById('input-rentang_tanggal');
                const hiddenMulai = document.getElementById('input-tanggal_mulai');
                const hiddenSelesai = document.getElementById('input-tanggal_selesai');
                if (!rangeEl || !hiddenMulai || !hiddenSelesai) return;

                const defaultDate = (hiddenMulai.value && hiddenSelesai.value)
                    ? [hiddenMulai.value, hiddenSelesai.value] : undefined;

                flatpickr(rangeEl, {
                    mode: 'range',
                    locale: flatpickrLocaleId,
                    dateFormat: 'd-m-Y',
                    defaultDate: defaultDate,
                    onChange: function (selectedDates) {
                        if (selectedDates.length === 2) {
                            hiddenMulai.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                            hiddenSelesai.value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                        } else {
                            hiddenMulai.value = '';
                            hiddenSelesai.value = '';
                        }
                        hiddenMulai.dispatchEvent(new Event('input', { bubbles: true }));
                        hiddenSelesai.dispatchEvent(new Event('input', { bubbles: true }));
                    },
                });
            })();

            // ----- Validasi real-time per step: tandai bulatan step di stepper jadi
            // hijau + centang begitu semua field wajibnya lengkap, dan tampilkan pesan
            // error di bawah field yang masih kosong (baru muncul setelah field-nya
            // "disentuh" user, bukan langsung pas halaman dibuka). Daftar field wajib di
            // sini SENGAJA sama dengan isDataWajibLengkap/validationRules() saat
            // status_draft = selesai — ini murni indikator visual, draft tetap boleh
            // disimpan walau belum lengkap. -----
            // Sengaja TIDAK dibungkus IIFE — beberapa step (Pengeluaran Riil, Kuitansi,
            // Pernyataan, Laporan) butuh dipantau dari kode lain di bawah (baris
            // pengeluaran yang ditambah/dihapus dinamis, file dokumentasi, dst), jadi
            // updateStepperStatus/isStepComplete perlu bisa dipanggil dari luar.
            const butuhSpd = @json($butuhSpd);
            const jenisPerjadin = @json($jenis);
            const touched = {};

            function isKosong(id) {
                const el = document.getElementById(id);
                if (!el || !el.value) return true;
                // "__lainnya__" itu sentinel sementara (lihat hubungkanJenisKegiatanLainnya)
                // — dropdown-nya kelihatan "terisi" tapi belum jadi id_jenis_kegiatan yang
                // valid sebelum user klik "Tambah", jadi tetap dihitung kosong.
                return el.value === '__lainnya__';
            }

            // Perihal & Pembebanan bisa ke-isi otomatis dari saran Jenis Kegiatan (lihat
            // listener 'change' input-jenis_kegiatan) — nilai saran itu masih ada bagian
            // "[]" yang perlu disunting sendiri sama user, jadi jangan dihitung "terisi"
            // buat penanda hijau/lengkap sebelum user benar-benar menyunting sendiri
            // (flag data-autofilled kehapus begitu user mengetik, lihat listener 'input'
            // semuaFieldWajib di bawah).
            const fieldBisaAutofill = ['input-perihal', 'input-pembebanan'];
            function confirmedOk(id) {
                const el = document.getElementById(id);
                return !!(el && el.value) && el.dataset.autofilled !== '1';
            }

            // Rincian Biaya: cuma Uang Harian yang wajib — Transport & Penginapan boleh
            // kosong (mis. perjalanan dalam kota yang tidak butuh keduanya).
            function rincianOk() {
                return (parseFloat(val('input-rincian-uang_harian')) || 0) > 0;
            }

            // Pengeluaran Riil boleh kosong semua (opsional) — tapi begitu ada minimal 1
            // baris yang nominalnya sungguhan diisi (> 0), step ini ditandai hijau juga
            // sebagai indikator positif, bukan syarat wajib.
            function pengeluaranOk() {
                return Array.from(document.querySelectorAll('#pengeluaran-rows .pengeluaran-input[type=number]'))
                    .some(function (el) { return (parseFloat(el.value) || 0) > 0; });
            }

            // Surat Pernyataan opsional juga — tapi kalau checkbox "Keterlambatan" dicentang,
            // keterangannya jangan sampai dibiarkan kosong (nanti di dokumen jadi kalimat
            // gantung / titik-titik yang tidak terisi).
            // Field-field yang BENERAN muncul sebagai "titik-titik" placeholder di dokumen
            // Surat Pernyataan (lihat suratPernyataanBlock & blok dalam_kota_lebih_8_jam di
            // dokumen-perjadin.js: Nama/NIP dari Pelaksana, No. & Tanggal Surat Tugas,
            // Perihal, Tanggal Mulai-Selesai) — BUKAN seluruh field Data Umum (Desa/Kel.
            // Asal, Pembebanan, dst tidak pernah ditampilkan di dokumen ini, jadi tidak
            // relevan buat penanda hijau step ini).
            function pernyataanOk() {
                const fieldTerpakai = ['input-pelaksana', 'input-no_surat_tugas', 'input-tanggal_surat_tugas',
                    'input-perihal', 'input-tanggal_mulai', 'input-tanggal_selesai'];
                if (fieldTerpakai.some(function (id) { return isKosong(id); })) return false;

                const cbKeterlambatan = document.getElementById('input-pernyataan-keterlambatan');
                if (cbKeterlambatan && cbKeterlambatan.checked && isKosong('input-pernyataan-keterlambatan_keterangan')) {
                    return false;
                }
                return true;
            }

            const stepConfigs = {
                'step-umum': {
                    // "biasa" (antar kabupaten/kota) tidak pakai Desa/Kel. asal & tujuan
                    // (fieldnya tidak ditampilkan sama sekali di step Data Umum untuk jenis
                    // ini) — cukup Kabupaten/Kota sebagai wilayah acuan.
                    fields: ['input-no_surat_tugas', 'input-tanggal_surat_tugas', 'input-jenis_kegiatan',
                        'input-pelaksana', 'input-tanggal_mulai', 'input-tanggal_selesai',
                        'input-kabupaten_asal', 'input-kabupaten_tujuan']
                        .concat(jenisPerjadin === 'biasa' ? [] : ['input-desa_asal', 'input-desa_tujuan']),
                    extraOk: function () { return confirmedOk('input-perihal') && confirmedOk('input-pembebanan'); },
                },
                'step-spd': { fields: butuhSpd ? ['input-no_spd', 'input-tanggal_spd', 'input-angkutan'] : [] },
                'step-rincian': { fields: [], extraOk: rincianOk },
                'step-pengeluaran': { fields: [], extraOk: pengeluaranOk },
                // Kuitansi tidak punya field sendiri (Tanggal Kuitansi diisi di step Data
                // Umum) — statusnya murni ikut 3 step inti (Data Umum, SPD, Rincian Biaya).
                'step-kuitansi': {
                    fields: [],
                    extraOk: function () {
                        return isStepComplete('step-umum') && isStepComplete('step-spd') && isStepComplete('step-rincian');
                    },
                },
                // Surat Pernyataan: beda dari Kuitansi (yang tidak punya field sendiri
                // sama sekali) — di sini kelengkapannya murni dari kondisi internalnya
                // sendiri (checkbox + keterangan keterlambatan kalau dicentang), tidak
                // ikut-ikutan status 3 step inti.
                'step-pernyataan': { fields: [], extraOk: pernyataanOk },
                // Laporan: semua field-nya wajib (beda dari step lain yang sebagian besar
                // opsional) — Kesimpulan, Tindak Lanjut, dan minimal 1 file Dokumentasi.
                'step-laporan': {
                    fields: ['input-kesimpulan', 'input-tindak_lanjut'],
                    extraOk: function () { return dokumentasiGabungan().length > 0; },
                },
            };

            function isStepComplete(stepId) {
                const config = stepConfigs[stepId];
                if (!config) return false;
                return config.fields.every(function (id) { return !isKosong(id); })
                    && (config.extraOk ? config.extraOk() : true);
            }

            function perbaruiPesanError(id) {
                const errEl = document.getElementById('err-' + id);
                if (!errEl) return;
                const belumOk = fieldBisaAutofill.indexOf(id) !== -1 ? !confirmedOk(id) : isKosong(id);
                errEl.classList.toggle('show', !!touched[id] && belumOk);
            }

            function updateStepperStatus() {
                Object.keys(stepConfigs).forEach(function (stepId) {
                    const stepNav = stepperEl.querySelector('.step[data-target="#' + stepId + '"]');
                    if (stepNav) stepNav.classList.toggle('step-complete', isStepComplete(stepId));
                });
                const errRincian = document.getElementById('err-rincian-total');
                if (errRincian) errRincian.classList.toggle('show', !!touched['rincian-total'] && !rincianOk());
                const errLaporanDokumentasi = document.getElementById('err-laporan-dokumentasi');
                if (errLaporanDokumentasi) {
                    errLaporanDokumentasi.classList.toggle('show', !!touched['laporan-dokumentasi'] && !stepConfigs['step-laporan'].extraOk());
                }
                fieldBisaAutofill.forEach(perbaruiPesanError);
            }

            // Semua field wajib dipantau lewat event 'input' — dipilih karena satu ini
            // konsisten dipakai di seluruh script buat sinkronisasi hidden input (select2,
            // flatpickr, dst selalu dispatchEvent('input') pas nilainya berubah, lihat
            // hubungkanSelectDesa/hubungkanRentangTanggal), jadi otomatis kepantau juga
            // baik dari interaksi user langsung maupun dari sinkronisasi terprogram.
            const semuaFieldWajib = Object.keys(stepConfigs).reduce(function (acc, stepId) {
                return acc.concat(stepConfigs[stepId].fields);
            }, []).concat(fieldBisaAutofill);
            semuaFieldWajib.forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('input', function (e) {
                    // isTrusted membedakan ketikan asli user vs event 'input' yang
                    // di-dispatch terprogram (mis. oleh listener auto-isi Jenis Kegiatan
                    // sendiri buat memicu validasi ulang) — kalau tidak dibedakan, flag
                    // data-autofilled yang baru saja dipasang auto-isi langsung kehapus
                    // lagi oleh listener ini di event yang sama.
                    if (fieldBisaAutofill.indexOf(id) !== -1 && e.isTrusted) delete el.dataset.autofilled;
                    touched[id] = true;
                    perbaruiPesanError(id);
                    updateStepperStatus();
                });
            });
            document.querySelectorAll('.rincian-input').forEach(function (el) {
                el.addEventListener('input', function () {
                    touched['rincian-total'] = true;
                    updateStepperStatus();
                });
            });
            document.getElementById('input-pernyataan-keterlambatan_keterangan').addEventListener('input', function () {
                updateStepperStatus();
            });

            // ----- Desa/Kel. Asal & Tujuan: dropdown multi-select per kecamatan (select2,
            // lihat forms-selects.js) — hasil pilihan digabung "a, b, dan c" ke input
            // hidden desa_asal/desa_tujuan yang sebenarnya dikirim ke server. -----
            function formatDaftarDenganDan(items) {
                if (!items || items.length === 0) return '';
                if (items.length === 1) return items[0];
                return items.slice(0, -1).join(', ') + ', dan ' + items[items.length - 1];
            }

            function parseDaftarDenganDan(str) {
                if (!str) return [];
                return str.replace(/,?\s*dan\s+/i, ', ').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            }

            // ----- Rekomendasi nilai Akomodasi (Penginapan) berdasarkan kecamatan tujuan —
            // rate_akomodasi cuma mencakup kecamatan di Kabupaten Murung Raya sendiri, jadi
            // tidak tampil untuk tujuan luar kabupaten. -----
            function kecamatanTujuanTerpilih() {
                if (val('input-kabupaten_tujuan') !== 'Kabupaten Murung Raya') return null;
                const daftar = parseDaftarDenganDan(val('input-desa_tujuan'));
                if (!daftar.length) return null;
                const namaDesa = daftar[0].replace(/^(Desa|Kelurahan)\s+/, '');
                return desaKeKecamatan[namaDesa] || null;
            }

            function perbaruiRekomendasiAkomodasi() {
                const hintEl = document.getElementById('hint-rekomendasi-akomodasi');
                const inputPenginapan = document.getElementById('input-rincian-penginapan');
                if (!hintEl || !inputPenginapan) return;

                const kecamatan = kecamatanTujuanTerpilih();
                const tarif = kecamatan ? rateAkomodasiByKecamatan[kecamatan] : null;
                if (!tarif) {
                    hintEl.innerHTML = '';
                    return;
                }

                const lama = DokumenPerjadin.hitungLamaHari(val('input-tanggal_mulai'), val('input-tanggal_selesai'));
                const malam = parseInt(val('input-rincian_hari-penginapan'), 10) || Math.max(lama - 1, 0);
                const totalIlustrasi = tarif * Math.max(malam, 1);

                // "Pakai" mengisi rate per malam-nya saja (bukan total) — field Penginapan
                // adalah rate, totalnya dihitung otomatis (rate x jumlah malam) di preview.
                hintEl.innerHTML = 'Rekomendasi (Kec. ' + kecamatan + '): ' + DokumenPerjadin.fmtRupiah(tarif) + '/malam &times; ' + Math.max(malam, 1)
                    + ' = <strong>' + DokumenPerjadin.fmtRupiah(totalIlustrasi) + '</strong> '
                    + '<button type="button" id="btn-pakai-rekomendasi-akomodasi" class="btn btn-link btn-sm p-0 align-baseline">Pakai</button>';

                document.getElementById('btn-pakai-rekomendasi-akomodasi').addEventListener('click', function () {
                    inputPenginapan.value = tarif;
                    inputPenginapan.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }

            // ----- Rekomendasi rate Uang Harian — satuan yang umum dipakai Rp 140.000/hari. -----
            const RATE_REKOMENDASI_UANG_HARIAN = 140000;
            function perbaruiRekomendasiUangHarian() {
                const hintEl = document.getElementById('hint-rekomendasi-uang-harian');
                const inputUangHarian = document.getElementById('input-rincian-uang_harian');
                if (!hintEl || !inputUangHarian) return;

                hintEl.innerHTML = 'Rekomendasi: ' + DokumenPerjadin.fmtRupiah(RATE_REKOMENDASI_UANG_HARIAN) + '/hari '
                    + '<button type="button" id="btn-pakai-rekomendasi-uang-harian" class="btn btn-link btn-sm p-0 align-baseline">Pakai</button>';

                document.getElementById('btn-pakai-rekomendasi-uang-harian').addEventListener('click', function () {
                    inputUangHarian.value = RATE_REKOMENDASI_UANG_HARIAN;
                    inputUangHarian.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }

            // ----- Peringatan Pengeluaran Riil (transportasi) yang melebihi rate transport
            // resmi untuk desa/kecamatan tujuan. Tujuan bisa lebih dari satu desa (multi-select),
            // jadi batasnya dijumlah per desa (masing-masing dari moda dengan nilai tertinggi,
            // supaya tidak salah tandai padahal masih dalam rate moda tertentu) — bukan diambil
            // yang terbesar saja, karena user memang bisa transit lewat beberapa desa sekaligus. -----
            function batasTransportTujuan() {
                let batas = 0;
                parseDaftarDenganDan(val('input-desa_tujuan')).forEach(function (label) {
                    const namaWilayah = label.replace(/^(Desa|Kelurahan)\s+/, '');
                    if (rateTransportMaxByWilayah[namaWilayah]) {
                        batas += parseFloat(rateTransportMaxByWilayah[namaWilayah]);
                    }
                });
                return batas || null;
            }

            function periksaPeringatanTransport() {
                const warningEl = document.getElementById('warning-transport-agregat');
                if (!warningEl) return;

                const batas = batasTransportTujuan();
                let totalTransportasi = 0;
                document.querySelectorAll('#pengeluaran-rows > div').forEach(function (row) {
                    const select = row.querySelector('select');
                    const nominalInput = row.querySelector('input[type=number]');
                    if (!select || !nominalInput || select.value !== 'transportasi') return;
                    totalTransportasi += parseFloat(nominalInput.value) || 0;
                });

                const melebihi = batas && totalTransportasi > batas;
                warningEl.classList.toggle('d-none', !melebihi);
                if (melebihi) {
                    warningEl.querySelector('div').textContent = 'Total Pengeluaran Riil Biaya Transportasi ('
                        + DokumenPerjadin.fmtRupiah(totalTransportasi) + ') melebihi rate transport resmi untuk tujuan ini (maks '
                        + DokumenPerjadin.fmtRupiah(batas) + ').';
                }
            }

            function hubungkanSelectDesa(selectId, hiddenId) {
                const $select = $('#' + selectId);
                const hidden = document.getElementById(hiddenId);
                if (!$select.length || !hidden) return;

                if (hidden.value) {
                    $select.val(parseDaftarDenganDan(hidden.value));
                }

                $select.on('change', function () {
                    hidden.value = formatDaftarDenganDan($select.val() || []);
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                });

                $select.trigger('change');
            }
            hubungkanSelectDesa('input-desa_asal_pilih', 'input-desa_asal');
            hubungkanSelectDesa('input-desa_tujuan_pilih', 'input-desa_tujuan');

            // ----- Kabupaten/Kota Asal & Tujuan: dropdown, disable+terkunci "Kabupaten Murung
            // Raya" untuk field disabled tetap perlu di-sync (bukan dikirim browser). -----
            function hubungkanSelectKabupaten(selectId, hiddenId) {
                const select = document.getElementById(selectId);
                const hidden = document.getElementById(hiddenId);
                if (!select || !hidden) return;
                if (select.disabled) return; // sudah dikunci server-side, hidden sudah benar
                // select2 memicu 'change' lewat jQuery — .trigger('change') pada <select>
                // (tidak punya method .change() native) cuma manggil handler yang
                // didaftarkan lewat $.fn.on, bukan addEventListener biasa (lihat juga
                // hubungkanPelaksana untuk kasus serupa).
                $(select).on('change', function () {
                    hidden.value = select.value;
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }
            hubungkanSelectKabupaten('input-kabupaten_asal_pilih', 'input-kabupaten_asal');
            hubungkanSelectKabupaten('input-kabupaten_tujuan_pilih', 'input-kabupaten_tujuan');

            // ----- Alat Angkut: dropdown preset (Kendaraan Umum/Air/Darat), opsi "Lainnya"
            // memunculkan input teks manual — keduanya disatukan ke hidden input "angkutan". -----
            (function hubungkanAngkutan() {
                const select = document.getElementById('input-angkutan_pilih');
                const hidden = document.getElementById('input-angkutan');
                const wrapperLainnya = document.getElementById('wrapper-angkutan_lainnya');
                const inputLainnya = document.getElementById('input-angkutan_lainnya');
                if (!select || !hidden || !wrapperLainnya || !inputLainnya) return;

                const preset = ['Kendaraan Umum', 'Kendaraan Air', 'Kendaraan Darat'];
                const nilaiAwal = hidden.value || '';
                if (nilaiAwal && preset.includes(nilaiAwal)) {
                    select.value = nilaiAwal;
                } else if (nilaiAwal) {
                    select.value = '__lainnya__';
                    inputLainnya.value = nilaiAwal;
                    wrapperLainnya.style.display = '';
                }

                select.addEventListener('change', function () {
                    if (select.value === '__lainnya__') {
                        wrapperLainnya.style.display = '';
                        hidden.value = inputLainnya.value;
                        inputLainnya.focus();
                    } else {
                        wrapperLainnya.style.display = 'none';
                        hidden.value = select.value;
                    }
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                });

                inputLainnya.addEventListener('input', function () {
                    hidden.value = inputLainnya.value;
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                });
            })();

            // ----- Pelaksana: pilih jenis (Pegawai BPS / Mitra) dulu, baru dropdown nama
            // pelaksana (select2 basic) yang daftarnya mengikuti jenis terpilih. ID pelaksana
            // yang benar-benar divalidasi/disimpan tetap disatukan ke hidden input "input-pelaksana". -----
            (function hubungkanPelaksana() {
                const jenisSelect = document.getElementById('input-jenis_pelaksana');
                const selectPegawai = document.getElementById('input-pelaksana_pegawai');
                const selectMitra = document.getElementById('input-pelaksana_mitra');
                const wrapperPegawai = document.getElementById('wrapper-pelaksana_pegawai');
                const wrapperMitra = document.getElementById('wrapper-pelaksana_mitra');
                const hidden = document.getElementById('input-pelaksana');
                if (!jenisSelect || !selectPegawai || !selectMitra || !hidden) return;

                function kosongkanHidden() {
                    hidden.value = '';
                    hidden.dataset.nama = '';
                    hidden.dataset.nip = '';
                    hidden.dataset.jabatan = '';
                    hidden.dataset.pangkat = '';
                    hidden.dataset.golongan = '';
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                }

                function salinDariOpsiTerpilih(select) {
                    const opt = select.options[select.selectedIndex];
                    if (!opt || !opt.value) {
                        kosongkanHidden();
                        return;
                    }
                    hidden.value = opt.value;
                    hidden.dataset.nama = opt.text.trim();
                    hidden.dataset.nip = opt.dataset.nip || '';
                    hidden.dataset.jabatan = opt.dataset.jabatan || '';
                    hidden.dataset.pangkat = opt.dataset.pangkat || '';
                    hidden.dataset.golongan = opt.dataset.golongan || '';
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                }

                function tampilkanSesuaiJenis() {
                    const jenis = jenisSelect.value;
                    if (wrapperPegawai) wrapperPegawai.style.display = jenis === 'pegawai' ? '' : 'none';
                    if (wrapperMitra) wrapperMitra.style.display = jenis === 'mitra' ? '' : 'none';
                }

                // select2 trigger 'change'-nya lewat jQuery, bukan native DOM event — jQuery
                // .trigger('change') pada elemen tanpa method .change() bawaan (semua <select>)
                // HANYA memanggil handler yang didaftarkan lewat $.fn.on, bukan addEventListener
                // biasa. Makanya listener select2 di sini juga harus didaftarkan lewat $().on(). -----
                jenisSelect.addEventListener('change', function () {
                    tampilkanSesuaiJenis();
                    $(selectPegawai).val('').trigger('change');
                    $(selectMitra).val('').trigger('change');
                    kosongkanHidden();
                });

                $(selectPegawai).on('change', function () { salinDariOpsiTerpilih(selectPegawai); });
                $(selectMitra).on('change', function () { salinDariOpsiTerpilih(selectMitra); });

                tampilkanSesuaiJenis();
                if (jenisSelect.value === 'pegawai' && hidden.value) {
                    salinDariOpsiTerpilih(selectPegawai);
                } else if (jenisSelect.value === 'mitra' && hidden.value) {
                    salinDariOpsiTerpilih(selectMitra);
                }
            })();

            // ----- SPD: tombol ambil nomor -----
            // SPD wajib untuk jenis "biasa" maupun "dalam kota > 8 jam" (diperlakukan sama).
            const noSpdInput = document.getElementById('input-no_spd');
            const tanggalSpdInput = document.getElementById('input-tanggal_spd');
            const btnAmbilNomorSpd = document.getElementById('btn-ambil-nomor-spd');

            if (btnAmbilNomorSpd) {
                btnAmbilNomorSpd.addEventListener('click', function () {
                    const tahunSumber = (tanggalSpdInput && tanggalSpdInput.value) || document.getElementById('input-tanggal_mulai')?.value || '';
                    const tahun = tahunSumber.slice(0, 4);
                    const url = '{{ route('perjalanan-dinas.nomor-spd-berikutnya') }}' + (tahun ? '?tahun=' + encodeURIComponent(tahun) : '');
                    btnAmbilNomorSpd.disabled = true;
                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (noSpdInput && data.no_spd) {
                                noSpdInput.value = data.no_spd;
                                noSpdInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        })
                        .finally(function () {
                            btnAmbilNomorSpd.disabled = false;
                        });
                });
            }

            // ----- Helpers (baca form) -----
            function val(id) {
                const el = document.getElementById(id);
                return el ? (el.value || '') : '';
            }

            function selText(id) {
                const el = document.getElementById(id);
                if (!el || el.selectedIndex < 0) return '';
                const opt = el.options[el.selectedIndex];
                return opt ? opt.text.trim() : '';
            }

            function pegawaiData(id) {
                const el = document.getElementById(id);
                const empty = { nama: '', nip: '', jabatan: '', pangkat: '', golongan: '' };
                if (!el) return empty;

                // "input-pelaksana" bukan <select> lagi (lihat hubungkanPelaksana di atas) —
                // datanya disalin ke data-attribute hidden input itu sendiri, bukan opsi terpilih.
                if (el.tagName !== 'SELECT') {
                    if (!el.value) return empty;
                    return {
                        nama: el.dataset.nama || '',
                        nip: el.dataset.nip || '',
                        jabatan: el.dataset.jabatan || '',
                        pangkat: el.dataset.pangkat || '',
                        golongan: el.dataset.golongan || '',
                    };
                }

                if (el.selectedIndex < 0) return empty;
                const opt = el.options[el.selectedIndex];
                if (!opt || !opt.value) return empty;
                return {
                    nama: opt.text.trim(),
                    nip: opt.dataset.nip || '',
                    jabatan: opt.dataset.jabatan || '',
                    pangkat: opt.dataset.pangkat || '',
                    golongan: opt.dataset.golongan || '',
                };
            }

            // Kumpulkan seluruh isi form jadi objek data, dipakai oleh modul DokumenPerjadin (window.DokumenPerjadin) yang sama dengan modal Lihat/Export di dashboard.
            function collectFormData() {
                const rincian = ['uang_harian', 'transport', 'penginapan'].map(function (jenis) {
                    return {
                        jenis_komponen: jenis,
                        jumlah_hari: val('input-rincian_hari-' + jenis) || null,
                        nominal: val('input-rincian-' + jenis) || 0,
                    };
                });
                const pengeluaran = Array.from(document.querySelectorAll('#pengeluaran-rows > div')).map(function (row) {
                    return {
                        jenis: row.querySelector('select').value,
                        uraian: row.querySelector('.pengeluaran-uraian').value,
                        nominal: row.querySelector('input[type=number]').value,
                    };
                });
                const pernyataan = ['tidak_pakai_kendaraan_dinas', 'tidak_menginap_hotel', 'keterlambatan']
                    .filter(function (jenis) { return document.getElementById('input-pernyataan-' + jenis).checked; })
                    .map(function (jenis) {
                        return {
                            jenis_kondisi: jenis,
                            keterangan: jenis === 'keterlambatan' ? val('input-pernyataan-keterlambatan_keterangan') : '',
                        };
                    });

                return {
                    no_surat_tugas: val('input-no_surat_tugas'),
                    tanggal_surat_tugas: val('input-tanggal_surat_tugas'),
                    perihal: val('input-perihal'),
                    uraian_tugas: val('input-uraian_tugas'),
                    tanggal_mulai: val('input-tanggal_mulai'),
                    tanggal_selesai: val('input-tanggal_selesai'),
                    pembebanan: val('input-pembebanan'),
                    jenis_perjadin: @json($jenis),
                    tingkat_biaya: val('input-tingkat_biaya'),
                    desa_asal: val('input-desa_asal'),
                    kabupaten_asal: val('input-kabupaten_asal'),
                    desa_tujuan: val('input-desa_tujuan'),
                    kabupaten_tujuan: val('input-kabupaten_tujuan'),
                    no_spd: val('input-no_spd'),
                    tanggal_spd: val('input-tanggal_spd'),
                    angkutan: val('input-angkutan'),
                    tanggal_kuitansi: val('input-tanggal_kuitansi'),
                    jenis_kegiatan_nama: selText('input-jenis_kegiatan'),
                    kesimpulan_hasil_kegiatan: val('input-kesimpulan'),
                    tindak_lanjut: val('input-tindak_lanjut'),
                    pelaksana: pegawaiData('input-pelaksana'),
                    penandatangan: pegawaiData('input-penandatangan_st'),
                    ppk: pegawaiData('input-ppk'),
                    bendahara: pegawaiData('input-bendahara'),
                    rincian: rincian,
                    pengeluaran: pengeluaran,
                    pernyataan: pernyataan,
                    dokumentasi: dokumentasiGabungan(),
                };
            }

            // ----- Auto-isi dari Jenis Kegiatan -----
            const jenisKegiatanSelect = document.getElementById('input-jenis_kegiatan');
            if (jenisKegiatanSelect) {
                jenisKegiatanSelect.addEventListener('change', function () {
                    const opt = jenisKegiatanSelect.options[jenisKegiatanSelect.selectedIndex];
                    if (!opt) return;
                    const uraianTugas = document.getElementById('input-uraian_tugas');
                    const perihal = document.getElementById('input-perihal');
                    const pembebanan = document.getElementById('input-pembebanan');
                    const kesimpulan = document.getElementById('input-kesimpulan');
                    if (uraianTugas && !uraianTugas.value && opt.dataset.uraianTugas) {
                        uraianTugas.value = opt.dataset.uraianTugas;
                    }
                    if (perihal && !perihal.value && opt.dataset.perihal) {
                        // Sama seperti pembebanan: cuma saran awal, isi "[]"-nya (nama
                        // survei) masih perlu disunting sendiri oleh user — jangan
                        // dihitung "terisi" buat penanda hijau di stepper sebelum itu.
                        perihal.value = opt.dataset.perihal;
                        perihal.dataset.autofilled = '1';
                        perihal.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (pembebanan && !pembebanan.value && opt.dataset.mak) {
                        pembebanan.value = opt.dataset.mak;
                        // Ditandai "belum dikonfirmasi user" — sekadar saran awal dari
                        // Jenis Kegiatan, bukan berarti sudah benar buat perjalanan dinas
                        // ini. Ditandai green di stepper baru kalau user sudah benar-benar
                        // mengetik/menyunting sendiri (lihat listener 'input' pembebanan
                        // di updateStepperStatus).
                        pembebanan.dataset.autofilled = '1';
                        pembebanan.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (kesimpulan && !kesimpulan.value && opt.dataset.uraianLaporan) {
                        kesimpulan.value = opt.dataset.uraianLaporan;
                    }
                });
            }

            // ----- Jenis Kegiatan "Lainnya...": simpan jenis kegiatan baru ke master data
            // (lewat AJAX, bukan cuma teks lokal) — id_jenis_kegiatan itu foreign key,
            // butuh baris sungguhan di tabel jenis_kegiatan supaya validasi server lolos,
            // dan langsung tersedia buat dipilih lagi di perjalanan dinas berikutnya. -----
            (function hubungkanJenisKegiatanLainnya() {
                const wrapperLainnya = document.getElementById('wrapper-jenis_kegiatan_lainnya');
                const inputLainnya = document.getElementById('input-jenis_kegiatan_lainnya');
                const btnTambah = document.getElementById('btn-tambah-jenis_kegiatan');
                if (!jenisKegiatanSelect || !wrapperLainnya || !inputLainnya || !btnTambah) return;

                jenisKegiatanSelect.addEventListener('change', function () {
                    wrapperLainnya.style.display = jenisKegiatanSelect.value === '__lainnya__' ? '' : 'none';
                    if (jenisKegiatanSelect.value === '__lainnya__') inputLainnya.focus();
                });

                btnTambah.addEventListener('click', function () {
                    const nama = inputLainnya.value.trim();
                    if (!nama) {
                        inputLainnya.focus();
                        return;
                    }
                    const labelAsli = btnTambah.textContent;
                    btnTambah.disabled = true;
                    btnTambah.textContent = 'Menyimpan...';

                    fetch('{{ route('perjalanan-dinas.jenis-kegiatan.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ nama_kegiatan: nama }),
                    })
                        .then(function (res) { if (!res.ok) throw new Error('gagal simpan'); return res.json(); })
                        .then(function (data) {
                            // Kalau id-nya sudah ada di opsi (dipilih ulang nama yang sama
                            // persis dengan yang sudah pernah ditambahkan), tinggal pilih —
                            // jangan bikin opsi dobel.
                            let opt = Array.from(jenisKegiatanSelect.options).find(function (o) {
                                return o.value === String(data.id_jenis_kegiatan);
                            });
                            if (!opt) {
                                opt = document.createElement('option');
                                opt.value = data.id_jenis_kegiatan;
                                opt.textContent = data.nama_kegiatan;
                                jenisKegiatanSelect.insertBefore(opt, jenisKegiatanSelect.querySelector('option[value="__lainnya__"]'));
                            }
                            jenisKegiatanSelect.value = String(data.id_jenis_kegiatan);
                            jenisKegiatanSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            wrapperLainnya.style.display = 'none';
                            inputLainnya.value = '';
                        })
                        .catch(function () {
                            alert('Gagal menyimpan jenis kegiatan baru. Coba lagi.');
                        })
                        .finally(function () {
                            btnTambah.disabled = false;
                            btnTambah.textContent = labelAsli;
                        });
                });
            })();

            // ----- Auto-isi Penandatangan ST / PPK / Bendahara berdasarkan peran_pejabat
            // pegawai (field khusus di Kelola Pegawai, bukan tebak-tebak dari teks jabatan
            // struktural — jabatan "Statistisi Ahli Pertama" misalnya bisa saja PPK). -----
            function autoSelectByPeran(selectId, peran) {
                const select = document.getElementById(selectId);
                if (!select || select.value) return;
                const match = Array.from(select.options).find(function (opt) {
                    return opt.dataset.peran === peran;
                });
                if (match) {
                    select.value = match.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            autoSelectByPeran('input-penandatangan_st', 'kepala_satker');
            autoSelectByPeran('input-ppk', 'ppk');
            autoSelectByPeran('input-bendahara', 'bendahara');

            // ----- Pengeluaran Riil -----
            let pengeluaranIndex = 0;
            const pengeluaranRowsEl = document.getElementById('pengeluaran-rows');
            const pengeluaranTemplate = document.getElementById('pengeluaran-row-template');
            const pengeluaranEmptyEl = document.getElementById('pengeluaran-empty');

            function addPengeluaranRow(data) {
                data = data || {};
                const idx = pengeluaranIndex++;
                const html = pengeluaranTemplate.innerHTML.replace(/__INDEX__/g, idx);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const rowEl = wrapper.firstElementChild;
                if (data.jenis) rowEl.querySelector('select').value = data.jenis;
                if (data.uraian) rowEl.querySelector('.pengeluaran-uraian').value = data.uraian;
                if (data.nominal) rowEl.querySelector('input[type=number]').value = data.nominal;
                rowEl.querySelector('input[type=number]').addEventListener('input', function () {
                    touched['pengeluaran-total'] = true;
                    updateStepperStatus();
                });
                rowEl.querySelector('.btn-remove-row').addEventListener('click', function () {
                    rowEl.remove();
                    toggleEmptyState();
                    renderAllPreviews();
                    updateStepperStatus();
                });
                pengeluaranRowsEl.appendChild(rowEl);
                toggleEmptyState();
                updateStepperStatus();
            }

            function toggleEmptyState() {
                pengeluaranEmptyEl.classList.toggle('d-none', pengeluaranRowsEl.children.length > 0);
            }

            document.getElementById('btn-add-pengeluaran').addEventListener('click', function () {
                addPengeluaranRow();
                renderAllPreviews();
            });

            // ----- Surat Pernyataan: checkbox tetap, keterangan cuma untuk Keterlambatan -----
            document.querySelectorAll('.pernyataan-checkbox').forEach(function (checkbox) {
                const optionEl = checkbox.closest('.custom-option');
                optionEl.classList.toggle('checked', checkbox.checked);
                checkbox.addEventListener('change', function () {
                    optionEl.classList.toggle('checked', checkbox.checked);
                    updateStepperStatus();
                });
            });

            const pernyataanKeterlambatanCheckbox = document.getElementById('input-pernyataan-keterlambatan');
            const pernyataanKeterlambatanWrap = document.getElementById('pernyataan-keterlambatan-keterangan-wrap');
            pernyataanKeterlambatanCheckbox.addEventListener('change', function () {
                pernyataanKeterlambatanWrap.hidden = !pernyataanKeterlambatanCheckbox.checked;
            });

            // ----- Dokumentasi (thumbnail preview + grid di preview Laporan) -----
            const dokumentasiInput = document.getElementById('input-dokumentasi');
            const dokumentasiList = document.getElementById('dokumentasi-preview-list');
            const dokumentasiCsrfToken = form.querySelector('input[name="_token"]').value;
            const dokumentasiDestroyUrlTemplate = @json($perjalananDinas
                ? route('perjalanan-dinas.dokumentasi.destroy', [$perjalananDinas, '__ID__'])
                : null);
            const dokumentasiCaptionUrlTemplate = @json($perjalananDinas
                ? route('perjalanan-dinas.dokumentasi.caption', [$perjalananDinas, '__ID__'])
                : null);

            // File yang sudah tersimpan di server (mode edit) — dihapus lewat tombol "x"
            // langsung AJAX (permanen seketika, tidak menunggu form disubmit). Caption-nya
            // juga diedit langsung AJAX (endpoint terpisah, lihat updateCaptionDokumentasi)
            // karena update() form utama sengaja tidak pernah mengubah baris Dokumentasi
            // yang sudah ada.
            let existingDokumentasi = @json($existingDokumentasi ?? []);
            // File baru yang baru dipilih user tapi belum disubmit. FileList bawaan
            // <input type="file"> read-only (tidak bisa dihapus satu-satu), jadi daftar
            // sebenarnya disimpan manual di sini — tiap berubah, disinkronkan ulang ke
            // dokumentasiInput.files lewat DataTransfer supaya yang benar-benar ikut
            // terkirim saat submit cuma yang tersisa (belum dihapus). pendingCaptions
            // sejajar index-nya dengan pendingFiles, dikirim sebagai input hidden
            // "dokumentasi_caption[]" terpisah (lihat rebuildDokumentasiCaptionInputs)
            // supaya di server tetap bisa dipasangkan ke file "dokumentasi[]" yang sesuai.
            let pendingFiles = [];
            let pendingPreviewUrls = [];
            let pendingCaptions = [];

            function dokumentasiGabungan() {
                return existingDokumentasi.map(function (d) { return { nama_file: d.nama_file, url: d.url, caption: d.caption }; })
                    .concat(pendingFiles.map(function (file, i) { return { nama_file: file.name, url: pendingPreviewUrls[i] || '', caption: pendingCaptions[i] }; }));
            }

            function rebuildDokumentasiInputFiles() {
                const dt = new DataTransfer();
                pendingFiles.forEach(function (file) { dt.items.add(file); });
                dokumentasiInput.files = dt.files;
            }

            // Caption file yang belum diupload dikirim sebagai input hidden terpisah
            // (bukan lewat FormData bawaan <input type="file">, yang tidak bisa
            // dititipi field lain per-file) — dibangun ulang tiap pendingCaptions
            // berubah supaya urutannya selalu sejajar sama dokumentasiInput.files.
            function rebuildDokumentasiCaptionInputs() {
                form.querySelectorAll('input[name="dokumentasi_caption[]"]').forEach(function (el) { el.remove(); });
                pendingCaptions.forEach(function (caption) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'dokumentasi_caption[]';
                    input.value = caption || '';
                    form.appendChild(input);
                });
            }

            function boksDokumentasi(namaFile, url, caption, onCaptionChange) {
                const box = document.createElement('div');
                box.className = 'border rounded p-2 text-center position-relative dokumentasi-thumb';
                box.style.width = '110px';
                if (url && /\.(jpe?g|png|gif|webp)$/i.test(namaFile || '')) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.style.width = '100%';
                    img.style.height = '60px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    box.appendChild(img);
                } else {
                    box.innerHTML = '<i class="ti ti-file-text ti-lg"></i>';
                }
                const label = document.createElement('div');
                label.className = 'small text-truncate mt-1';
                label.textContent = namaFile || '';
                box.appendChild(label);

                const captionInput = document.createElement('input');
                captionInput.type = 'text';
                captionInput.className = 'form-control form-control-sm mt-1';
                captionInput.placeholder = 'Caption foto';
                captionInput.value = caption || '';
                captionInput.addEventListener('click', function (e) { e.stopPropagation(); });
                captionInput.addEventListener('change', function () { onCaptionChange(captionInput.value); });
                box.appendChild(captionInput);

                return box;
            }

            function tombolHapusDokumentasi(onHapus) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dokumentasi-hapus';
                btn.title = 'Hapus';
                btn.innerHTML = '<i class="ti ti-x"></i>';
                btn.addEventListener('click', onHapus);
                return btn;
            }

            function renderDokumentasiList() {
                dokumentasiList.innerHTML = '';

                existingDokumentasi.forEach(function (d) {
                    const box = boksDokumentasi(d.nama_file, d.url, d.caption, function (caption) {
                        fetch(dokumentasiCaptionUrlTemplate.replace('__ID__', d.id), {
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': dokumentasiCsrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ caption: caption }),
                        }).then(function (res) {
                            if (!res.ok) throw new Error('gagal simpan caption');
                            d.caption = caption;
                            renderAllPreviews();
                        }).catch(function () {
                            alert('Gagal menyimpan caption. Coba lagi.');
                        });
                    });
                    box.appendChild(tombolHapusDokumentasi(function () {
                        if (!confirm('Hapus file "' + (d.nama_file || '') + '"? File yang sudah tersimpan akan langsung dihapus permanen.')) return;
                        fetch(dokumentasiDestroyUrlTemplate.replace('__ID__', d.id), {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': dokumentasiCsrfToken, 'Accept': 'application/json' },
                        }).then(function (res) {
                            if (!res.ok) throw new Error('gagal hapus');
                            existingDokumentasi = existingDokumentasi.filter(function (item) { return item.id !== d.id; });
                            touched['laporan-dokumentasi'] = true;
                            renderDokumentasiList();
                            renderAllPreviews();
                        }).catch(function () {
                            alert('Gagal menghapus file dokumentasi. Coba lagi.');
                        });
                    }));
                    dokumentasiList.appendChild(box);
                });

                pendingFiles.forEach(function (file, index) {
                    const box = boksDokumentasi(file.name, pendingPreviewUrls[index] || '', pendingCaptions[index], function (caption) {
                        pendingCaptions[index] = caption;
                        rebuildDokumentasiCaptionInputs();
                        renderAllPreviews();
                    });
                    if (file.type.startsWith('image/') && !pendingPreviewUrls[index]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            pendingPreviewUrls[index] = e.target.result;
                            renderDokumentasiList();
                            renderAllPreviews();
                        };
                        reader.readAsDataURL(file);
                    }
                    box.appendChild(tombolHapusDokumentasi(function () {
                        pendingFiles.splice(index, 1);
                        pendingPreviewUrls.splice(index, 1);
                        pendingCaptions.splice(index, 1);
                        rebuildDokumentasiInputFiles();
                        rebuildDokumentasiCaptionInputs();
                        touched['laporan-dokumentasi'] = true;
                        renderDokumentasiList();
                        renderAllPreviews();
                    }));
                    dokumentasiList.appendChild(box);
                });

                updateStepperStatus();
            }

            renderDokumentasiList();

            dokumentasiInput.addEventListener('change', function () {
                pendingFiles = pendingFiles.concat(Array.from(dokumentasiInput.files));
                pendingPreviewUrls = pendingFiles.map(function (_, i) { return pendingPreviewUrls[i]; });
                pendingCaptions = pendingFiles.map(function (_, i) { return pendingCaptions[i]; });
                rebuildDokumentasiInputFiles();
                rebuildDokumentasiCaptionInputs();
                touched['laporan-dokumentasi'] = true;
                renderDokumentasiList();
                renderAllPreviews();
            });

            // ----- Review -----
            function renderReview() {
                const el = document.getElementById('modal-review-summary');
                if (!el) return;
                // Nominal rincian = rate per hari/malam (bukan total jadi), sama seperti
                // di renderRincianBiaya (dokumen-perjadin.js) — total di sini dihitung
                // dengan formula yang sama (rate x jumlah hari, fallback ke lama
                // perjalanan dinas kalau jumlah harinya tidak diisi manual) biar angka
                // ringkasan di modal Review persis sama dengan yang tampil di dokumen.
                const lamaUntukRincian = DokumenPerjadin.hitungLamaHari(val('input-tanggal_mulai'), val('input-tanggal_selesai'));
                const malamUntukRincian = Math.max(lamaUntukRincian - 1, 0);
                const rincianTotal = ['uang_harian', 'transport', 'penginapan'].reduce(function (sum, jenis) {
                    const rate = parseFloat(val('input-rincian-' + jenis)) || 0;
                    const hariManual = parseInt(val('input-rincian_hari-' + jenis), 10);
                    const hari = isNaN(hariManual) ? (jenis === 'penginapan' ? malamUntukRincian : lamaUntukRincian) : hariManual;
                    return sum + rate * hari;
                }, 0);
                const pengeluaranCount = document.querySelectorAll('#pengeluaran-rows > div').length;
                const pernyataanCount = ['tidak_pakai_kendaraan_dinas', 'tidak_menginap_hotel', 'keterlambatan']
                    .filter(function (jenis) { return document.getElementById('input-pernyataan-' + jenis).checked; }).length;
                const dokumentasiCount = existingDokumentasi.length + pendingFiles.length;
                const fmtTanggalPanjang = DokumenPerjadin.fmtTanggalPanjang;
                const esc = DokumenPerjadin.esc;

                function item(ok, label) {
                    return `<li class="d-flex align-items-center gap-2 mb-2">
                        <i class="ti ${ok ? 'ti-circle-check text-success' : 'ti-circle-dashed text-muted'}"></i>
                        <span>${label}</span>
                    </li>`;
                }

                // Checklist ini pakai isStepComplete() yang sama dengan penanda hijau di
                // stepper & gate isDataWajibLengkap — satu sumber kebenaran, supaya
                // ketiganya tidak pernah beda pendapat soal step mana yang "lengkap".
                let html = '<ul class="list-unstyled mb-0">';
                html += item(isStepComplete('step-umum'), 'Data Umum & Surat Tugas: ' + (esc(val('input-perihal')) || '-'));
                @if ($butuhSpd)
                    html += item(isStepComplete('step-spd'), 'SPD: ' + (esc(val('input-no_spd')) || 'belum diisi'));
                @endif
                html += item(isStepComplete('step-rincian'), 'Rincian Biaya: ' + DokumenPerjadin.fmtRupiah(rincianTotal));
                html += item(pengeluaranCount > 0, 'Pengeluaran Riil: ' + pengeluaranCount + ' item (opsional)');
                html += item(isStepComplete('step-kuitansi'), 'Kuitansi: ' + (val('input-tanggal_kuitansi') ? fmtTanggalPanjang(val('input-tanggal_kuitansi')) : 'belum diisi'));
                html += item(pernyataanCount > 0, 'Surat Pernyataan: ' + pernyataanCount + ' pernyataan (opsional)');
                html += item(!isKosong('input-kesimpulan') && !isKosong('input-tindak_lanjut'), 'Laporan: ' + (val('input-kesimpulan') ? 'sudah diisi' : 'belum diisi'));
                html += item(dokumentasiCount > 0, 'Dokumentasi: ' + dokumentasiCount + ' file');
                html += '</ul>';
                el.innerHTML = html;
            }

            // Preview di layar dipecah per "lembar kertas" (lihat DokumenPerjadin.renderPaginated)
            // supaya dokumen yang lebih dari 1 halaman (mis. Laporan, Surat Pernyataan dengan
            // beberapa kondisi) kelihatan sebagai lembar-lembar terpisah, mirip print preview.
            function setPreview(elId, html, paperClass) {
                const el = document.getElementById(elId);
                el.innerHTML = DokumenPerjadin.renderPaginated(html, paperClass);
                DokumenPerjadin.applyPageMinHeights(el);
            }

            function renderAllPreviews() {
                const data = collectFormData();
                setPreview('preview-surat-tugas', DokumenPerjadin.renderSuratTugas(data), 'dokumen-f4 dokumen-watermark-pratinjau');
                @if ($butuhSpd)
                    setPreview('preview-spd', DokumenPerjadin.renderSpd(data), 'dokumen-f4');
                @endif
                setPreview('preview-rincian-biaya', DokumenPerjadin.renderRincianBiaya(data), 'dokumen-a4 dokumen-rincian-biaya');
                setPreview('preview-pengeluaran-riil', DokumenPerjadin.renderPengeluaranRiil(data), 'dokumen-a4');
                setPreview('preview-kuitansi', DokumenPerjadin.renderKuitansi(data), 'dokumen-a4');
                setPreview('preview-pernyataan', DokumenPerjadin.renderPernyataan(data), 'dokumen-a4');
                setPreview('preview-laporan', DokumenPerjadin.renderLaporan(data), 'dokumen-a4');
                renderReview();
                perbaruiRekomendasiAkomodasi();
                perbaruiRekomendasiUangHarian();
                periksaPeringatanTransport();
            }

            // Preview gabungan semua dokumen di modal Review, sekarang bertab persis
            // seperti modal Lihat/Export di dashboard (lihat _rekap-table.blade.php) —
            // dipanggil belakangan lewat event shown.bs.modal (bukan dari
            // renderAllPreviews), karena DokumenPerjadin.applyPageMinHeights() mengukur
            // offsetWidth elemennya; kalau modal masih display:none saat diukur, hasilnya
            // 0/salah.
            let reviewDocs = {};
            let reviewActiveKey = null;
            let reviewData = null;

            function paperClassForReview(key) {
                if (key === 'surat-tugas') return 'dokumen-f4 dokumen-watermark-pratinjau';
                if (key === 'spd') return 'dokumen-f4';
                return 'dokumen-a4' + (key === 'rincian' ? ' dokumen-rincian-biaya' : '');
            }

            function paperSizeForReview(key) {
                return (key === 'surat-tugas' || key === 'spd') ? 'f4' : 'a4';
            }

            function extraClassForReview(key) {
                if (key === 'rincian') return 'dokumen-rincian-biaya';
                if (key === 'surat-tugas') return 'dokumen-watermark-pratinjau';
                return '';
            }

            const btnReviewExportSatu = document.getElementById('btn-review-export-satu');
            const btnReviewExportSemua = document.getElementById('btn-review-export-semua');
            const btnReviewPreviewSemua = document.getElementById('btn-review-preview-semua');

            function tampilkanTabReview(key) {
                const tabsEl = document.getElementById('modal-review-tabs');
                const contentEl = document.getElementById('modal-review-tab-content');
                const previewAllEl = document.getElementById('modal-review-preview-all');
                tabsEl.querySelectorAll('.nav-link').forEach(function (b) { b.classList.toggle('active', b.dataset.key === key); });
                btnReviewPreviewSemua.classList.remove('active');
                previewAllEl.classList.add('d-none');
                contentEl.classList.remove('d-none');
                contentEl.querySelectorAll('.dokumen-preview-pages').forEach(function (p) { p.classList.add('d-none'); });
                const pane = contentEl.querySelector('[data-key="' + key + '"]');
                if (pane) {
                    pane.classList.remove('d-none');
                    DokumenPerjadin.applyPageMinHeights(pane);
                }
                reviewActiveKey = key;
                btnReviewExportSatu.disabled = false;
            }

            function tampilkanPreviewSemuaReview() {
                const tabsEl = document.getElementById('modal-review-tabs');
                const contentEl = document.getElementById('modal-review-tab-content');
                const previewAllEl = document.getElementById('modal-review-preview-all');
                previewAllEl.innerHTML = Object.keys(reviewDocs).map(function (key) {
                    return DokumenPerjadin.renderPaginated(reviewDocs[key].html, paperClassForReview(key));
                }).join('<div class="mb-4"></div>');
                tabsEl.querySelectorAll('.nav-link').forEach(function (b) { b.classList.remove('active'); });
                btnReviewPreviewSemua.classList.add('active');
                contentEl.classList.add('d-none');
                previewAllEl.classList.remove('d-none');
                DokumenPerjadin.applyPageMinHeights(previewAllEl);
                reviewActiveKey = null;
                btnReviewExportSatu.disabled = true;
            }

            function renderModalReviewDocuments() {
                reviewData = collectFormData();
                reviewDocs = { 'surat-tugas': { label: 'Surat Tugas', html: DokumenPerjadin.renderSuratTugas(reviewData) } };
                @if ($butuhSpd)
                    reviewDocs['spd'] = { label: 'SPD', html: DokumenPerjadin.renderSpd(reviewData) };
                @endif
                reviewDocs['rincian'] = { label: 'Rincian Biaya', html: DokumenPerjadin.renderRincianBiaya(reviewData) };
                reviewDocs['pengeluaran'] = { label: 'Pengeluaran Riil', html: DokumenPerjadin.renderPengeluaranRiil(reviewData) };
                reviewDocs['kuitansi'] = { label: 'Kuitansi', html: DokumenPerjadin.renderKuitansi(reviewData) };
                reviewDocs['pernyataan'] = { label: 'Pernyataan', html: DokumenPerjadin.renderPernyataan(reviewData) };
                reviewDocs['laporan'] = { label: 'Laporan', html: DokumenPerjadin.renderLaporan(reviewData) };

                const tabsEl = document.getElementById('modal-review-tabs');
                const contentEl = document.getElementById('modal-review-tab-content');
                const previewAllEl = document.getElementById('modal-review-preview-all');
                tabsEl.innerHTML = '';
                contentEl.innerHTML = '';
                previewAllEl.innerHTML = '';
                previewAllEl.classList.add('d-none');
                contentEl.classList.remove('d-none');
                btnReviewPreviewSemua.classList.remove('active');

                let first = true;
                Object.keys(reviewDocs).forEach(function (key) {
                    const doc = reviewDocs[key];

                    const li = document.createElement('li');
                    li.className = 'nav-item';
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'nav-link' + (first ? ' active' : '');
                    btn.dataset.key = key;
                    btn.textContent = doc.label;
                    btn.addEventListener('click', function () { tampilkanTabReview(key); });
                    li.appendChild(btn);
                    tabsEl.appendChild(li);

                    const pane = document.createElement('div');
                    pane.className = 'dokumen-preview-pages' + (first ? '' : ' d-none');
                    pane.dataset.key = key;
                    pane.innerHTML = DokumenPerjadin.renderPaginated(doc.html, paperClassForReview(key));
                    contentEl.appendChild(pane);

                    first = false;
                });

                reviewActiveKey = Object.keys(reviewDocs)[0];
                btnReviewExportSatu.disabled = false;
            }
            if (reviewModalEl) {
                reviewModalEl.addEventListener('shown.bs.modal', renderModalReviewDocuments);
            }

            btnReviewPreviewSemua.addEventListener('click', function () {
                if (btnReviewPreviewSemua.classList.contains('active')) {
                    tampilkanTabReview(Object.keys(reviewDocs)[0]);
                } else {
                    tampilkanPreviewSemuaReview();
                }
            });

            // ----- Export PDF (HD, server-side) langsung dari modal Review — tidak perlu
            // data tersimpan dulu, karena endpoint export cuma butuh HTML yang sudah
            // di-render (sama seperti preview di atas), bukan id record dari database. -----
            const cssUrlDokumenReview = '{{ asset("assets/css/dokumen-perjadin.css") }}?v={{ filemtime(public_path("assets/css/dokumen-perjadin.css")) }}';
            const exportPdfUrlReview = '{{ route("dokumen.export-pdf") }}';
            const csrfTokenReview = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function namaFileDokumenReview(labelDokumen) {
                const noSuratTugas = (reviewData && reviewData.no_surat_tugas) || '';
                const angkaMatch = noSuratTugas.match(/^B-(\d+)/);
                const nomor = angkaMatch ? angkaMatch[1] : ((noSuratTugas || '').replace(/[\\/:*?"<>|]/g, '-').trim() || 'TanpaNomor');
                const namaPelaksana = (reviewData && reviewData.pelaksana && reviewData.pelaksana.nama) || 'TanpaNama';
                return (nomor + '-' + namaPelaksana + '-' + labelDokumen).replace(/[\\/:*?"<>|]/g, '-').trim();
            }

            function kunciTombolExportReview(tombolAktif) {
                [btnReviewExportSatu, btnReviewExportSemua].forEach(function (b) { b.disabled = true; });
                const labelAsli = tombolAktif.innerHTML;
                tombolAktif.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengekspor...';
                return function selesai() {
                    [btnReviewExportSatu, btnReviewExportSemua].forEach(function (b) { b.disabled = false; });
                    tombolAktif.innerHTML = labelAsli;
                };
            }

            async function eksporSatuDokumenReview(key) {
                const doc = reviewDocs[key];
                if (!doc) return;
                await DokumenPerjadin.exportAsPdfHd(
                    doc.html, namaFileDokumenReview(doc.label), cssUrlDokumenReview,
                    paperSizeForReview(key), extraClassForReview(key), exportPdfUrlReview, csrfTokenReview
                );
            }

            btnReviewExportSatu.addEventListener('click', async function () {
                const selesai = kunciTombolExportReview(btnReviewExportSatu);
                try {
                    await eksporSatuDokumenReview(reviewActiveKey);
                } catch (e) {
                    alert('Gagal export PDF: ' + e.message);
                } finally {
                    selesai();
                }
            });

            btnReviewExportSemua.addEventListener('click', async function () {
                const selesai = kunciTombolExportReview(btnReviewExportSemua);
                try {
                    for (const key of Object.keys(reviewDocs)) {
                        await eksporSatuDokumenReview(key);
                    }
                } catch (e) {
                    alert('Gagal export PDF: ' + e.message);
                } finally {
                    selesai();
                }
            });

            form.addEventListener('input', renderAllPreviews);
            form.addEventListener('change', renderAllPreviews);
            stepperEl.addEventListener('shown.bs-stepper', renderAllPreviews);

            // ----- Isi ulang baris dari data lama (validasi gagal) -----
            const oldPengeluaran = @json(old('pengeluaran', []));
            oldPengeluaran.forEach(function (r) { addPengeluaranRow(r); });
            toggleEmptyState();

            renderAllPreviews();

            // Dipanggil paling akhir (bukan pas pasangValidasiRealtime tadi) — perlu
            // nunggu dokumentasiGabungan/pengeluaran-rows/dst yang disetel belakangan
            // di script ini sudah siap semua, supaya status awal stepper (mis. mode edit
            // yang datanya sudah lengkap dari awal) langsung benar dari render pertama.
            updateStepperStatus();

            // ----- Mode "view": kunci semua input & tombol aksi (kecuali navigasi step),
            // dipasang paling akhir supaya baris-baris yang ditambah lewat JS di atas
            // (Pengeluaran Riil) ikut terkunci juga. -----
            if (form.dataset.mode === 'view') {
                form.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                    if (el.classList.contains('btn-wizard-prev') || el.classList.contains('btn-wizard-next') || el.classList.contains('step-trigger')) {
                        return;
                    }
                    el.disabled = true;
                });
            }
        });
    </script>
@endpush
@endif
