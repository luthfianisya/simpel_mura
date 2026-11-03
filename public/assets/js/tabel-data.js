'use strict';

/**
 * DataTables standar SIMPEL untuk tabel biasa: cari, jumlah baris per halaman, info, dan
 * pagination, dengan bahasa & tata letak yang sama seperti tabel rekap perjalanan dinas,
 * plus tombol "Kolom" untuk menyembunyikan kolom (atur-kolom.js).
 *
 *   <table class="table" data-datatable data-kosong="Belum ada data.">
 *     <th data-orderable="false">   kolom yang tidak bisa diurutkan (checkbox, Aksi)
 *
 * Urutan awal mengikuti urutan dari server (order: []). Tabel yang butuh opsi khusus
 * (rekap perjadin, monitoring SPJ) tetap diinisialisasi sendiri di halamannya.
 */
window.SIMPEL_DATATABLE_BAHASA = {
    search: '',
    searchPlaceholder: 'Cari data...',
    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
    infoEmpty: 'Menampilkan 0 data',
    infoFiltered: '(disaring dari _MAX_ data)',
    lengthMenu: 'Tampilkan _MENU_ data',
    zeroRecords: 'Tidak ada data yang cocok dengan pencarian.',
    paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
    emptyTable: 'Belum ada data.',
};

document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.DataTable) return;

    document.querySelectorAll('table[data-datatable]').forEach(function (tabel) {
        if (jQuery.fn.dataTable.isDataTable(tabel)) return;

        const api = jQuery(tabel).DataTable({
            autoWidth: false,
            order: [],
            displayLength: 10,
            lengthMenu: [10, 25, 50, 100],
            language: Object.assign({}, window.SIMPEL_DATATABLE_BAHASA, {
                emptyTable: tabel.dataset.kosong || window.SIMPEL_DATATABLE_BAHASA.emptyTable,
            }),
            dom: '<"row mx-1 mt-3 align-items-center"' +
                '<"col-md-6"<"me-3"l>>' +
                '<"col-md-6 d-flex justify-content-md-end"f>' +
                '>' +
                't' +
                '<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        });
        window.SimpelAturKolom.pasang(api);
    });
});
