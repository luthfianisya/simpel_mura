<!DOCTYPE html>
<html lang="id" class="light-style" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets') }}/" data-template="vertical-menu-template">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>Masuk | SIMPEL - BPS Murung Raya</title>
  <meta name="description" content="Sistem Informasi Penyusunan Dokumen Pertanggungjawaban Perjalanan Dinas Elektronik" />

  <link rel="icon" type="image/png" href="{{ asset('assets/img/branding/logo-bps.png') }}" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}" />

  <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

  <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('assets/js/config.js') }}"></script>

  <style>
    body {
      min-height: 100vh;
      overflow-x: hidden;
      background: linear-gradient(72.47deg, #1B449C 22.16%, #3D5EE1 76.47%);
    }

    /* Hiasan latar */
    .login-latar { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
    .login-latar .blob { position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.07); }
    .login-latar .blob-1 { width: 520px; height: 520px; top: -220px; left: -160px; }
    .login-latar .blob-2 { width: 380px; height: 380px; bottom: -180px; left: 32%; background: rgba(255, 255, 255, 0.05); }
    .login-latar .blob-3 { width: 300px; height: 300px; top: -90px; right: -90px; background: rgba(255, 255, 255, 0.09); }
    .login-latar .cincin {
      position: absolute;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, 0.18);
    }
    .login-latar .cincin-1 { width: 120px; height: 120px; bottom: 9%; right: 6%; }
    .login-latar .cincin-2 { width: 46px; height: 46px; top: 16%; left: 44%; border-color: rgba(125, 211, 252, 0.5); }
    .login-latar .titik {
      position: absolute;
      width: 150px;
      height: 110px;
      background-image: radial-gradient(rgba(255, 255, 255, 0.35) 2px, transparent 2px);
      background-size: 18px 18px;
    }
    .login-latar .titik-1 { top: 8%; left: 6%; }
    .login-latar .titik-2 { bottom: 7%; right: 22%; }
    .login-latar .plus {
      position: absolute;
      color: rgba(255, 255, 255, 0.45);
      font-size: 1.25rem;
      line-height: 1;
      font-weight: 300;
    }

    .login-wadah {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 2.5rem 1rem;
    }

    /* Sisi kiri: sambutan */
    .login-sambutan { color: #fff; padding-right: 3rem; }
    .login-sambutan .lencana {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 0.8rem;
      border-radius: 0.375rem;
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.2);
      font-size: 0.8125rem;
      font-weight: 500;
      margin-bottom: 1.5rem;
    }
    .login-sambutan .lencana img { height: 18px; }
    .login-sambutan h1 {
      color: #fff;
      font-weight: 800;
      font-size: 3rem;
      letter-spacing: 0.02em;
      margin-bottom: 0.5rem;
    }
    .login-sambutan .kepanjangan {
      font-size: 1.15rem;
      font-weight: 500;
      color: rgba(255, 255, 255, 0.92);
      max-width: 460px;
      margin-bottom: 1rem;
    }
    .login-sambutan .keterangan { color: rgba(255, 255, 255, 0.72); max-width: 440px; margin-bottom: 2.5rem; }

    /* Kartu fitur mengambang */
    .login-fitur { position: relative; height: 210px; max-width: 470px; }
    .kartu-fitur {
      position: absolute;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.8rem 1rem;
      border-radius: 0.625rem;
      background: rgba(255, 255, 255, 0.96);
      box-shadow: 0 16px 32px -14px rgba(10, 25, 70, 0.55);
      animation: mengambang 5s ease-in-out infinite;
    }
    .kartu-fitur .ikon {
      width: 38px;
      height: 38px;
      border-radius: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }
    .kartu-fitur b { display: block; font-size: 0.875rem; color: #1e2a4a; }
    .kartu-fitur small { color: #6b7896; font-size: 0.75rem; }
    .kartu-fitur .centang { color: #16a34a; font-size: 1.1rem; margin-left: 0.25rem; }
    .kartu-fitur.k-1 { top: 0; left: 0; }
    .kartu-fitur.k-2 { top: 72px; left: 150px; animation-delay: -1.6s; }
    .kartu-fitur.k-3 { top: 146px; left: 30px; animation-delay: -3.2s; }
    .ikon.biru { background: rgba(37, 99, 235, 0.12); color: #2563EB; }
    .ikon.hijau { background: rgba(22, 163, 74, 0.12); color: #16a34a; }
    .ikon.kuning { background: rgba(245, 158, 11, 0.15); color: #d97706; }

    @keyframes mengambang { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    @media (prefers-reduced-motion: reduce) { .kartu-fitur { animation: none; } }

    /* Sisi kanan: kartu login */
    .login-kartu {
      max-width: 430px;
      margin-left: auto;
      padding: 2.5rem 2.25rem;
      background: #fff;
      border-radius: 0.75rem;
      box-shadow: 0 30px 60px -24px rgba(10, 25, 70, 0.6);
    }
    .login-kartu .brand { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.75rem; }
    .login-kartu .brand img { height: 26px; }
    .login-kartu .brand span { font-weight: 800; font-size: 1.35rem; color: #1B449C; letter-spacing: 0.02em; }
    .login-kartu h4 { font-weight: 700; color: #1e2a4a; margin-bottom: 0.25rem; }
    .login-kartu .form-label { font-weight: 600; color: #4b5a7c; font-size: 0.8125rem; text-transform: none; letter-spacing: 0; }
    .login-kartu .input-ikon { position: relative; }
    .login-kartu .input-ikon > i {
      position: absolute;
      left: 0.9rem;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 1.1rem;
      z-index: 5;
      pointer-events: none;
    }
    .login-kartu .form-control {
      background: #f3f6fd;
      border: 1px solid transparent;
      border-radius: 0.5rem;
      padding: 0.7rem 0.9rem 0.7rem 2.6rem;
    }
    .login-kartu .form-control:focus {
      background: #fff;
      border-color: #2563EB;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .login-kartu .form-control.is-invalid { border-color: #ea5455; }
    .login-kartu .lihat-password {
      position: absolute;
      right: 0.5rem;
      top: 50%;
      transform: translateY(-50%);
      border: 0;
      background: transparent;
      color: #94a3b8;
      padding: 0.25rem 0.4rem;
      z-index: 5;
    }
    .login-kartu .lihat-password:hover { color: #2563EB; }
    .login-kartu #password { padding-right: 2.75rem; }
    .login-kartu .btn-masuk {
      padding: 0.75rem;
      font-weight: 600;
      border-radius: 0.5rem;
      box-shadow: 0 12px 22px -8px rgba(37, 99, 235, 0.6);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .login-kartu .btn-masuk:hover { transform: translateY(-2px); box-shadow: 0 16px 28px -10px rgba(37, 99, 235, 0.65); }
    .login-footer {
      max-width: 430px;
      margin: 1.5rem 0 0 auto;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.8125rem;
      text-align: center;
    }

    @media (max-width: 991.98px) {
      .login-kartu { margin: 0 auto; padding: 2rem 1.5rem; }
      .login-footer { margin: 1.25rem auto 0; }
    }
  </style>
</head>
<body>
  <div class="login-latar" aria-hidden="true">
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>
    <span class="blob blob-3"></span>
    <span class="cincin cincin-1"></span>
    <span class="cincin cincin-2"></span>
    <span class="titik titik-1"></span>
    <span class="titik titik-2"></span>
    <span class="plus" style="top: 22%; right: 46%;">+</span>
    <span class="plus" style="bottom: 18%; left: 8%;">+</span>
    <span class="plus" style="top: 62%; left: 47%;">×</span>
  </div>

  <div class="login-wadah">
    <div class="container-xxl">
      <div class="row align-items-center justify-content-center">
        <div class="col-lg-6 d-none d-lg-block">
          <div class="login-sambutan">
            <div class="lencana">
              <img src="{{ asset('assets/img/branding/logo-bps.png') }}" alt="">
              BPS Kabupaten Murung Raya
            </div>
            <h1>SIMPEL</h1>
            <p class="kepanjangan">Sistem Informasi Penyusunan Dokumen Pertanggungjawaban Perjalanan Dinas Elektronik</p>
            {{-- <p class="keterangan">Susun SPD, kuitansi, dan laporan perjalanan dinas dalam satu tempat, cepat dan rapi.</p> --}}

            <div class="login-fitur" aria-hidden="true">
              <div class="kartu-fitur k-1">
                <span class="ikon biru"><i class="ti ti-file-text"></i></span>
                <div><b>Surat Perjalanan Dinas</b><small>Nomor SPD otomatis</small></div>
                <i class="ti ti-circle-check centang"></i>
              </div>
              <div class="kartu-fitur k-2">
                <span class="ikon hijau"><i class="ti ti-receipt"></i></span>
                <div><b>Kuitansi & Rincian Biaya</b><small>Terhitung otomatis</small></div>
                <i class="ti ti-circle-check centang"></i>
              </div>
              <div class="kartu-fitur k-3">
                <span class="ikon kuning"><i class="ti ti-file-export"></i></span>
                <div><b>Export PDF</b><small>Laporan siap cetak</small></div>
                <i class="ti ti-circle-check centang"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6 col-md-8">
          <div class="login-kartu">
            <div class="brand">
              <img src="{{ asset('assets/img/branding/logo-bps.png') }}" alt="Logo BPS">
              <span>SIMPEL</span>
            </div>

            <h4>Selamat Datang! 👋</h4>
            <p class="mb-4 text-muted">Silakan masuk untuk mengelola perjalanan dinas Anda.</p>

            @if (session('status'))
              <div class="alert alert-success">
                {{ session('status') }}
              </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
              @csrf

              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-ikon">
                  <i class="ti ti-user"></i>
                  <input type="text" id="username" name="username" class="form-control @error('username') is-invalid @enderror"
                    value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Masukkan username...">
                </div>
                @error('username')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-ikon">
                  <i class="ti ti-lock"></i>
                  <input type="password" id="password" name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    required autocomplete="current-password" placeholder="&#183;&#183;&#183;&#183;&#183;&#183;&#183;&#183;">
                  <button type="button" class="lihat-password" id="toggle-password" aria-label="Tampilkan password"><i class="ti ti-eye-off"></i></button>
                </div>
                @error('password')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-4 d-flex align-items-center justify-content-between">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                  <label class="form-check-label" for="remember_me">Ingat saya</label>
                </div>
                @if (Route::has('password.request'))
                  <a href="{{ route('password.request') }}" class="small">Lupa password?</a>
                @endif
              </div>

              <button type="submit" class="btn btn-primary btn-masuk w-100">
                Masuk <i class="ti ti-arrow-right ti-sm ms-1"></i>
              </button>
            </form>
          </div>
          <div class="login-footer">&copy; {{ date('Y') }} SIMPEL &middot; BPS Kabupaten Murung Raya</div>
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
  <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
  <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>

  <script>
    document.getElementById('toggle-password').addEventListener('click', function () {
      const input = document.getElementById('password');
      const icon = this.querySelector('i');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      icon.className = isHidden ? 'ti ti-eye' : 'ti ti-eye-off';
    });
  </script>
</body>
</html>
