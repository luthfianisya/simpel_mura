@extends('layouts.app')

@section('title', 'Buat Perjalanan Dinas')

@php
    $jenisLabel = match ($jenis) {
        'biasa' => 'Perjalanan Dinas Biasa',
        'dalam_kota_kurang_8_jam' => 'Dalam Kota ≤ 8 Jam',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        default => 'Perjalanan Dinas',
    };
@endphp

@push('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/dokumen-perjadin.css') }}" />
    <style>
        .bs-stepper .bs-stepper-content {
            padding: 1.5rem 0 0;
        }

        .wizard-form-card {
            border: 1px solid #e7e7e9;
        }

        .preview-sticky {
            position: sticky;
            top: 1rem;
        }

        .btn-remove-row {
            line-height: 1;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-1">Buat Perjalanan Dinas</h4>
            <p class="text-muted mb-0">{{ $jenisLabel }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-label-secondary d-inline-flex align-items-center gap-1">
            <i class="ti ti-arrow-left ti-sm"></i> Kembali
        </a>
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

    @if (!$pemohonPegawai)
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
    <form id="wizard-form" method="POST" action="{{ route('perjalanan-dinas.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="jenis_perjadin" value="{{ $jenis }}">

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
                <div class="line"></div>
                <div class="step" data-target="#step-review">
                    <button type="button" class="step-trigger" role="tab">
                        <span class="bs-stepper-circle">{{ ++$stepNo }}</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">Review</span>
                            <span class="bs-stepper-subtitle">&amp; Simpan</span>
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
                                            <label class="form-label">No. Surat Tugas</label>
                                            <div class="input-group">
                                                <span class="input-group-text">B-</span>
                                                <input type="text" id="input-no_surat_tugas_angka" class="form-control" inputmode="numeric" pattern="[0-9]*" placeholder="mis. 1082" value="{{ $noSuratTugasAngka }}">
                                                <span class="input-group-text" id="suffix-no_surat_tugas">/62130/KU.340/{{ now()->year }}</span>
                                            </div>
                                            <input type="hidden" id="input-no_surat_tugas" name="no_surat_tugas" value="{{ $oldNoSuratTugas }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Surat Tugas</label>
                                            <input type="date" id="input-tanggal_surat_tugas" name="tanggal_surat_tugas" class="form-control" value="{{ old('tanggal_surat_tugas') }}" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Jenis Kegiatan</label>
                                            <select id="input-jenis_kegiatan" name="id_jenis_kegiatan" class="form-select" required>
                                                <option value="" disabled {{ old('id_jenis_kegiatan') ? '' : 'selected' }}>Pilih jenis kegiatan</option>
                                                @foreach ($jenisKegiatanList as $jk)
                                                    <option value="{{ $jk->id_jenis_kegiatan }}"
                                                        data-uraian-tugas="{{ $jk->template_uraian_tugas }}"
                                                        data-uraian-laporan="{{ $jk->template_uraian_laporan }}"
                                                        data-mak="{{ $jk->saran_mak_default }}"
                                                        {{ old('id_jenis_kegiatan') == $jk->id_jenis_kegiatan ? 'selected' : '' }}>
                                                        {{ $jk->nama_kegiatan }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($jenisKegiatanList->isEmpty())
                                                <div class="form-text text-warning">Belum ada data jenis kegiatan di master data.</div>
                                            @endif
                                        </div>
                                        @if ($jenis === 'dalam_kota_lebih_8_jam')
                                            <div class="col-md-6">
                                                <label class="form-label">Apakah SPD?</label>
                                                <select id="input-is_spd" name="is_spd" class="form-select">
                                                    <option value="0" {{ old('is_spd', '0') == '0' ? 'selected' : '' }}>Tidak</option>
                                                    <option value="1" {{ old('is_spd') == '1' ? 'selected' : '' }}>Ya</option>
                                                </select>
                                                <div class="form-text">Jika Ya, lengkapi No. SPD di step SPD.</div>
                                            </div>
                                        @endif
                                        <div class="col-md-12">
                                            <label class="form-label">Perihal</label>
                                            <input type="text" id="input-perihal" name="perihal" class="form-control" value="{{ old('perihal') }}" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Uraian Tugas</label>
                                            <textarea id="input-uraian_tugas" name="uraian_tugas" class="form-control" rows="2">{{ old('uraian_tugas') }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Pemohon</label>
                                            <input type="text" class="form-control" value="{{ $pemohonPegawai->nama }}" disabled>
                                            <div class="form-text">Otomatis diisi dari akun Anda yang sedang login.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Pelaksana</label>
                                            <select id="input-pelaksana" name="id_pegawai_mitra_pelaksana" class="form-select" required>
                                                <option value="" disabled {{ old('id_pegawai_mitra_pelaksana') ? '' : 'selected' }}>Pilih pelaksana</option>
                                                @foreach ($pegawaiMitraList as $pm)
                                                    <option value="{{ $pm->id_pegawai_mitra }}"
                                                        data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}"
                                                        {{ old('id_pegawai_mitra_pelaksana') == $pm->id_pegawai_mitra ? 'selected' : '' }}>
                                                        {{ $pm->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Mulai</label>
                                            <input type="date" id="input-tanggal_mulai" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Selesai</label>
                                            <input type="date" id="input-tanggal_selesai" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}" required>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Pembebanan (Akun/MAK)</label>
                                            <input type="text" id="input-pembebanan" name="pembebanan" class="form-control" value="{{ old('pembebanan') }}" placeholder="mis. GG.2902.BMA.006.530.B.524113" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tingkat Biaya Perjadin</label>
                                            <select id="input-tingkat_biaya" class="form-select">
                                                <option value="">-</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Desa/Kel. Asal</label>
                                            <input type="text" id="input-desa_asal" name="desa_asal" class="form-control" value="{{ old('desa_asal') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Kabupaten/Kota Asal</label>
                                            <input type="text" id="input-kabupaten_asal" name="kabupaten_asal" class="form-control" value="{{ old('kabupaten_asal') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Desa/Kel. Tujuan</label>
                                            <input type="text" id="input-desa_tujuan" name="desa_tujuan" class="form-control" value="{{ old('desa_tujuan') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Kabupaten/Kota Tujuan</label>
                                            <input type="text" id="input-kabupaten_tujuan" name="kabupaten_tujuan" class="form-control" value="{{ old('kabupaten_tujuan') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Penandatangan ST</label>
                                            <select id="input-penandatangan_st" name="id_penandatangan_st" class="form-select">
                                                <option value="">-</option>
                                                @foreach ($pegawaiMitraList as $pm)
                                                    <option value="{{ $pm->id_pegawai_mitra }}"
                                                        data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}"
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
                                                        data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}"
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
                                                        data-nip="{{ $pm->nip }}" data-jabatan="{{ $pm->jabatan }}" data-pangkat="{{ $pm->pangkat }}" data-golongan="{{ $pm->golongan }}"
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
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div>
                                <div id="preview-surat-tugas" class="dokumen-preview dokumen-a4"></div>
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
                                                <label class="form-label">No. SPD</label>
                                                <div class="input-group">
                                                    <input type="text" id="input-no_spd" name="no_spd" class="form-control" value="{{ old('no_spd') }}" required>
                                                    <button type="button" id="btn-ambil-nomor-spd" class="btn btn-outline-primary d-none">Ambil Nomor SPD</button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tanggal SPD</label>
                                                <input type="date" id="input-tanggal_spd" name="tanggal_spd" class="form-control" value="{{ old('tanggal_spd') }}" required>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Alat Angkut</label>
                                                <input type="text" id="input-angkutan" name="angkutan" class="form-control" value="{{ old('angkutan') }}" placeholder="mis. Kendaraan Roda Empat">
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
                                    <div class="alert alert-warning py-2 px-3 small mb-2">
                                        <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                    </div>
                                    <div id="preview-spd" class="dokumen-preview dokumen-a4"></div>
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
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="mb-0">Rincian Biaya (Rencana)</h6>
                                        <button type="button" id="btn-add-rincian" class="btn btn-sm btn-label-primary"><i class="ti ti-plus ti-sm"></i> Tambah</button>
                                    </div>
                                    <div id="rincian-rows"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div>
                                <div id="preview-rincian-biaya" class="dokumen-preview dokumen-a4"></div>
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
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div>
                                <div id="preview-pengeluaran-riil" class="dokumen-preview dokumen-a4"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP: KUITANSI & SURAT PERNYATAAN --}}
                <div id="step-kuitansi" class="content">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card wizard-form-card">
                                <div class="card-body">
                                    <h6 class="mb-3">Kuitansi</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Kuitansi</label>
                                            <input type="date" id="input-tanggal_kuitansi" name="tanggal_kuitansi" class="form-control" value="{{ old('tanggal_kuitansi') }}">
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
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div>
                                <div id="preview-kuitansi" class="dokumen-preview dokumen-a4"></div>
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
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="mb-0">Surat Pernyataan</h6>
                                        <button type="button" id="btn-add-pernyataan" class="btn btn-sm btn-label-primary"><i class="ti ti-plus ti-sm"></i> Tambah</button>
                                    </div>
                                    <div id="pernyataan-rows"></div>
                                    <div id="pernyataan-empty" class="text-muted small">Tidak ada surat pernyataan khusus.</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div>
                                <div id="preview-pernyataan" class="dokumen-preview dokumen-a4"></div>
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
                                            <label class="form-label">Kesimpulan Hasil Kegiatan</label>
                                            <textarea id="input-kesimpulan" name="kesimpulan_hasil_kegiatan" class="form-control" rows="3">{{ old('kesimpulan_hasil_kegiatan') }}</textarea>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Tindak Lanjut</label>
                                            <textarea id="input-tindak_lanjut" name="tindak_lanjut" class="form-control" rows="2">{{ old('tindak_lanjut') }}</textarea>
                                        </div>
                                    </div>
                                    <h6 class="mb-3">Dokumentasi</h6>
                                    <input type="file" id="input-dokumentasi" name="dokumentasi[]" class="form-control" multiple accept="image/*,.pdf">
                                    <div id="dokumentasi-preview-list" class="d-flex flex-wrap gap-2 mt-3"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                <button type="button" class="btn btn-primary btn-wizard-next">Lanjut <i class="ti ti-arrow-right ti-sm ms-1"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="preview-sticky">
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <i class="ti ti-info-circle me-1"></i> Preview masih draft awal, format resmi menyusul.
                                </div>
                                <div id="preview-laporan" class="dokumen-preview dokumen-a4"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP: REVIEW & SUBMIT --}}
                <div id="step-review" class="content">
                    <div class="row g-4">
                        <div class="col-lg-8 mx-auto">
                            <div class="card wizard-form-card">
                                <div class="card-body">
                                    <h6 class="mb-3">Review Data</h6>
                                    <div id="review-summary"></div>
                                    <div class="d-flex justify-content-between mt-4">
                                        <button type="button" class="btn btn-label-secondary btn-wizard-prev"><i class="ti ti-arrow-left ti-sm me-1"></i> Kembali</button>
                                        <button type="button" id="btn-simpan-perjalanan-dinas" class="btn btn-success"><i class="ti ti-device-floppy ti-sm me-1"></i> Simpan Perjalanan Dinas</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Template baris berulang (dipakai oleh JS, bukan ditampilkan langsung) --}}
    <script type="text/template" id="rincian-row-template">
        <div class="row g-2 align-items-end mb-2">
            <div class="col-5">
                <label class="form-label small mb-1">Komponen Biaya</label>
                <select name="rincian[__INDEX__][jenis_komponen]" class="form-select rincian-input" required>
                    <option value="uang_harian">Uang Harian</option>
                    <option value="transport">Transport</option>
                    <option value="penginapan">Penginapan</option>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label small mb-1">Nominal (Rp)</label>
                <input type="number" min="0" step="1000" name="rincian[__INDEX__][nominal]" class="form-control rincian-input" required>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-outline-danger btn-remove-row w-100" title="Hapus"><i class="ti ti-trash ti-sm"></i></button>
            </div>
        </div>
    </script>

    <script type="text/template" id="pengeluaran-row-template">
        <div class="row g-2 align-items-end mb-2">
            <div class="col-3">
                <label class="form-label small mb-1">Jenis</label>
                <select name="pengeluaran[__INDEX__][jenis]" class="form-select pengeluaran-input">
                    <option value="transportasi">Biaya Transportasi</option>
                    <option value="akomodasi">Biaya Akomodasi</option>
                </select>
            </div>
            <div class="col-4">
                <label class="form-label small mb-1">Uraian</label>
                <input type="text" name="pengeluaran[__INDEX__][uraian]" class="form-control pengeluaran-input" placeholder="mis. Tiket pesawat">
            </div>
            <div class="col-3">
                <label class="form-label small mb-1">Nominal (Rp)</label>
                <input type="number" min="0" step="1000" name="pengeluaran[__INDEX__][nominal]" class="form-control pengeluaran-input">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-outline-danger btn-remove-row w-100" title="Hapus"><i class="ti ti-trash ti-sm"></i></button>
            </div>
        </div>
    </script>

    <script type="text/template" id="pernyataan-row-template">
        <div class="row g-2 align-items-start mb-2">
            <div class="col-4">
                <label class="form-label small mb-1">Kondisi</label>
                <select name="pernyataan[__INDEX__][jenis_kondisi]" class="form-select pernyataan-input">
                    <option value="tidak_pakai_kendaraan_dinas">Tidak Menggunakan Kendaraan Dinas</option>
                    <option value="tidak_menginap_hotel">Tidak Menginap di Hotel/Akomodasi</option>
                    <option value="keterlambatan">Keterlambatan Pengajuan Tagihan</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small mb-1">Keterangan</label>
                <textarea name="pernyataan[__INDEX__][keterangan]" class="form-control pernyataan-input" rows="1" placeholder="mis. alasan keterlambatan (jika relevan)"></textarea>
            </div>
            <div class="col-2 pt-4">
                <button type="button" class="btn btn-outline-danger btn-remove-row w-100" title="Hapus"><i class="ti ti-trash ti-sm"></i></button>
            </div>
        </div>
    </script>
    @endif
