{{-- Portal aplikasi BPS Kabupaten Murung Raya (route "/", routes/portal.php). --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Portal Aplikasi | BPS Kabupaten Murung Raya</title>
  <meta name="description" content="Portal aplikasi internal BPS Kabupaten Murung Raya." />
  <link rel="icon" type="image/png" href="{{ asset('assets/img/branding/logo-bps.png') }}" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <style>
    :root {
      --biru-tua: #1b449c;
      --biru: #2563eb;
      --teks: #2f3349;
      --teks-redup: #6b7896;
      --garis: #e3e7f1;
      --latar: #f4f7fd;
      --kartu: #ffffff;
      --hijau: #16a34a;
      --abu: #8a94ad;
    }

    * { box-sizing: border-box; }

    body {
      /* Footer selalu di dasar layar walau aplikasinya sedikit. */
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      margin: 0;
      font-family: 'Public Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
      color: var(--teks);
      background: var(--latar);
      -webkit-font-smoothing: antialiased;
    }

    a { color: inherit; text-decoration: none; }

    /* Konten melebar hampir penuh layar; jarak kiri-kanan menyesuaikan lebar layar
       (20px di HP, sampai 48px di layar besar). */
    .wadah {
      width: 100%;
      max-width: 1600px;
      margin: 0 auto;
      padding: 0 clamp(20px, 3vw, 48px);
    }

    /* ---------- Bagian atas ---------- */
    .atas {
      position: relative;
      overflow: hidden;
      color: #fff;
      background: linear-gradient(120deg, var(--biru-tua) 0%, var(--biru) 100%);
      padding-bottom: 96px;
    }
    .atas::before,
    .atas::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.07);
      pointer-events: none;
    }
    .atas::before { width: 520px; height: 520px; right: -140px; top: -220px; }
    .atas::after { width: 280px; height: 280px; left: -90px; bottom: -150px; }

    .navbar {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 22px 0;
    }
    .merek {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .merek-logo {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      background: #fff;
      display: grid;
      place-items: center;
      box-shadow: 0 6px 16px -8px rgba(0, 0, 0, 0.35);
    }
    .merek-logo img { width: 32px; height: auto; }
    .merek-teks strong { display: block; font-size: 1rem; letter-spacing: 0.2px; }
    .merek-teks span { font-size: 0.8rem; opacity: 0.85; }
    .jam {
      font-size: 0.85rem;
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.14);
      white-space: nowrap;
    }

    .sambutan {
      position: relative;
      padding: 40px 0 0;
      max-width: 720px;
    }
    .sambutan .label {
      display: inline-block;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      padding: 6px 12px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.16);
    }
    .sambutan h1 {
      margin: 16px 0 12px;
      font-size: clamp(1.8rem, 4vw, 2.6rem);
      line-height: 1.2;
      font-weight: 800;
    }
    .sambutan p {
      margin: 0;
      font-size: 1.02rem;
      line-height: 1.6;
      opacity: 0.9;
    }

    /* ---------- Pencarian ---------- */
    .cari {
      position: relative;
      margin-top: -34px;
      z-index: 2;
    }
    .cari-kotak {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0 18px;
      background: var(--kartu);
      border-radius: 14px;
      box-shadow: 0 18px 40px -20px rgba(27, 68, 156, 0.45);
    }
    .cari-kotak svg { flex-shrink: 0; color: var(--biru); }
    .cari-kotak input {
      flex: 1;
      min-width: 0;
      height: 64px;
      border: 0;
      outline: 0;
      font: inherit;
      font-size: 1rem;
      color: var(--teks);
      background: transparent;
    }
    .cari-kotak input::placeholder { color: var(--abu); }
    .cari-kotak kbd {
      font: inherit;
      font-size: 0.75rem;
      color: var(--abu);
      padding: 3px 8px;
      border: 1px solid var(--garis);
      border-radius: 6px;
    }

    /* ---------- Daftar aplikasi ---------- */
    /* main juga .wadah (padding kiri-kanan), jadi padding atas/bawahnya ditulis lebih spesifik. */
    main.wadah { flex: 1; padding-top: 40px; padding-bottom: 56px; }
    .judul-bagian {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 18px;
    }
    .judul-bagian h2 { margin: 0; font-size: 1.15rem; }
    .judul-bagian span { color: var(--teks-redup); font-size: 0.9rem; }

    /* 3 kolom lega di layar laptop/monitor, 1 kolom di HP. */
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(min(380px, 100%), 1fr));
      gap: 28px;
    }

    /* Kartu besar & bersih. Warna aksen per aplikasi (--aksen-1 gelap, --aksen-2 terang)
       diisi dari properti "warna" di daftar APLIKASI, dipakai di ikon & tombol. */
    .kartu {
      --aksen-1: var(--biru-tua);
      --aksen-2: var(--biru);
      display: flex;
      flex-direction: column;
      padding: 36px;
      background: var(--kartu);
      border: 1px solid #edf0f6;
      border-radius: 22px;
      box-shadow: 0 2px 6px -2px rgba(27, 68, 156, 0.06);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      animation: muncul 0.45s cubic-bezier(0.22, 0.61, 0.36, 1) backwards;
    }
    .kartu:hover {
      transform: translateY(-4px);
      box-shadow: 0 22px 44px -26px color-mix(in srgb, var(--aksen-1) 60%, transparent);
    }

    .kartu-atas {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 30px;
    }
    .ikon {
      flex-shrink: 0;
      width: 84px;
      height: 84px;
      border-radius: 20px;
      display: grid;
      place-items: center;
      color: #fff;
      background: linear-gradient(135deg, var(--aksen-1), var(--aksen-2));
      box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--aksen-1) 90%, transparent);
    }
    .kategori {
      font-size: 0.92rem;
      font-weight: 600;
      color: #3d4663;
      padding: 7px 16px;
      border-radius: 999px;
      background: #f1f3f8;
      white-space: nowrap;
    }
    .kartu.nonaktif .ikon { color: var(--abu); background: #eef1f7; box-shadow: none; }

    .kartu h3 {
      margin: 0 0 12px;
      font-size: 1.9rem;
      font-weight: 700;
      line-height: 1.2;
      color: #161b2e;
    }
    .kartu .keterangan {
      margin: 0 0 32px;
      flex: 1;
      color: var(--teks-redup);
      font-size: 1.1rem;
      line-height: 1.55;
    }

    .tombol {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      height: 58px;
      border-radius: 14px;
      font-size: 1.12rem;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, var(--aksen-1), var(--aksen-2));
      transition: filter 0.2s ease, box-shadow 0.2s ease;
    }
    .tombol svg { transition: transform 0.2s ease; }
    .tombol:hover,
    .tombol:focus-visible {
      filter: brightness(1.06);
      box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--aksen-1) 95%, transparent);
      outline: none;
    }
    .tombol:hover svg { transform: translateX(3px); }
    .tombol.nonaktif {
      color: var(--abu);
      background: #eef1f7;
      cursor: default;
    }

    .kosong {
      display: none;
      padding: 48px 20px;
      text-align: center;
      color: var(--teks-redup);
      background: var(--kartu);
      border: 1px dashed var(--garis);
      border-radius: 16px;
    }

    footer {
      padding: 22px 0 28px;
      border-top: 1px solid var(--garis);
      color: var(--teks-redup);
      font-size: 0.85rem;
    }
    footer .wadah {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 8px;
    }

    @keyframes muncul {
      from { opacity: 0; transform: translateY(14px); }
    }

    @media (max-width: 575.98px) {
      .jam { display: none; }
      .atas { padding-bottom: 80px; }
      .sambutan { padding-top: 24px; }
      .cari-kotak kbd { display: none; }
      .grid { gap: 18px; }
      .kartu { padding: 26px; }
      .ikon { width: 68px; height: 68px; border-radius: 17px; }
      .kartu h3 { font-size: 1.6rem; }
    }

    @media (prefers-reduced-motion: reduce) {
      .kartu { animation: none; transition: none; }
    }
  </style>
