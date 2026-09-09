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
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}" />

  <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />

  <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('assets/js/config.js') }}"></script>
</head>
<body>
  <div class="authentication-wrapper authentication-basic px-4">
    <div class="authentication-inner py-6 mx-auto">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4 d-flex align-items-center gap-2">
            <span class="app-brand-logo demo">
              <img src="{{ asset('assets/img/branding/logo-bps.png') }}" alt="Logo BPS" height="22">
            </span>
            <span class="app-brand-text demo fw-bold" style="font-size: 1.35rem;">SIMPEL</span>
          </div>

          <h4 class="mb-1 text-center">Selamat Datang! 👋</h4>
          <p class="mb-4 text-muted text-center">Silakan masuk untuk mengelola perjalanan dinas Anda.</p>

          @if (session('status'))
            <div class="alert alert-success">
              {{ session('status') }}
            </div>
          @endif

          <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" id="username" name="username" class="form-control @error('username') is-invalid @enderror"
                value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="mis. fia">
              @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                <input type="password" id="password" name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    required autocomplete="current-password" placeholder="&#183;&#183;&#183;&#183;&#183;&#183;&#183;&#183;">
                <span class="input-group-text cursor-pointer" id="toggle-password"><i class="ti ti-eye-off"></i></span>
              </div>
              @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3 d-flex align-items-center justify-content-between">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                <label class="form-check-label" for="remember_me">Ingat saya</label>
              </div>
              @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small">Lupa password?</a>
              @endif
            </div>

            <button type="submit" class="btn btn-primary d-grid w-100">Masuk</button>
          </form>
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
