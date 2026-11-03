{{--
  Breadcrumb otomatis untuk semua halaman di layouts.app: Dashboard › [induk] › judul halaman.
  Judul diambil dari @section('title') halaman; induk hanya untuk halaman turunan yang
  tidak punya menu sendiri (mis. buat/ubah/lihat perjalanan dinas).
--}}
@php
    // @section('title', '...') sudah di-escape oleh Blade; di-decode dulu supaya "&" tidak
    // tampil sebagai "&amp;" saat dicetak ulang dengan {{ }}.
    $judulHalaman = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES, 'UTF-8');
    $induk = match (true) {
        request()->routeIs('perjalanan-dinas.create', 'perjalanan-dinas.edit', 'perjalanan-dinas.show')
            => ['label' => 'Semua Perjalanan Dinas', 'url' => route('perjalanan-dinas.index')],
        default => null,
    };
    // Halaman di dalam submenu sidebar (config/menu.php 'children') diberi induk nama
    // grupnya, mis. Dashboard › Manajemen Pengguna › Kelola Pegawai (tanpa tautan, karena
    // grupnya sendiri bukan halaman).
    foreach (config('menu') as $menu) {
        if (!$induk && !empty($menu['children']) && request()->routeIs(...array_column($menu['children'], 'route'))) {
            $induk = ['label' => $menu['label'], 'url' => null];
        }
    }
@endphp

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}"><i class="ti ti-smart-home ti-xs me-1"></i>Dashboard</a>
      </li>
      @if ($induk)
        <li class="breadcrumb-item">
          @if ($induk['url'])
            <a href="{{ $induk['url'] }}">{{ $induk['label'] }}</a>
          @else
            {{ $induk['label'] }}
          @endif
        </li>
      @endif
      <li class="breadcrumb-item active" aria-current="page">{{ $judulHalaman }}</li>
  </ol>
</nav>
