<?php

return [
    ['label' => 'Dashboard', 'icon' => 'ti ti-smart-home', 'route' => 'dashboard', 'roles' => ['administrator', 'pegawai']],
    ['label' => 'Data Personil', 'icon' => 'ti ti-users', 'route' => 'personil.index', 'roles' => ['administrator']],
    ['label' => 'Perjalanan Dinas', 'icon' => 'ti ti-briefcase', 'route' => 'perjalanan-dinas.index', 'roles' => ['administrator', 'pegawai']],
    ['label' => 'Rincian Biaya', 'icon' => 'ti ti-file-invoice', 'route' => 'rincian-biaya.index', 'roles' => ['administrator', 'pegawai']],
    ['label' => 'Laporan', 'icon' => 'ti ti-report', 'route' => 'laporan.index', 'roles' => ['administrator']],
];