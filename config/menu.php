<?php

return [
    ['label' => 'Dashboard', 'icon' => 'ti ti-smart-home', 'route' => 'dashboard', 'roles' => ['administrator', 'pegawai', 'user']],
    ['label' => 'Perjalanan Dinas Saya', 'icon' => 'ti ti-briefcase', 'route' => 'perjalanan-dinas.saya', 'roles' => ['administrator', 'pegawai', 'user']],
    ['label' => 'Semua Perjalanan Dinas', 'icon' => 'ti ti-list-details', 'route' => 'perjalanan-dinas.index', 'roles' => ['administrator', 'pegawai', 'user']],
    ['label' => 'Monitoring SPJ', 'icon' => 'ti ti-clipboard-check', 'route' => 'monitoring-spj.index', 'roles' => ['administrator', 'pegawai', 'user']],
    // ['label' => 'Monitoring GUP', 'icon' => 'ti ti-wallet', 'route' => 'monitoring-gup.index', 'roles' => ['administrator', 'pegawai', 'user']],
    ['label' => 'Rate Transport dan Akomodasi', 'icon' => 'ti ti-coin', 'route' => 'rate.index', 'roles' => ['administrator', 'pegawai', 'user']],
    // Item dengan 'children' tampil sebagai menu yang bisa dibuka-tutup (submenu).
    ['label' => 'Manajemen Pengguna', 'icon' => 'ti ti-users', 'roles' => ['administrator'], 'children' => [
        ['label' => 'Pegawai', 'route' => 'pegawai-mitra.pegawai'],
        ['label' => 'Mitra', 'route' => 'pegawai-mitra.mitra'],
        ['label' => 'Akun', 'route' => 'user.index'],
    ]],
    ['label' => 'Kelola Wilayah', 'icon' => 'ti ti-map-pin', 'route' => 'wilayah.index', 'roles' => ['administrator']],
    // ['label' => 'Rincian Biaya', 'icon' => 'ti ti-file-invoice', 'route' => 'rincian-biaya.index', 'roles' => ['administrator', 'pegawai', 'user']],
    // ['label' => 'Laporan', 'icon' => 'ti ti-report', 'route' => 'laporan.index', 'roles' => ['administrator']],
    ['label' => 'Feedback', 'icon' => 'ti ti-message-report', 'route' => 'feedback.index', 'roles' => ['administrator', 'pegawai', 'user']],
    ['label' => 'Bantuan & FAQ', 'icon' => 'ti ti-help', 'route' => 'bantuan.index', 'roles' => ['administrator', 'pegawai', 'user']],
];