</head>
<body>
  <header class="atas">
    <div class="wadah">
      <nav class="navbar">
        <div class="merek">
          <span class="merek-logo"><img src="{{ asset('assets/img/branding/logo-bps.png') }}" alt="Logo BPS" /></span>
          <span class="merek-teks">
            <strong>BPS Kabupaten Murung Raya</strong>
            <span>Portal Aplikasi Internal</span>
          </span>
        </div>
        <span class="jam" id="jam"></span>
      </nav>

      <div class="sambutan">
        <span class="label">Portal Aplikasi</span>
        <h1 id="salam">Selamat datang</h1>
        <p>Satu pintu untuk seluruh aplikasi internal BPS Kabupaten Murung Raya.<br>Pilih aplikasi yang ingin Anda gunakan di bawah ini.</p>
      </div>
    </div>
  </header>

  <div class="wadah cari">
    <label class="cari-kotak">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M21 21l-6-6"/></svg>
      <input type="search" id="cari" placeholder="Cari aplikasi..." autocomplete="off" aria-label="Cari aplikasi" />
      <kbd>/</kbd>
    </label>
  </div>

  <main class="wadah">
    <div class="judul-bagian">
      <h2>Daftar Aplikasi</h2>
      <span id="jumlah"></span>
    </div>
    <div class="grid" id="daftar"></div>
    <div class="kosong" id="kosong">Tidak ada aplikasi yang cocok dengan pencarian.</div>
  </main>

  <footer>
    <div class="wadah">
      <span>&copy; <span id="tahun"></span> BPS Kabupaten Murung Raya</span>
      <span>Badan Pusat Statistik Kabupaten Murung Raya</span>
    </div>
  </footer>

  <script>
    /*
     * DAFTAR APLIKASI — tambah / ubah kartu cukup di sini.
     *
     *   nama       : nama aplikasi
     *   kategori   : label kecil di kanan atas kartu (mis. "Keuangan")
     *   singkatan  : kepanjangan / keterangan singkat di bawah nama (yang tampil di kartu)
     *   deskripsi  : penjelasan tambahan (tidak tampil, hanya ikut dicari)
     *   url        : alamat aplikasi. Awali "/" untuk aplikasi di domain portal ini
     *                (mis. "/aplikasi-lain/"), atau alamat lengkap "https://..." untuk situs lain.
     *                SIMPEL memakai url('simpel') supaya ikut APP_URL.
     *                Kosongkan ("") kalau aplikasinya belum tersedia.
     *   ikon       : salah satu kunci di IKON (dokumen, web, grafik, pengguna, arsip, kalender,
     *                perisai, megafon, gedung, target, segera)
     *   status     : "aktif", "eksternal" (dibuka di tab baru), atau "segera" (belum bisa diklik)
     *   warna      : dua warna gradien [gelap, terang], mis. ['#c2410c', '#fb923c'] untuk oranye
     */
    var APLIKASI = [
      {
        nama: 'SipArsip 2026',
        kategori: 'Tata Usaha',
        singkatan: 'Sistem Informasi Pengelolaan Arsip',
        deskripsi: 'Pengelolaan arsip BPS Kabupaten Murung Raya tahun 2026.',
        url: 'http://s.bps.go.id/siparsip',
        ikon: 'arsip',
        status: 'eksternal',
        warna: ['#b45309', '#fbbf24'],
      },
      {
        nama: 'HURA',
        kategori: 'Kehumasan',
        singkatan: 'Humas BPS Kabupaten Murung Raya',
        deskripsi: 'Kanal kehumasan BPS Kabupaten Murung Raya.',
        url: 'https://sites.google.com/view/humas6213/home',
        ikon: 'megafon',
        status: 'eksternal',
        warna: ['#be185d', '#f472b6'],
      },
      {
        nama: 'ZI-Mura',
        kategori: 'Zona Integritas',
        singkatan: 'Pembangunan Zona Integritas BPS Kabupaten Murung Raya',
        deskripsi: 'Informasi dan dokumentasi pembangunan Zona Integritas BPS Kabupaten Murung Raya.',
        url: 'https://sites.google.com/view/zi-mura2025/home',
        ikon: 'perisai',
        status: 'eksternal',
        warna: ['#047857', '#34d399'],
      },
      {
        nama: 'SEMESTA',
        kategori: 'Akuntabilitas Kinerja',
        singkatan: 'Sistem Manajemen Akuntabilitas Kinerja dan Tata Kelola',
        deskripsi: 'Manajemen akuntabilitas kinerja dan tata kelola BPS Kabupaten Murung Raya.',
        url: 'https://sites.google.com/view/semesta6213',
        ikon: 'target',
        status: 'eksternal',
        warna: ['#6d28d9', '#a78bfa'],
      },
      {
        nama: 'POSE',
        kategori: 'Sensus Ekonomi',
        singkatan: 'Portal Sensus Ekonomi 2026',
        deskripsi: 'Portal informasi Sensus Ekonomi 2026 BPS Kabupaten Murung Raya.',
        url: 'https://sites.google.com/view/pose26/pose26',
        ikon: 'gedung',
        status: 'eksternal',
        warna: ['#c2410c', '#fb923c'],
      },
      {
        nama: 'Pass Ya!',
        kategori: 'Statistik Sektoral',
        singkatan: 'Pembinaan Statistik Sektoral',
        deskripsi: 'Program pembinaan statistik sektoral BPS Kabupaten Murung Raya.',
        url: 'https://sites.google.com/view/project-ist-2024-pass-ya/home',
        ikon: 'grafik',
        status: 'eksternal',
        warna: ['#0e7490', '#22d3ee'],
      },
      {
        nama: 'SIMPEL',
        kategori: 'Keuangan',
        singkatan: 'Sistem Informasi Penyusunan Dokumen Pertanggungjawaban Perjalanan Dinas Elektronik',
        // deskripsi: 'Penyusunan SPD, kuitansi, rincian biaya, dan monitoring SPJ perjalanan dinas secara elektronik.',
        url: @json(url('simpel')),
        ikon: 'dokumen',
        status: 'aktif',
        warna: ['#1b449c', '#3b82f6'],
      },
    ];

    var IKON = {
      dokumen: '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9 13h6M9 17h6M9 9h1"/>',
      web: '<circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8"/><path d="M11.5 3a17 17 0 0 0 0 18M12.5 3a17 17 0 0 1 0 18"/>',
      grafik: '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
      pengguna: '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.85"/>',
      arsip: '<rect x="3" y="4" width="18" height="4" rx="2"/><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8M10 12h4"/>',
      kalender: '<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M16 3v4M8 3v4M4 11h16"/>',
      perisai: '<path d="M12 3a12 12 0 0 0 8.5 3A12 12 0 0 1 12 21 12 12 0 0 1 3.5 6 12 12 0 0 0 12 3"/><path d="M9 12l2 2 4-4"/>',
      megafon: '<path d="M18 8a3 3 0 0 1 0 6"/><path d="M10 8v11a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-5"/><path d="M12 8h0l4.5-3.5A1 1 0 0 1 18 5.3v12.4a1 1 0 0 1-1.5.8L12 15H4a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h8"/>',
      gedung: '<path d="M3 21h18"/><path d="M3 7v1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7H3l2-4h14l2 4"/><path d="M5 21V10.85M19 21V10.85M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/>',
      target: '<circle cx="12" cy="12" r="1"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="9"/>',
      segera: '<path d="M12 5v14M5 12h14"/>',
    };

    var LABEL_STATUS = { aktif: 'Aktif', eksternal: 'Situs Eksternal', segera: 'Segera Hadir' };

    function esc(teks) {
      return String(teks == null ? '' : teks).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    function svg(kunci, ukuran) {
      return '<svg width="' + ukuran + '" height="' + ukuran + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (IKON[kunci] || IKON.dokumen) + '</svg>';
    }

    var daftar = document.getElementById('daftar');
    daftar.innerHTML = APLIKASI.map(function (app, i) {
      var bisaDibuka = app.status !== 'segera' && app.url;
      var tabBaru = app.status === 'eksternal' ? ' target="_blank" rel="noopener"' : '';
      var cari = [app.nama, app.kategori, app.singkatan, app.deskripsi].join(' ').toLowerCase();
      var panah = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

      return '<div class="kartu' + (bisaDibuka ? '' : ' nonaktif') + '"'
        + ' data-cari="' + esc(cari) + '" style="animation-delay:' + Math.min(i * 70, 420) + 'ms'
        + (app.warna ? ';--aksen-1:' + esc(app.warna[0]) + ';--aksen-2:' + esc(app.warna[1]) : '') + '">'
        + '<div class="kartu-atas">'
        + '<span class="ikon">' + svg(app.ikon, 40) + '</span>'
        + (app.status === 'segera'
          ? '<span class="kategori">' + esc(LABEL_STATUS.segera) + '</span>'
          : (app.kategori ? '<span class="kategori">' + esc(app.kategori) + '</span>' : ''))
        + '</div>'
        + '<h3>' + esc(app.nama) + '</h3>'
        + '<p class="keterangan">' + esc(app.singkatan || app.deskripsi || '') + '</p>'
        + (bisaDibuka
          ? '<a class="tombol" href="' + esc(app.url) + '"' + tabBaru + '>Buka Aplikasi ' + panah + '</a>'
          : '<span class="tombol nonaktif">Belum Tersedia</span>')
        + '</div>';
    }).join('');

    // Pencarian langsung saat mengetik.
    var inputCari = document.getElementById('cari');
    var kartu = Array.prototype.slice.call(daftar.children);
    var teksJumlah = document.getElementById('jumlah');
    function saring() {
      var kata = inputCari.value.trim().toLowerCase();
      var tampil = 0;
      kartu.forEach(function (el) {
        var cocok = !kata || el.getAttribute('data-cari').indexOf(kata) !== -1;
        el.style.display = cocok ? '' : 'none';
        if (cocok) tampil++;
      });
      document.getElementById('kosong').style.display = tampil ? 'none' : 'block';
      teksJumlah.textContent = kata ? tampil + ' dari ' + kartu.length + ' aplikasi' : kartu.length + ' aplikasi';
    }
    inputCari.addEventListener('input', saring);
    saring();

    // Tekan "/" untuk langsung ke kotak pencarian.
    document.addEventListener('keydown', function (e) {
      if (e.key === '/' && document.activeElement !== inputCari) {
        e.preventDefault();
        inputCari.focus();
      }
    });

    // Salam sesuai waktu, jam, dan tahun di footer.
    var sekarang = new Date();
    var jamKe = sekarang.getHours();
    document.getElementById('salam').textContent = jamKe < 11 ? 'Selamat pagi' : jamKe < 15 ? 'Selamat siang' : jamKe < 18 ? 'Selamat sore' : 'Selamat malam';
    document.getElementById('tahun').textContent = sekarang.getFullYear();

    var elJam = document.getElementById('jam');
    function perbaruiJam() {
      elJam.textContent = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
        + ' · ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }
    perbaruiJam();
    setInterval(perbaruiJam, 30000);
  </script>
</body>
</html>
