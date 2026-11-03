'use strict';

/**
 * Pilih banyak baris tabel untuk edit/hapus sekaligus. Dipakai lewat atribut HTML:
 *
 *   <table data-pilih-massal>                       tabelnya
 *     <input type="checkbox" class="cek-semua">       di header: centang/lepas semua baris
 *     <input type="checkbox" class="cek-baris" value="{id}">   di tiap baris
 *   [data-tampil-jika-terpilih]   elemen (tombol) yang hanya tampil kalau ada baris dicentang
 *   [data-jumlah-terpilih]        teksnya diisi jumlah baris yang dicentang
 *   <form data-isi-id-terpilih>   otomatis diisi input hidden ids[] sesuai baris yang dicentang
 *   [data-bulk-bar]               bar aksi massal; dipindah tepat ke atas header tabel
 *   [data-batal-pilih]            tombol untuk melepas semua centangan
 *
 * Kalau tabelnya DataTables (data-datatable), baris di halaman lain tidak ada di DOM,
 * jadi baris dibaca lewat API-nya: centang yang di halaman lain tetap terhitung, dan
 * "centang semua" berlaku untuk semua baris yang lolos pencarian (lintas halaman).
 * Script ini dimuat setelah tabel-data.js, jadi DataTables-nya sudah terpasang.
 */
document.addEventListener('DOMContentLoaded', function () {
    const tabel = document.querySelector('table[data-pilih-massal]');
    if (!tabel) return;

    const pakaiDataTables = window.jQuery && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable(tabel);
    const api = pakaiDataTables ? jQuery(tabel).DataTable() : null;

    // Bar aksi massal ditaruh tepat di atas header tabel: di bawah baris "Tampilkan/Cari"
    // DataTables (yang baru ada setelah DataTables terpasang, jadi dipindah lewat JS).
    const bar = document.querySelector('[data-bulk-bar]');
    if (bar) {
        const patokan = tabel.closest('.dataTables_scroll') || tabel;
        patokan.parentNode.insertBefore(bar, patokan);
    }

    function cekBaris(hanyaTersaring) {
        if (api) {
            const baris = api.rows(hanyaTersaring ? { search: 'applied' } : undefined).nodes().toArray();
            return baris.map(function (tr) { return tr.querySelector('.cek-baris'); }).filter(Boolean);
        }
        return Array.from(tabel.querySelectorAll('.cek-baris'));
    }

    // DataTables (scrollX) bisa mengkloning header, jadi checkbox "semua" dicari lewat class.
    function semuaCekSemua() {
        return Array.from(document.querySelectorAll('.cek-semua'));
    }

    function perbarui() {
        const terpilih = cekBaris(false).filter(function (c) { return c.checked; });

        document.querySelectorAll('[data-tampil-jika-terpilih]').forEach(function (el) {
            el.classList.toggle('d-none', terpilih.length === 0);
        });
        document.querySelectorAll('[data-jumlah-terpilih]').forEach(function (el) {
            el.textContent = terpilih.length;
        });
        document.querySelectorAll('form[data-isi-id-terpilih]').forEach(function (form) {
            form.querySelectorAll('input[name="ids[]"]').forEach(function (i) { i.remove(); });
            terpilih.forEach(function (c) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = c.value;
                form.appendChild(input);
            });
        });

        const tersaring = cekBaris(true);
        const jumlahTersaringTercentang = tersaring.filter(function (c) { return c.checked; }).length;
        semuaCekSemua().forEach(function (cek) {
            cek.checked = tersaring.length > 0 && jumlahTersaringTercentang === tersaring.length;
            cek.indeterminate = jumlahTersaringTercentang > 0 && jumlahTersaringTercentang < tersaring.length;
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-batal-pilih]')) {
            cekBaris(false).forEach(function (c) { c.checked = false; });
            perbarui();
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('cek-semua')) {
            cekBaris(true).forEach(function (c) { c.checked = e.target.checked; });
            perbarui();
        } else if (e.target.classList.contains('cek-baris')) {
            perbarui();
        }
    });
    if (api) {
        api.on('draw', perbarui);
    }

    perbarui();
});
