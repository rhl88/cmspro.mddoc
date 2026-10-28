<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>文档中心</title>
    <link rel="stylesheet" href="{{ asset('apps/cmspro.mddoc/css/doc.css') }}">
    <link rel="stylesheet" href="{{ asset('apps/cmspro.mddoc/lib/highlight.js/styles/github-dark.min.css') }}">
    <script>
        // 首屏防闪烁：在渲染前确定主题
        (function () {
            var theme = localStorage.getItem('mddoc_theme');
            if (!theme) {
                theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
<div class="container">
    <!-- 顶栏 -->
    <header class="topbar" id="topbar">
        <div class="topbar-inner">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebar-toggle" aria-label="打开导航">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/></svg>
                </button>
                <a href="{{ $brand['link'] }}" class="brand">
                    @if($brand['icon_url'])
                    <span class="brand-logo"><img src="{{ $brand['icon_url'] }}" alt=""></span>
                    @elseif($brand['icon_svg'])
                    <span class="brand-logo">{!! $brand['icon_svg'] !!}</span>
                    @endif
                    @if($brand['name'])
                    <span class="brand-name">{{ $brand['name'] }}</span>
                    @endif
                </a>
            </div>
            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="搜索文档..." autocomplete="off">
                <span class="search-kbd">/</span>
                <button id="search-clear" class="search-clear" style="display: none;" aria-label="清空搜索">
                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
                <div id="search-results" class="search-results"></div>
            </div>
            <div class="topbar-actions">
                <nav class="header-nav">
                    @foreach($brand['navs'] as $nav)
                    <a href="{{ $nav['url'] }}">{{ $nav['title'] }}</a>
                    @endforeach
                </nav>
                <button class="theme-toggle" id="theme-toggle" aria-label="切换主题" title="切换亮/暗主题">
                    <svg class="icon-sun" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0-5l2.39 3.42h-4.78L12 2zm0 20l-2.39-3.42h4.78L12 22zM2 12l3.42-2.39v4.78L2 12zm20 0l-3.42 2.39v-4.78L22 12zM4.93 4.93l3.9.9-2.37 2.38-1.53-3.28zm14.14 14.14l-3.9-.9 2.37-2.38 1.53 3.28zM19.07 4.93l-.9 3.9-2.38-2.37 3.28-1.53zM4.93 19.07l.9-3.9 2.38 2.37-3.28 1.53z"/></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12.34 2.02C6.59 1.82 2 6.42 2 12c0 5.52 4.48 10 10 10 5.58 0 10.18-4.59 9.98-10.34a8.03 8.03 0 0 1-9.64-9.64zM12 20a8 8 0 0 1-8-8c0-1.44.38-2.79 1.05-3.96A10.05 10.05 0 0 0 12 20z"/></svg>
                </button>
            </div>
        </div>
    </header>

    <!-- 主体三栏 -->
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <nav class="nav">
                <div class="nav-title">文档导航</div>
                <ul id="nav-list"></ul>
            </nav>
        </aside>
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <div class="content-wrapper">
            <main class="content">
                <div id="loading" class="loading">
                    <div class="spinner"></div>
                    <div class="loading-text">加载中...</div>
                </div>
                <nav id="breadcrumb" class="breadcrumb"></nav>
                <div id="content" class="markdown-content"></div>
                <nav id="prev-next" class="prev-next"></nav>
            </main>
            <aside class="toc">
                <div class="toc-header">
                    <span>本页目录</span>
                </div>
                <nav id="toc-nav"></nav>
            </aside>
        </div>
    </div>

    <button id="back-to-top" class="back-to-top" title="返回顶部">
        <svg viewBox="0 0 24 24" width="24" height="24">
            <path fill="currentColor" d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
        </svg>
    </button>
</div>

<script src="{{ asset('apps/cmspro.mddoc/js/marked.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/highlight.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/languages/xml.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/languages/javascript.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/languages/css.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/languages/php.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/languages/sql.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/languages/bash.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/lib/highlight.js/languages/json.min.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/js/doc.js') }}"></script>
</body>
</html>
