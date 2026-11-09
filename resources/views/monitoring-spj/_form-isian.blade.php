{{--
  Isian kolom manual Monitoring SPJ, dipakai bersama oleh modal "Edit" (satu baris) dan
  "Edit Terpilih" (banyak baris) supaya tampilannya sama persis.
  $bulk = true  → pilihan kosong berarti "Tetap" (tidak diubah), dan centang Kelengkapan
                  punya 3 keadaan: – (tidak diubah), ✓ (sudah), kosong (belum). Nilainya
                  dikirim lewat input tersembunyi yang baru aktif setelah centangnya diklik.
  $bulk = false → centang biasa (name langsung di checkbox).
--}}
@php
    $kosong = $bulk ? 'Tetap' : '-';
    $kelengkapan = ['sudah_dibayar' => 'Pembayaran', 'sudah_spby' => 'SPBy', 'sudah_kuitansi' => 'Kuitansi', 'sudah_drpp' => 'DRPP'];
@endphp

<h6 class="judul-isian-spj">Dokumen Pembayaran</h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">Nomor SPP/SPM</label>
        <input type="text" name="no_spp_spm" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tanggal SPP</label>
        <input type="date" name="tanggal_spp" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tanggal SPM</label>
        <input type="date" name="tanggal_spm" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Nomor SP2D</label>
        <input type="text" name="no_sp2d" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tanggal SP2D</label>
        <input type="date" name="tanggal_sp2d" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tanggal Diperiksa PPK</label>
        <input type="date" name="tanggal_diperiksa_ppk" class="form-control">
    </div>
</div>

<h6 class="judul-isian-spj">Status SPJ</h6>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Posisi SPJ</label>
        <select name="posisi_spj" class="form-select">
            <option value="">{{ $kosong }}</option>
            @foreach ($posisiSpj as $posisi)
                <option value="{{ $posisi }}">{{ $posisi }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Jenis Pembayaran</label>
        <select name="jenis_pembayaran" class="form-select">
            <option value="">{{ $kosong }}</option>
            @foreach ($jenisPembayaran as $jenis)
                <option value="{{ $jenis }}">{{ $jenis }}</option>
            @endforeach
        </select>
    </div>
    {{-- Tanggal GUP tidak diisi di sini: mengikuti GUP tempat SPJ dikelompokkan (tombol
         "Kelompokkan ke GUP" di bar aksi massal). Jenis pembayaran selain GUP = keluar dari GUP. --}}

    <div class="col-12">
        <div class="kotak-kelengkapan-spj">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <span class="form-label mb-0">Kelengkapan</span>
                @if ($bulk)
                    <small class="text-muted"><i class="ti ti-minus ti-xs"></i> = tidak diubah</small>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-4">
                <div class="form-check">
                    <input class="form-check-input cek-centang-semua" type="checkbox" id="{{ $prefix }}-centang-semua">
                    <label class="form-check-label fw-semibold" for="{{ $prefix }}-centang-semua">Centang Semua</label>
                </div>
                @foreach ($kelengkapan as $field => $label)
                    <div class="form-check">
                        @if ($bulk)
                            <input class="form-check-input cek-status-spj" type="checkbox" data-field="{{ $field }}" id="{{ $prefix }}-{{ $field }}">
                            <input type="hidden" name="{{ $field }}" value="" disabled>
                        @else
                            <input class="form-check-input cek-status-spj" type="checkbox" data-field="{{ $field }}" name="{{ $field }}" value="1" id="{{ $prefix }}-{{ $field }}">
                        @endif
                        <label class="form-check-label" for="{{ $prefix }}-{{ $field }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Tunai/Transfer</label>
        <select name="cara_bayar" class="form-select" disabled title="Centang Pembayaran terlebih dahulu">
            <option value="">{{ $kosong }}</option>
            @foreach ($caraBayar as $nilai => $label)
                <option value="{{ $nilai }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label">Keterangan</label>
        <input type="text" name="keterangan" class="form-control" maxlength="255">
    </div>
</div>
