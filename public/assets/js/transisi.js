/**
 * Isi halaman muncul halus (fade + naik sedikit) saat masuk ke layar: yang terlihat ketika
 * halaman dibuka muncul berurutan, sisanya muncul saat discroll. Gaya ada di assets/css/transisi.css.
 *
 * Alur tanpa kedipan: skrip kecil di <head> memasang "transisi-aktif" di <html> sehingga isi
 * halaman tersembunyi dulu. Skrip ini (dimuat di akhir <body>, sebelum halaman tergambar)
 * memilih blok-blok yang akan dianimasikan, menandainya "belum-muncul", lalu memasang
 * "transisi-siap" supaya sisanya langsung tampil.
 */
(function () {
  'use strict';

  var akar = document.documentElement;
  window.__transisiSiap = true;
  if (!akar.classList.contains('transisi-aktif')) return;

  var wadah = document.querySelector('.container-p-y');
  if (!wadah) {
    akar.classList.add('transisi-siap');
    return;
  }

  var batasTinggi = window.innerHeight * 0.6;
  var target = [];

  function dilewati(el) {
    if (/^(SCRIPT|STYLE|TEMPLATE|INPUT|LINK|META)$/.test(el.tagName)) return true;
    if (el.classList.contains('modal') || el.classList.contains('offcanvas')) return true;
    var gaya = getComputedStyle(el);
    // Elemen tersembunyi / melayang tidak perlu (dan tidak bisa) dianimasikan.
    return gaya.display === 'none' || gaya.position === 'fixed' || gaya.position === 'absolute';
  }

  // Pilih blok: kolom grid dipecah per kolom; pembungkus yang tinggi (mis. seluruh isi
  // dashboard dalam satu div) dibongkar ke anak-anaknya supaya bagian bawah muncul saat discroll.
  // Kartu tidak pernah dibongkar: satu kartu = satu blok.
  function pilih(induk) {
    Array.prototype.forEach.call(induk.children, function (el) {
      if (dilewati(el)) return;
      var bongkar = !el.classList.contains('card') && (
        el.classList.contains('row') ||
        (el.offsetHeight > batasTinggi && el.children.length > 1 && el.tagName !== 'FORM' && el.tagName !== 'TABLE')
      );
      if (bongkar) pilih(el);
      else target.push(el);
    });
  }
  pilih(wadah);

  target.forEach(function (el) { el.classList.add('belum-muncul'); });
  akar.classList.add('transisi-siap');

  function munculkan(el, jeda) {
    el.style.animationDelay = jeda + 'ms';
    el.classList.remove('belum-muncul');
    el.classList.add('sedang-muncul');
    el.addEventListener('animationend', function selesai(e) {
      if (e.target !== el) return;
      el.classList.remove('sedang-muncul');
      el.style.animationDelay = '';
      el.removeEventListener('animationend', selesai);
    });
  }

  var pengamat = new IntersectionObserver(function (entri) {
    var urutan = 0;
    entri.forEach(function (e) {
      if (!e.isIntersecting) return;
      pengamat.unobserve(e.target);
      // Yang masuk layar bersamaan muncul bergantian, jedanya dibatasi supaya tidak terasa lambat.
      munculkan(e.target, Math.min(urutan++ * 70, 350));
    });
  }, { rootMargin: '0px 0px -30px 0px' });

  target.forEach(function (el) { pengamat.observe(el); });
})();
