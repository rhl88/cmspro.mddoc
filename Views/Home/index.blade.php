<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>文档中心</title>
    <link rel="stylesheet" href="{{ asset('apps/cmspro.mddoc/css/doc.css') }}">
    <link rel="stylesheet" href="{{ asset('apps/cmspro.mddoc/lib/highlight.js/styles/github-dark.min.css') }}">
</head>
<body>
<div class="container">
    <header class="header">
        <h1>文档中心</h1>
        <div class="search-container">
            <input type="text" id="search-input" class="search-input" placeholder="搜索文档内容...">
            <button id="search-clear" class="search-clear" style="display: none;">✕</button>
            <div id="search-results" class="search-results"></div>
        </div>
        <nav class="header-nav">
            <a href="/">首页</a>
            <a href="/apps">应用市场</a>
            <a href="/help">帮助中心</a>
            <a href="//bbs.cmspro.cn">论坛</a>
        </nav>
    </header>
    <div class="main">
        <aside class="sidebar">
            <nav class="nav">
                <h2>文档导航</h2>
                <ul id="nav-list"></ul>
            </nav>
        </aside>
        <div class="content-wrapper">
            <main class="content">
                <div id="loading" class="loading">
                    <div class="spinner"></div>
                    <div class="loading-text">加载中...</div>
                </div>
                <div id="content" class="markdown-content"></div>
            </main>
            <aside class="toc">
                <div class="toc-header">
                    <h3>文档目录</h3>
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
