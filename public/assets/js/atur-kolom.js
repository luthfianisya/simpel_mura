'use strict';

/**
 * Tombol "Kolom" di sebelah kotak pencarian DataTables: pengguna bisa menyembunyikan /
 * menampilkan kolom tabel. Pilihannya diingat per tabel di browser (localStorage), jadi
 * tidak perlu diatur ulang tiap kali halaman dibuka.
 *
 *   SimpelAturKolom.pasang(api)   api = instance DataTable (mis. $('#x').DataTable())
 *
 * Kolom tanpa judul (checkbox pilih baris) dan kolom "Aksi" selalu tampil, tidak masuk daftar.
 *
 * Filter milik tabel ini (elemen ber-[data-filter-tabel="{id tabel}"] berisi 1 <select>)
 * ikut dipindah ke baris kontrol yang sama, tepat di sebelah kiri tombol "Atur Kolom", dan
 * ditampilkan sebagai tombol dropdown bergaya sama (lihat jadikanDropdownFilter).
 */
window.SimpelAturKolom = (function () {
    // <select> bawaan browser menampilkan daftar pilihannya lewat sistem operasi (kaku, tidak
    // bisa dibuat membulat). Jadi tampilannya diganti tombol dropdown Bootstrap, sementara
    // <select> aslinya tetap ada (disembunyikan) dan nilainya diubah lewat event "change" —
    // logika filter yang sudah menempel di <select> (mis. column().search()) tetap jalan.
    function jadikanDropdownFilter(el) {
        const select = el.querySelector('select');
        if (!select) return el;

        const wadah = document.createElement('div');
        wadah.className = 'dropdown dt-filter';
        wadah.innerHTML = '<button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">'
            + '<i class="ti ti-filter me-1"></i><span class="dt-filter-label"></span></button>'
            + '<ul class="dropdown-menu"></ul>';
        if (select.title) wadah.querySelector('button').title = select.title;
        const label = wadah.querySelector('.dt-filter-label');
        const menu = wadah.querySelector('.dropdown-menu');

        function segarkan() {
            const terpilih = select.options[select.selectedIndex];
            label.textContent = terpilih ? terpilih.textContent : '';
            menu.querySelectorAll('.dropdown-item').forEach(function (item) {
                item.classList.toggle('active', item.dataset.nilai === select.value);
            });
        }

        Array.from(select.options).forEach(function (opsi) {
            const li = document.createElement('li');
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item';
            item.dataset.nilai = opsi.value;
            item.textContent = opsi.textContent;
            item.addEventListener('click', function () {
                select.value = opsi.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                segarkan();
            });
            li.appendChild(item);
            menu.appendChild(li);
        });

        select.classList.add('d-none');
        wadah.appendChild(select);
        el.replaceWith(wadah);
        segarkan();
        return wadah;
    }

    function kunciPenyimpanan(api, indexTabel) {
        const id = api.table().node().id || ('tabel-' + indexTabel);
        return 'simpel:kolom-tersembunyi:' + window.location.pathname + ':' + id;
    }

    function bacaTersembunyi(kunci) {
        try {
            const nilai = JSON.parse(window.localStorage.getItem(kunci) || '[]');
            return Array.isArray(nilai) ? nilai : [];
        } catch (e) {
            return [];
        }
    }

    function simpanTersembunyi(kunci, daftar) {
        try {
            window.localStorage.setItem(kunci, JSON.stringify(daftar));
        } catch (e) {
            // localStorage tidak tersedia (mis. mode privat) — pilihan hanya berlaku sampai halaman ditutup.
        }
    }

    function judulKolom(api, index) {
        return (api.column(index).header().textContent || '').replace(/\s+/g, ' ').trim();
    }

    let jumlahTerpasang = 0;

    function pasang(api) {
        const indexTabel = jumlahTerpasang++;
        const kunci = kunciPenyimpanan(api, indexTabel);
        const kolom = api.columns().indexes().toArray().filter(function (i) {
            const judul = judulKolom(api, i);
            // data-atur-kolom="false" = kolom teknis (mis. kunci kelompok GUP) yang tidak boleh diatur.
            return judul !== '' && judul.toLowerCase() !== 'aksi' && api.column(i).header().dataset.aturKolom !== 'false';
        });
        if (!kolom.length) return;

        // Terapkan pilihan tersimpan (hanya indeks yang memang ada di daftar).
        const tersembunyi = bacaTersembunyi(kunci).filter(function (i) { return kolom.indexOf(i) !== -1; });
        tersembunyi.forEach(function (i) { api.column(i).visible(false, false); });
        api.columns.adjust().draw(false);

        const wadah = document.createElement('div');
        wadah.className = 'dropdown dt-atur-kolom';
        wadah.innerHTML = '<button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" '
            + 'data-bs-auto-close="outside" aria-expanded="false" title="Atur kolom yang ditampilkan">'
            + '<i class="ti ti-columns me-1"></i>Atur Kolom</button>'
            + '<div class="dropdown-menu dropdown-menu-end p-2"></div>';
        const menu = wadah.querySelector('.dropdown-menu');

        kolom.forEach(function (i) {
            const idCek = 'atur-kolom-' + indexTabel + '-' + i;
            const baris = document.createElement('div');
            baris.className = 'form-check';
            baris.innerHTML = '<input class="form-check-input" type="checkbox" id="' + idCek + '">'
                + '<label class="form-check-label" for="' + idCek + '"></label>';
            baris.querySelector('label').textContent = judulKolom(api, i);
            const cek = baris.querySelector('input');
            cek.checked = api.column(i).visible();
            cek.addEventListener('change', function () {
                api.column(i).visible(cek.checked, false);
                api.columns.adjust().draw(false);
                simpanTersembunyi(kunci, kolom.filter(function (k) { return !api.column(k).visible(); }));
            });
            menu.appendChild(baris);
        });

        const tampilkanSemua = document.createElement('button');
        tampilkanSemua.type = 'button';
        tampilkanSemua.className = 'btn btn-sm btn-label-primary w-100 mt-2';
        tampilkanSemua.textContent = 'Tampilkan semua';
        tampilkanSemua.addEventListener('click', function () {
            kolom.forEach(function (i) { api.column(i).visible(true, false); });
            api.columns.adjust().draw(false);
            menu.querySelectorAll('input[type="checkbox"]').forEach(function (c) { c.checked = true; });
            simpanTersembunyi(kunci, []);
        });
        menu.appendChild(tampilkanSemua);

        // Taruh di sebelah kiri kotak pencarian (baris kontrol atas DataTables).
        const filter = api.table().container().querySelector('.dataTables_filter');
        if (filter) {
            const kanan = filter.parentNode;
            kanan.insertBefore(wadah, filter);
            kanan.classList.add('gap-2', 'align-items-center', 'flex-wrap');
            // Baris kontrol bawaan dibagi 2 sama lebar (col-md-6) — terlalu sempit untuk
            // filter + Kolom + Cari. Sisi kanan dibuat mengisi sisa ruang, sisi kiri
            // ("Tampilkan ... data") cukup selebar isinya.
            if (kanan.classList.contains('col-md-6')) {
                kanan.classList.replace('col-md-6', 'col-md');
                if (kanan.previousElementSibling) kanan.previousElementSibling.classList.replace('col-md-6', 'col-md-auto');
            }

            const idTabel = api.table().node().id;
            if (idTabel) {
                document.querySelectorAll('[data-filter-tabel="' + idTabel + '"]').forEach(function (el) {
                    kanan.insertBefore(jadikanDropdownFilter(el), wadah);
                });
            }
        }
    }

    return { pasang: pasang };
})();
