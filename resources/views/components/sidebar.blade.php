<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="{{ route('dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo demo">
        <img src="{{ asset('assets/img/branding/logo-bps.png') }}" alt="Logo BPS" height="22">
      </span>
      <span class="app-brand-text demo menu-text fw-bold">SIMPEL</span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach (config('menu') as $item)
      @if (in_array(auth()->user()->role, $item['roles']))
        <li class="menu-item {{ request()->routeIs($item['route']) ? 'active' : '' }}">
          <a href="{{ route($item['route']) }}" class="menu-link">
            <i class="menu-icon tf-icons {{ $item['icon'] }}"></i>
            <div>{{ $item['label'] }}</div>
          </a>
        </li>
      @endif
    @endforeach
  </ul>
</aside>