@endsection

@if ($pemohonPegawai)
@push('page-js')
    <script src="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
    <script src="{{ asset('assets/js/dokumen-perjadin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('wizard-form');
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
            // dropdown Rincian Biaya), padahal user belum klik "Simpan".
            document.getElementById('btn-simpan-perjalanan-dinas').addEventListener('click', function () {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            });

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
                }
            }
            noSuratTugasAngkaInput.addEventListener('input', updateNoSuratTugas);
            tanggalSuratTugasInput.addEventListener('change', updateNoSuratTugas);
            updateNoSuratTugas();

            // ----- SPD: wajib-tidaknya & tombol ambil nomor -----
            const jenisPerjadin = @json($jenis);
            const isSpdToggle = document.getElementById('input-is_spd');
            const noSpdInput = document.getElementById('input-no_spd');
            const tanggalSpdInput = document.getElementById('input-tanggal_spd');
            const btnAmbilNomorSpd = document.getElementById('btn-ambil-nomor-spd');

            function spdWajib() {
                return jenisPerjadin === 'biasa' || (jenisPerjadin === 'dalam_kota_lebih_8_jam' && isSpdToggle && isSpdToggle.value === '1');
            }

            function updateSpdRequirement() {
                const wajib = spdWajib();
                if (noSpdInput) noSpdInput.required = wajib;
                if (tanggalSpdInput) tanggalSpdInput.required = wajib;
                if (btnAmbilNomorSpd) btnAmbilNomorSpd.classList.toggle('d-none', !wajib);
            }

            if (isSpdToggle) {
                isSpdToggle.addEventListener('change', updateSpdRequirement);
            }
            updateSpdRequirement();

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
                if (!el || el.selectedIndex < 0) return empty;
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
                const rincian = Array.from(document.querySelectorAll('#rincian-rows > div')).map(function (row) {
                    return {
                        jenis_komponen: row.querySelector('select').value,
                        nominal: row.querySelector('input[type=number]').value,
                    };
                });
                const pengeluaran = Array.from(document.querySelectorAll('#pengeluaran-rows > div')).map(function (row) {
                    return {
                        jenis: row.querySelector('select').value,
                        uraian: row.querySelector('input[type=text]').value,
                        nominal: row.querySelector('input[type=number]').value,
                    };
                });
                const pernyataan = Array.from(document.querySelectorAll('#pernyataan-rows > div')).map(function (row) {
                    return {
                        jenis_kondisi: row.querySelector('select').value,
                        keterangan: row.querySelector('textarea').value,
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
                    dokumentasi: dokumentasiPreviewData,
                };
            }

            // ----- Auto-isi dari Jenis Kegiatan -----
            const jenisKegiatanSelect = document.getElementById('input-jenis_kegiatan');
            if (jenisKegiatanSelect) {
                jenisKegiatanSelect.addEventListener('change', function () {
                    const opt = jenisKegiatanSelect.options[jenisKegiatanSelect.selectedIndex];
                    if (!opt) return;
                    const uraianTugas = document.getElementById('input-uraian_tugas');
                    const pembebanan = document.getElementById('input-pembebanan');
                    const kesimpulan = document.getElementById('input-kesimpulan');
                    if (uraianTugas && !uraianTugas.value && opt.dataset.uraianTugas) {
                        uraianTugas.value = opt.dataset.uraianTugas;
                    }
                    if (pembebanan && !pembebanan.value && opt.dataset.mak) {
                        pembebanan.value = opt.dataset.mak;
                    }
                    if (kesimpulan && !kesimpulan.value && opt.dataset.uraianLaporan) {
                        kesimpulan.value = opt.dataset.uraianLaporan;
                    }
                });
            }

            // ----- Auto-isi Penandatangan ST / PPK / Bendahara berdasarkan jabatan pegawai -----
            function autoSelectByJabatan(selectId, keywords) {
                const select = document.getElementById(selectId);
                if (!select || select.value) return;
                const match = Array.from(select.options).find(function (opt) {
                    const jabatan = (opt.dataset.jabatan || '').toLowerCase();
                    return keywords.some(function (kw) { return jabatan.includes(kw); });
                });
                if (match) {
                    select.value = match.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            autoSelectByJabatan('input-penandatangan_st', ['kepala']);
            autoSelectByJabatan('input-ppk', ['pejabat pembuat komitmen', 'ppk']);
            autoSelectByJabatan('input-bendahara', ['bendahara']);

            // ----- Rincian Biaya -----
            let rincianIndex = 0;
            const rincianRowsEl = document.getElementById('rincian-rows');
            const rincianTemplate = document.getElementById('rincian-row-template');

            function addRincianRow(data) {
                data = data || {};
                const idx = rincianIndex++;
                const html = rincianTemplate.innerHTML.replace(/__INDEX__/g, idx);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const rowEl = wrapper.firstElementChild;
                if (data.jenis_komponen) rowEl.querySelector('select').value = data.jenis_komponen;
                if (data.nominal) rowEl.querySelector('input[type=number]').value = data.nominal;
                rowEl.querySelector('.btn-remove-row').addEventListener('click', function () {
                    rowEl.remove();
                    renderAllPreviews();
                });
                rincianRowsEl.appendChild(rowEl);
            }

            document.getElementById('btn-add-rincian').addEventListener('click', function () {
                addRincianRow();
                renderAllPreviews();
            });

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
                if (data.uraian) rowEl.querySelector('input[type=text]').value = data.uraian;
                if (data.nominal) rowEl.querySelector('input[type=number]').value = data.nominal;
                rowEl.querySelector('.btn-remove-row').addEventListener('click', function () {
                    rowEl.remove();
                    toggleEmptyState();
                    renderAllPreviews();
                });
                pengeluaranRowsEl.appendChild(rowEl);
                toggleEmptyState();
            }

            function toggleEmptyState() {
                pengeluaranEmptyEl.classList.toggle('d-none', pengeluaranRowsEl.children.length > 0);
            }

            document.getElementById('btn-add-pengeluaran').addEventListener('click', function () {
                addPengeluaranRow();
                renderAllPreviews();
            });

            // ----- Surat Pernyataan -----
            let pernyataanIndex = 0;
            const pernyataanRowsEl = document.getElementById('pernyataan-rows');
            const pernyataanTemplate = document.getElementById('pernyataan-row-template');
            const pernyataanEmptyEl = document.getElementById('pernyataan-empty');

            function addPernyataanRow(data) {
                data = data || {};
                const idx = pernyataanIndex++;
                const html = pernyataanTemplate.innerHTML.replace(/__INDEX__/g, idx);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const rowEl = wrapper.firstElementChild;
                if (data.jenis_kondisi) rowEl.querySelector('select').value = data.jenis_kondisi;
                if (data.keterangan) rowEl.querySelector('textarea').value = data.keterangan;
                rowEl.querySelector('.btn-remove-row').addEventListener('click', function () {
                    rowEl.remove();
                    togglePernyataanEmptyState();
                    renderAllPreviews();
                });
                pernyataanRowsEl.appendChild(rowEl);
                togglePernyataanEmptyState();
            }

            function togglePernyataanEmptyState() {
                pernyataanEmptyEl.classList.toggle('d-none', pernyataanRowsEl.children.length > 0);
            }

            document.getElementById('btn-add-pernyataan').addEventListener('click', function () {
                addPernyataanRow();
                renderAllPreviews();
            });

            // ----- Dokumentasi (thumbnail preview + grid di preview Laporan) -----
            const dokumentasiInput = document.getElementById('input-dokumentasi');
            const dokumentasiList = document.getElementById('dokumentasi-preview-list');
            let dokumentasiPreviewData = [];
            dokumentasiInput.addEventListener('change', function () {
                dokumentasiList.innerHTML = '';
                const files = Array.from(dokumentasiInput.files);
                dokumentasiPreviewData = files.map(function (file) { return { nama_file: file.name, url: '' }; });

                files.forEach(function (file, index) {
                    const box = document.createElement('div');
                    box.className = 'border rounded p-2 text-center';
                    box.style.width = '90px';
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.style.width = '100%';
                        img.style.height = '60px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '4px';
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            img.src = e.target.result;
                            dokumentasiPreviewData[index].url = e.target.result;
                            renderAllPreviews();
                        };
                        reader.readAsDataURL(file);
                        box.appendChild(img);
                    } else {
                        box.innerHTML = '<i class="ti ti-file-text ti-lg"></i>';
                    }
                    const label = document.createElement('div');
                    label.className = 'small text-truncate mt-1';
                    label.textContent = file.name;
                    box.appendChild(label);
                    dokumentasiList.appendChild(box);
                });

                renderAllPreviews();
            });

            // ----- Review -----
            function renderReview() {
                const el = document.getElementById('review-summary');
                if (!el) return;
                const rincianCount = document.querySelectorAll('#rincian-rows > div').length;
                const pengeluaranCount = document.querySelectorAll('#pengeluaran-rows > div').length;
                const pernyataanCount = document.querySelectorAll('#pernyataan-rows > div').length;
                const dokumentasiCount = dokumentasiInput.files.length;
                const fmtTanggalPanjang = DokumenPerjadin.fmtTanggalPanjang;
                const esc = DokumenPerjadin.esc;

                function item(ok, label) {
                    return `<li class="d-flex align-items-center gap-2 mb-2">
                        <i class="ti ${ok ? 'ti-circle-check text-success' : 'ti-circle-dashed text-muted'}"></i>
                        <span>${label}</span>
                    </li>`;
                }

                let html = '<ul class="list-unstyled mb-0">';
                html += item(!!val('input-no_surat_tugas') && !!val('input-perihal'), 'Data Umum & Surat Tugas: ' + (esc(val('input-perihal')) || '-'));
                @if ($butuhSpd)
                    html += item(!!val('input-no_spd'), 'SPD: ' + (esc(val('input-no_spd')) || 'belum diisi'));
                @endif
                html += item(rincianCount > 0, 'Rincian Biaya: ' + rincianCount + ' komponen');
                html += item(pengeluaranCount > 0, 'Pengeluaran Riil: ' + pengeluaranCount + ' item (opsional)');
                html += item(!!val('input-tanggal_kuitansi'), 'Kuitansi: ' + (val('input-tanggal_kuitansi') ? fmtTanggalPanjang(val('input-tanggal_kuitansi')) : 'belum diisi (opsional)'));
                html += item(pernyataanCount > 0, 'Surat Pernyataan: ' + pernyataanCount + ' pernyataan (opsional)');
                html += item(!!val('input-kesimpulan'), 'Laporan: ' + (val('input-kesimpulan') ? 'sudah diisi' : 'belum diisi (opsional)'));
                html += item(dokumentasiCount > 0, 'Dokumentasi: ' + dokumentasiCount + ' file (opsional)');
                html += '</ul>';
                el.innerHTML = html;
            }

            function renderAllPreviews() {
                const data = collectFormData();
                document.getElementById('preview-surat-tugas').innerHTML = DokumenPerjadin.renderSuratTugas(data);
                @if ($butuhSpd)
                    document.getElementById('preview-spd').innerHTML = DokumenPerjadin.renderSpd(data);
                @endif
                document.getElementById('preview-rincian-biaya').innerHTML = DokumenPerjadin.renderRincianBiaya(data);
                document.getElementById('preview-pengeluaran-riil').innerHTML = DokumenPerjadin.renderPengeluaranRiil(data);
                document.getElementById('preview-kuitansi').innerHTML = DokumenPerjadin.renderKuitansi(data);
                document.getElementById('preview-pernyataan').innerHTML = DokumenPerjadin.renderPernyataan(data);
                document.getElementById('preview-laporan').innerHTML = DokumenPerjadin.renderLaporan(data);
                renderReview();
            }

            form.addEventListener('input', renderAllPreviews);
            form.addEventListener('change', renderAllPreviews);
            stepperEl.addEventListener('shown.bs-stepper', renderAllPreviews);

            // ----- Isi ulang baris dari data lama (validasi gagal) atau baris default -----
            const oldRincian = @json(old('rincian', [['jenis_komponen' => 'uang_harian', 'nominal' => '']]));
            oldRincian.forEach(function (r) { addRincianRow(r); });

            const oldPengeluaran = @json(old('pengeluaran', []));
            oldPengeluaran.forEach(function (r) { addPengeluaranRow(r); });
            toggleEmptyState();

            const oldPernyataan = @json(old('pernyataan', []));
            oldPernyataan.forEach(function (r) { addPernyataanRow(r); });
            togglePernyataanEmptyState();

            renderAllPreviews();
        });
    </script>
@endpush
@endif
