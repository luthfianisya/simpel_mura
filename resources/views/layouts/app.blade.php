<!DOCTYPE html>
<html
  lang="id"
  class="light-style layout-navbar-fixed layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="{{ asset('assets') }}/"
  data-template="vertical-menu-template"
>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <title>@yield('title', 'Dashboard') | SIMPEL - BPS Murung Raya</title>
  <meta name="description" content="Sistem Informasi Penyusunan Dokumen Pertanggungjawaban Perjalanan Dinas Elektronik" />

  <link rel="icon" type="image/png" href="{{ asset('assets/img/branding/logo-bps.png') }}" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />

  <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}" class="template-customizer-core-css" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />

  <style>
    /* Header semua tabel disamakan dengan tabel Monitoring SPJ: biru BPS, teks putih,
       huruf kapital kecil, rata tengah. Termasuk header hasil kloning DataTables (scrollX).
       !important dipakai karena tema (.table:not(.table-dark) thead th) dan FixedColumns
       (kolom beku dtfc-fixed-*, dimuat lewat page-css) memaksa warna teks / latar putih. */
    .table > thead > tr > th,
    .dataTables_scrollHead thead th {
      background-color: #1B449C !important;
      color: #fff !important;
      border-bottom: 2px solid #1B449C;
      text-transform: uppercase;
      font-size: var(--bs-body-font-size);
      font-weight: 600;
      letter-spacing: 0.4px;
      white-space: nowrap;
      text-align: center;
      vertical-align: middle;
    }

    /* Menu sidebar bersubmenu (mis. Manajemen Pengguna): tema hanya memberi gradien biru ke
       menu aktif yang BUKAN .menu-toggle, jadi induknya tetap abu-abu walau halaman yang
       dibuka ada di dalamnya. Induk yang aktif ATAU sedang di-expand (.open) diberi gradien
       yang sama seperti menu lain, dan sub-item aktifnya cukup teks biru tebal + latar tipis
       supaya tidak dobel gradien. Latarnya !important karena aturan hover tema
       (.menu-item:not(.active) .menu-link:hover, lebih spesifik) menggantinya jadi #f8f8f8
       saat induk sedang dibuka — gradiennya hilang jadi putih ketika disorot. */
    .bg-menu-theme.menu-vertical .menu-inner > .menu-item.active > .menu-link.menu-toggle,
    .bg-menu-theme.menu-vertical .menu-inner > .menu-item.open > .menu-link.menu-toggle {
      background: linear-gradient(72.47deg, #1B449C 22.16%, #2563eb 76.47%) !important;
      box-shadow: 0px 2px 6px 0px rgba(37, 99, 235, 0.48);
      color: #fff !important;
    }
    .bg-menu-theme.menu-vertical .menu-sub > .menu-item.active > .menu-link:not(.menu-toggle) {
      background: rgba(37, 99, 235, 0.1);
      box-shadow: none;
      color: #2563eb !important;
      font-weight: 600;
    }

    /* Filter tabel jadi tombol dropdown (atur-kolom.js, jadikanDropdownFilter) bergaya sama
       dengan "Atur Kolom"; daftar pilihannya bisa di-scroll kalau panjang. */
    .dt-filter .dropdown-menu {
      max-height: 320px;
      overflow-y: auto;
    }

    /* Kotak "Cari data..." dan pilihan "Tampilkan [10] data" DataTables: setinggi & bergaya
       sama dengan tombol "Atur Kolom" (btn-outline-primary), kotak cari diberi ikon kaca pembesar. */
    .dataTables_wrapper .dataTables_filter label {
      margin: 0;
    }
    .dataTables_wrapper .dataTables_filter input.form-control,
    .dataTables_wrapper .dataTables_length select.form-select {
      height: calc(2.2rem + 2px);
      margin: 0;
      font-size: 0.9375rem;
      color: #2563eb;
      background-color: #fff;
      border: 1px solid #2563eb;
      border-radius: 0.375rem;
      transition: box-shadow 0.15s ease, background-color 0.15s ease;
    }
    .dataTables_wrapper .dataTables_filter input.form-control {
      width: 260px;
      padding: 0.4rem 0.9rem 0.4rem 2.35rem;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='10' cy='10' r='7'/%3E%3Cpath d='M21 21l-6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 0.8rem center;
      background-size: 1.05rem;
    }
    .dataTables_wrapper .dataTables_filter input.form-control::placeholder {
      color: rgba(37, 99, 235, 0.65);
    }
    .dataTables_wrapper .dataTables_length select.form-select {
      padding: 0.4rem 2.2rem 0.4rem 0.9rem;
      font-weight: 500;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.7rem center;
      background-size: 1rem;
    }
    .dataTables_wrapper .dataTables_filter input.form-control:hover,
    .dataTables_wrapper .dataTables_length select.form-select:hover {
      background-color: rgba(37, 99, 235, 0.04);
    }
    .dataTables_wrapper .dataTables_filter input.form-control:focus,
    .dataTables_wrapper .dataTables_length select.form-select:focus {
      background-color: #fff;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    @media (max-width: 575.98px) {
      .dataTables_wrapper .dataTables_filter input.form-control { width: 100%; }
    }

    /* Daftar kolom di tombol "Kolom" (atur-kolom.js): bisa di-scroll kalau kolomnya banyak. */
    .dt-atur-kolom .dropdown-menu {
      min-width: 230px;
      max-height: 340px;
      overflow-y: auto;
    }
    .dt-atur-kolom .form-check {
      margin-bottom: 0.35rem;
    }

    /* Bar aksi massal: muncul tepat di atas header tabel saat ada baris yang dicentang
       (lihat pilih-massal.js, yang memindahkannya ke sana setelah DataTables terpasang). */
    .bulk-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.5rem;
      margin: 0.75rem 1.25rem;
      padding: 0.5rem 0.75rem;
      background: rgba(37, 99, 235, 0.08);
      border: 1px solid rgba(37, 99, 235, 0.2);
      border-radius: 0.5rem;
    }
    .bulk-bar-info {
      color: #2563eb;
      font-weight: 600;
    }

    /* Tombol aksi di tabel (ikon Lihat/Edit/Hapus, dst). Kelas btn-text-* tidak ada di CSS
       tema, jadi didefinisikan di sini: ikon berwarna di latar transparan, lalu saat hover
       latarnya diberi warna pudar dan tombol sedikit terangkat. */
    .table .btn-icon.btn-sm {
      width: 1.875rem;
      height: 1.875rem;
      border: 0;
      border-radius: 0.5rem;
      background-color: transparent;
      transition: background-color 0.15s ease, color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    }
    .table .btn-icon.btn-sm .ti-sm {
      font-size: 1.125rem !important;
    }
    .table .btn-icon.btn-sm:hover,
    .table .btn-icon.btn-sm:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 2px 6px rgba(var(--btn-aksi-rgb), 0.2);
      background-color: rgba(var(--btn-aksi-rgb), 0.12);
    }
    .table .btn-icon.btn-sm:active {
      transform: translateY(0);
      box-shadow: none;
    }
    .btn-text-primary { --btn-aksi-rgb: var(--bs-primary-rgb); color: var(--bs-primary); }
    .btn-text-secondary { --btn-aksi-rgb: 111, 107, 125; color: #6f6b7d; }
    .btn-text-success { --btn-aksi-rgb: var(--bs-success-rgb); color: var(--bs-success); }
    .btn-text-warning { --btn-aksi-rgb: var(--bs-warning-rgb); color: #e98a2e; }
    .btn-text-danger { --btn-aksi-rgb: var(--bs-danger-rgb); color: var(--bs-danger); }
    .btn-text-primary:hover, .btn-text-secondary:hover, .btn-text-success:hover,
    .btn-text-warning:hover, .btn-text-danger:hover {
      color: rgb(var(--btn-aksi-rgb));
    }

    /* Tema memaksa .btn { display: inline-flex !important }, sehingga tombol SweetAlert yang
       disembunyikan lewat style="display: none" (mis. tombol "No"/deny) ikut tampil. */
    .swal2-actions .btn[style*="display: none"] {
      display: none !important;
    }
  </style>

  @stack('page-css')

  <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('assets/js/config.js') }}"></script>
