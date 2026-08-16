<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>@yield('title', '文档中心 - CMSPRO')</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="images/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('Home/css/home.css') }}">
    @stack('page_styles')
</head>
<body>
<header class="home-header" id="homeHeader">
    <div class="header-inner">
        <a href="{{ url('/') }}" class="header-logo">
            <img src="{{ asset('Home/logo-nav.png') }}" alt="CMSPRO">
        </a>
        <nav class="header-nav" id="headerNav">
            @foreach($homeMenus as $menu)
                @if($menu->parent_id == 0)
                    <a href="{{ menu_path($menu->path) }}" class="nav-link{{ is_active_menu($menu->path) ? ' active' : '' }}">{{ $menu->name }}</a>
                @endif
            @endforeach
        </nav>
        <div class="header-actions">
            <a href="{{ url('/user/login') }}" class="btn btn-outline btn-sm">用户中心</a>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="菜单">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<main>
    @yield('content')
</main>

<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<script>
(function() {
    var navEl = document.getElementById('headerNav');
    var mobileBtn = document.getElementById('mobileMenuBtn');
    var overlay = document.getElementById('mobileMenuOverlay');
    var header = document.getElementById('homeHeader');

    function toggleMobileMenu() {
        mobileBtn.classList.toggle('active');
        navEl.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    }

    mobileBtn.addEventListener('click', toggleMobileMenu);
    overlay.addEventListener('click', toggleMobileMenu);

    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
})();
</script>
@php $siteAnalytics = \App\Models\ConfigItem::where('code', 'site_analytics')->value('value'); @endphp
@if($siteAnalytics)
{!! $siteAnalytics !!}
@endif
@yield('script')
@stack('page_scripts')
</body>
</html>
