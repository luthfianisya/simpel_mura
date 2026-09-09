@php
    // Tanpa foto profil — pakai inisial nama di lingkaran avatar (sama seperti di
    // halaman Profil), bukan gambar generik "no profile".
    $inisialUser = strtoupper(mb_substr(auth()->user()->name, 0, 1));
@endphp
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="ti ti-menu-2 ti-sm"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <ul class="navbar-nav flex-row align-items-center ms-auto">
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <span class="avatar-initial rounded-circle bg-label-primary">{{ $inisialUser }}</span>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
              <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar avatar-online">
                    <span class="avatar-initial rounded-circle bg-label-primary">{{ $inisialUser }}</span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <span class="fw-semibold d-block">{{ auth()->user()->name }}</span>
                  <small class="text-muted">{{ ucfirst(auth()->user()->role) }}</small>
                </div>
              </div>
            </a>
          </li>
          <li><div class="dropdown-divider"></div></li>
          <li>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
              <i class="ti ti-settings me-2 ti-sm"></i>
              <span class="align-middle">Pengaturan Akun</span>
            </a>
          </li>
          <li><div class="dropdown-divider"></div></li>
          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <a class="dropdown-item" href="javascript:void(0)" onclick="event.preventDefault(); this.closest('form').submit();">
                <i class="ti ti-logout me-2 ti-sm"></i>
                <span class="align-middle">Keluar</span>
              </a>
            </form>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>