</head>
<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      @include('components.sidebar')

      <div class="layout-page">
        @include('components.navbar')

        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            {{-- Dashboard adalah halaman awal, tidak perlu breadcrumb. --}}
            @unless (request()->routeIs('dashboard'))
              @include('components.breadcrumb')
            @endunless
            @yield('content')
          </div>

          @include('components.footer')
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
  <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
  <script src="{{ asset('assets/js/sweetalert-helpers.js') }}"></script>
  {{-- DataTables inti dimuat global; ekstensi (buttons, fixedcolumns) dimuat per halaman yang butuh. --}}
  <script src="{{ asset('assets/vendor/libs/datatables/jquery.dataTables.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/js/atur-kolom.js') }}?v={{ filemtime(public_path('assets/js/atur-kolom.js')) }}"></script>
  <script src="{{ asset('assets/js/tabel-data.js') }}?v={{ filemtime(public_path('assets/js/tabel-data.js')) }}"></script>
  <script src="{{ asset('assets/js/export-excel.js') }}?v={{ filemtime(public_path('assets/js/export-excel.js')) }}"></script>
  <script src="{{ asset('assets/js/pilih-massal.js') }}?v={{ filemtime(public_path('assets/js/pilih-massal.js')) }}"></script>
  <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>

  @stack('page-js')
</body>
</html>