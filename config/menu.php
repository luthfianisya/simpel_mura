<?php

return [
    ['label' => 'Dashboard', 'icon' => 'ti ti-smart-home', 'route' => 'dashboard', 'roles' => ['administrator', 'pegawai', 'user']],
    ['label' => 'Perjalanan Dinas Saya', 'icon' => 'ti ti-briefcase', 'route' => 'perjalanan-dinas.saya', 'roles' => ['administrator', 'pegawai', 'user']],
    ['label' => 'Semua Perjalanan Dinas', 'icon' => 'ti ti-list-details', 'route' => 'perjalanan-dinas.index', 'roles' => ['administrator', 'pegawai', 'user']],
    // ['label' => 'Data Personil', 'icon' => 'ti ti-users', 'route' => 'pegawai-mitra.index', 'roles' => ['administrator']],
    // ['label' => 'Rincian Biaya', 'icon' => 'ti ti-file-invoice', 'route' => 'rincian-biaya.index', 'roles' => ['administrator', 'pegawai', 'user']],
    // ['label' => 'Laporan', 'icon' => 'ti ti-report', 'route' => 'laporan.index', 'roles' => ['administrator']],
    ['label' => 'Bantuan & FAQ', 'icon' => 'ti ti-help', 'route' => 'bantuan.index', 'roles' => ['administrator', 'pegawai', 'user']],
];