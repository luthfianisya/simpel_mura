/**
 * Gaya file Excel hasil tombol "Export Excel" DataTables (excelHtml5), dipakai bersama oleh
 * semua tabel yang punya export:
 *
 *   {
 *     extend: 'excel',
 *     title: 'Judul',
 *     exportOptions: { columns: ..., format: { body: SimpelExportExcel.isiSel } },
 *     customize: SimpelExportExcel.rapikan,
 *   }
 *
 * Hasilnya: judul besar, header biru tua (#1B449C) teks putih tebal, border tipis, baris
 * belang (zebra), angka berformat ribuan, header dibekukan + filter otomatis.
 */
(function () {
  'use strict';

  var NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
  var BIRU = 'FF1B449C';
  var GARIS = 'FFC9D3E6';
  var BELANG = 'FFF3F6FC';

  function el(doc, nama, atribut, anak) {
    var node = doc.createElementNS(NS, nama);
    Object.keys(atribut || {}).forEach(function (k) { node.setAttribute(k, atribut[k]); });
    (anak || []).forEach(function (a) { node.appendChild(a); });
    return node;
  }

  // Tambah elemen ke <fonts>/<fills>/<borders>/<cellXfs>, perbarui count, kembalikan index-nya.
  function tambah(doc, wadahNama, node) {
    var wadah = doc.getElementsByTagName(wadahNama)[0];
    wadah.appendChild(node);
    var jumlah = wadah.childNodes.length;
    wadah.setAttribute('count', jumlah);
    return jumlah - 1;
  }

  function font(doc, opsi) {
    var anak = [];
    if (opsi.tebal) anak.push(el(doc, 'b'));
    anak.push(el(doc, 'sz', { val: opsi.ukuran || 11 }));
    anak.push(el(doc, 'color', { rgb: opsi.warna || 'FF2F3349' }));
    anak.push(el(doc, 'name', { val: 'Calibri' }));
    anak.push(el(doc, 'family', { val: 2 }));
    return tambah(doc, 'fonts', el(doc, 'font', {}, anak));
  }

  function isi(doc, warna) {
    return tambah(doc, 'fills', el(doc, 'fill', {}, [
      el(doc, 'patternFill', { patternType: 'solid' }, [
        el(doc, 'fgColor', { rgb: warna }),
        el(doc, 'bgColor', { indexed: 64 }),
      ]),
    ]));
  }

  function garisTipis(doc) {
    return tambah(doc, 'borders', el(doc, 'border', {}, ['left', 'right', 'top', 'bottom', 'diagonal'].map(function (sisi) {
      return sisi === 'diagonal'
        ? el(doc, sisi)
        : el(doc, sisi, { style: 'thin' }, [el(doc, 'color', { rgb: GARIS })]);
    })));
  }

  function gaya(doc, opsi) {
    var atribut = {
      numFmtId: opsi.angka ? 3 : 0, // 3 = #,##0
      fontId: opsi.font || 0,
      fillId: opsi.isi || 0,
      borderId: opsi.garis || 0,
      applyFont: 1, applyFill: 1, applyBorder: 1, applyAlignment: 1,
    };
    if (opsi.angka) atribut.applyNumberFormat = 1;
    return tambah(doc, 'cellXfs', el(doc, 'xf', atribut, [
      el(doc, 'alignment', {
        vertical: 'center',
        horizontal: opsi.rata || (opsi.angka ? 'right' : 'left'),
        wrapText: opsi.bungkus ? 1 : 0,
      }),
    ]));
  }

  // 0 → A, 25 → Z, 26 → AA, ...
  function hurufKolom(i) {
    var huruf = '';
    for (i += 1; i > 0; i = Math.floor((i - 1) / 26)) {
      huruf = String.fromCharCode(65 + ((i - 1) % 26)) + huruf;
    }
    return huruf;
  }

  function rapikan(xlsx) {
    var styles = xlsx.xl['styles.xml'];
    var sheet = xlsx.xl.worksheets['sheet1.xml'];

    var fontJudul = font(styles, { tebal: true, ukuran: 14, warna: BIRU });
    var fontHeader = font(styles, { tebal: true, warna: 'FFFFFFFF' });
    var fillHeader = isi(styles, BIRU);
    var fillBelang = isi(styles, BELANG);
    var garis = garisTipis(styles);

    var s = {
      judul: gaya(styles, { font: fontJudul }),
      header: gaya(styles, { font: fontHeader, isi: fillHeader, garis: garis, rata: 'center', bungkus: true }),
      teks: gaya(styles, { garis: garis }),
      teksBelang: gaya(styles, { garis: garis, isi: fillBelang }),
      angka: gaya(styles, { garis: garis, angka: true }),
      angkaBelang: gaya(styles, { garis: garis, isi: fillBelang, angka: true }),
    };

    var baris = Array.prototype.slice.call(sheet.getElementsByTagName('row'));
    // Baris 1 = judul kalau ada sel gabungan (DataTables menulis judul + mergeCell di baris 1).
    var adaJudul = sheet.getElementsByTagName('mergeCell').length > 0;
    var barisHeader = adaJudul ? 2 : 1;

    // DataTables tidak menulis sel untuk nilai kosong, jadi sel kosong tidak bisa diberi
    // border/warna. Lengkapi dulu semua sel dari kolom A sampai kolom terakhir header.
    var rowHeader = baris.filter(function (row) { return parseInt(row.getAttribute('r'), 10) === barisHeader; })[0];
    var jumlahKolom = rowHeader ? rowHeader.getElementsByTagName('c').length : 0;
    baris.forEach(function (row) {
      var r = parseInt(row.getAttribute('r'), 10);
      if (r < barisHeader) return;
      var ada = {};
      Array.prototype.slice.call(row.getElementsByTagName('c')).forEach(function (c) {
        ada[c.getAttribute('r').replace(/\d+/g, '')] = c;
      });
      for (var i = 0; i < jumlahKolom; i++) {
        var huruf = hurufKolom(i);
        var c = ada[huruf] || el(sheet, 'c', { r: huruf + r });
        row.appendChild(c); // appendChild memindahkan sel yang sudah ada → urutannya rapi A, B, C, ...
      }
    });

    baris.forEach(function (row) {
      var r = parseInt(row.getAttribute('r'), 10);
      var sel = Array.prototype.slice.call(row.getElementsByTagName('c'));
      if (adaJudul && r === 1) {
        row.setAttribute('ht', 28);
        row.setAttribute('customHeight', 1);
        sel.forEach(function (c) { c.setAttribute('s', s.judul); });
        return;
      }
      if (r === barisHeader) {
        row.setAttribute('ht', 32);
        row.setAttribute('customHeight', 1);
        sel.forEach(function (c) { c.setAttribute('s', s.header); });
        return;
      }
      var belang = (r - barisHeader) % 2 === 0;
      sel.forEach(function (c) {
        var angka = c.getAttribute('t') === 'n' || (!c.getAttribute('t') && c.getElementsByTagName('v').length);
        c.setAttribute('s', angka ? (belang ? s.angkaBelang : s.angka) : (belang ? s.teksBelang : s.teks));
      });
    });

    // Lebar kolom: sedikit lebih lega dari hitungan DataTables, dibatasi supaya tidak kelewat lebar.
    Array.prototype.slice.call(sheet.getElementsByTagName('col')).forEach(function (col) {
      var lebar = parseFloat(col.getAttribute('width')) || 10;
      col.setAttribute('width', Math.min(Math.max(lebar + 2, 12), 60));
    });

    // Bekukan baris header & pasang filter otomatis di header.
    var worksheet = sheet.documentElement;
    var cols = sheet.getElementsByTagName('cols')[0];
    var sheetViews = el(sheet, 'sheetViews', {}, [
      el(sheet, 'sheetView', { workbookViewId: 0 }, [
        el(sheet, 'pane', { ySplit: barisHeader, topLeftCell: 'A' + (barisHeader + 1), activePane: 'bottomLeft', state: 'frozen' }),
      ]),
    ]);
    worksheet.insertBefore(sheetViews, cols || sheet.getElementsByTagName('sheetData')[0]);

    var header = baris.filter(function (row) { return parseInt(row.getAttribute('r'), 10) === barisHeader; })[0];
    var selHeader = header ? header.getElementsByTagName('c') : [];
    if (selHeader.length) {
      var kolomAkhir = selHeader[selHeader.length - 1].getAttribute('r').replace(/\d+/g, '');
      var barisAkhir = baris.length ? baris[baris.length - 1].getAttribute('r') : barisHeader;
      var sheetData = sheet.getElementsByTagName('sheetData')[0];
      worksheet.insertBefore(el(sheet, 'autoFilter', { ref: 'A' + barisHeader + ':' + kolomAkhir + barisAkhir }), sheetData.nextSibling);
    }
  }

  // Nilai sel: kolom angka (td.text-end dengan data-order numerik, mis. Nominal) diekspor
  // sebagai angka murni supaya bisa dijumlah di Excel, bukan teks "80.000".
  function isiSel(data, baris, kolom, node) {
    if (node && node.classList && node.classList.contains('text-end')) {
      var urut = node.getAttribute('data-order');
      if (urut !== null && urut !== '' && !isNaN(urut)) return Number(urut);
    }
    // Sama dengan format bawaan Buttons: buang tag HTML (badge, ikon), spasi & entity.
    return $.fn.dataTable.Buttons.stripData(data);
  }

  window.SimpelExportExcel = { rapikan: rapikan, isiSel: isiSel };
})();
