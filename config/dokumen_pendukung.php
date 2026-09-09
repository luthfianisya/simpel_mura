<?php

// Dokumen referensi statis (SK, nota, peraturan) yang bisa diunduh siapa saja yang
// login — filenya disimpan di storage/app/public/dokumen-pendukung. Admin ganti file
// langsung di folder itu kalau ada revisi baru, tidak perlu form upload.
return [
    'sk-rate-akomodasi' => [
        'label' => 'SK Rate Akomodasi',
        'keterangan' => 'SK Kuasa Pengguna Anggaran tentang besaran rate akomodasi dalam kota.',
        'file' => 'SK Rate Akomodasi.pdf',
    ],
    'sk-rate-transport' => [
        'label' => 'SK Rate Transport Dalam Kota',
        'keterangan' => 'SK Kuasa Pengguna Anggaran tentang besaran rate transport perjalanan dinas dalam kota.',
        'file' => 'SK Rate Transport Dalam Kota.pdf',
    ],
    'nota-tempel' => [
        'label' => 'Nota Tempel',
        'keterangan' => 'Template nota tempel untuk pertanggungjawaban pengeluaran riil.',
        'file' => 'Nota Tempel.xlsx',
    ],
    'perka-bps-13-2026' => [
        'label' => 'PERKA BPS No. 13 Tahun 2026',
        'keterangan' => 'Peraturan Kepala BPS tentang Petunjuk Pelaksanaan Anggaran Kegiatan Sensus dan Survei.',
        'file' => 'PERKA BPS No 13 Tahun 2026 tentang PAK Sensus dan Survei.pdf',
    ],
];
