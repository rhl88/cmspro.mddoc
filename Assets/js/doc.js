/**
 * MD文档中心 - 前端逻辑
 * 基于 ApiH5 script.js 适配，将 Nginx 目录扫描替换为 API 调用
 */

// 配置
const CONFIG = {
    apiBase: '/api/mddoc',
    cacheKey: 'mddoc_structure_cache_v2',
    searchCacheKey: 'mddoc_search_cache_v2',
    legacyCacheKeys: ['mddoc_structure_cache', 'mddoc_search_cache'],
    themeKey: 'mddoc_theme',
    cacheExpiry: 5 * 60 * 1000,
};

// 状态管理
const state = {
    structure: null,
    currentFile: null,
    activeCategory: null,
    searchIndex: [],
    searchIndexReady: false,
};

// DOM 元素
const elements = {
    navList: null,
    content: null,
    loading: null,
    tocNav: null,
    backToTop: null,
    searchInput: null,
    searchClear: null,
    searchResults: null,
    breadcrumb: null,
    prevNext: null,
    sidebar: null,
    sidebarOverlay: null,
    themeToggle: null,
};

// 主题初始化与切换
function initTheme() {
    const saved = localStorage.getItem(CONFIG.themeKey);
    const theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);

    if (elements.themeToggle) {
        elements.themeToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem(CONFIG.themeKey, next);
        });
    }
}

// 清理旧版本缓存键
function cleanupLegacyCache() {
    CONFIG.legacyCacheKeys.forEach(key => {
        try { localStorage.removeItem(key); } catch {}
    });
}

// 移动端抽屉开关
function toggleSidebar(open) {
    if (!elements.sidebar || !elements.sidebarOverlay) return;
    elements.sidebar.classList.toggle('open', open);
    elements.sidebarOverlay.classList.toggle('active', open);
}

function closeSidebar() {
    toggleSidebar(false);
}

// 初始化
async function init() {
    elements.navList = document.getElementById('nav-list');
    elements.content = document.getElementById('content');
    elements.loading = document.getElementById('loading');
    elements.tocNav = document.getElementById('toc-nav');
    elements.backToTop = document.getElementById('back-to-top');
    elements.searchInput = document.getElementById('search-input');
    elements.searchClear = document.getElementById('search-clear');
    elements.searchResults = document.getElementById('search-results');
    elements.breadcrumb = document.getElementById('breadcrumb');
    elements.prevNext = document.getElementById('prev-next');
    elements.sidebar = document.getElementById('sidebar');
    elements.sidebarOverlay = document.getElementById('sidebar-overlay');
    elements.themeToggle = document.getElementById('theme-toggle');

    window.addEventListener('hashchange', handleHashChange);
    window.addEventListener('scroll', handleScroll);

    if (elements.backToTop) {
        elements.backToTop.addEventListener('click', scrollToTop);
    }

    // 主题切换
    initTheme();

    // 移动端抽屉
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => toggleSidebar(!elements.sidebar.classList.contains('open')));
    }
    if (elements.sidebarOverlay) {
        elements.sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // 快捷键：/ 聚焦搜索
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && elements.searchInput
            && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            elements.searchInput.focus();
        }
    });

    // 清理旧版本缓存
    cleanupLegacyCache();

    initSearch();

    try {
        showLoading();
        await loadStructure();
        renderNavigation();
        handleHashChange();
        // 搜索索引后台构建，不阻塞导航渲染
        buildSearchIndex().catch((error) => {
            console.warn('搜索索引构建失败:', error);
        });
    } catch (error) {
        console.error('初始化失败:', error);
        showError('初始化失败: ' + error.message);
    }
}

// 加载文档结构（通过 API）
async function loadStructure() {
    const cached = getCachedData(CONFIG.cacheKey);
    if (cached) {
        state.structure = cached;
        return;
    }

    const response = await fetch(CONFIG.apiBase + '/structure');
    const result = await response.json();

    if (result.code !== 0) {
        throw new Error(result.message || '获取文档结构失败');
    }

    state.structure = result.data;
    cacheData(CONFIG.cacheKey, state.structure);
}

// 加载文件内容（通过 API）
async function fetchFileContent(path) {
    const response = await fetch(CONFIG.apiBase + '/file?path=' + encodeURIComponent(path));
    const result = await response.json();

    if (result.code !== 0) {
        throw new Error(result.message || '获取文件内容失败');
    }

    return result.data.content;
}

// 缓存相关
function getCachedData(key) {
    try {
        const cached = localStorage.getItem(key);
        if (!cached) return null;
        const data = JSON.parse(cached);
        if (Date.now() - data.timestamp > CONFIG.cacheExpiry) {
            localStorage.removeItem(key);
            return null;
        }
        return data.data;
    } catch {
        return null;
    }
}

function cacheData(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify({
            data,
            timestamp: Date.now()
        }));
    } catch {}
}

// 渲染导航
function renderNavigation() {
    if (!state.structure || !state.structure.categories) {
        elements.navList.innerHTML = '<li class="empty-state"><p>暂无文档</p></li>';
        return;
    }

    elements.navList.innerHTML = '';

    state.structure.categories.forEach(category => {
        const categoryEl = createCategoryElement(category);
        elements.navList.appendChild(categoryEl);
    });
}

// 创建分类元素
function createCategoryElement(category) {
    const li = document.createElement('li');
    li.className = 'nav-category';
    li.dataset.categoryId = category.id;

    const header = document.createElement('div');
    header.className = 'category-header';
    header.innerHTML = `
        <span class="category-name">${escapeHtml(category.name)}</span>
        <span class="category-toggle">
            <svg viewBox="0 0 24 24" width="16" height="16">
                <path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
            </svg>
        </span>
    `;

    const filesContainer = document.createElement('ul');
    filesContainer.className = 'category-files';

    if (category.files && category.files.length > 0) {
        category.files.forEach(file => {
            const fileLi = createFileElement(file, category);
            filesContainer.appendChild(fileLi);
        });
    }

    header.addEventListener('click', () => {
        toggleCategory(category.id, li, filesContainer);
    });

    li.appendChild(header);
    li.appendChild(filesContainer);

    if (category.collapsed !== false) {
        li.classList.add('collapsed');
        filesContainer.style.display = 'none';
    }

    return li;
}

// 创建文件元素
function createFileElement(file, category) {
    const li = document.createElement('li');
    li.className = 'nav-item';

    const a = document.createElement('a');
    a.href = '#/doc/' + encodeURIComponent(file.file);
    a.dataset.file = file.file;
    a.dataset.category = category.id;
    a.textContent = file.name;

    a.addEventListener('click', (e) => {
        e.preventDefault();
        navigateToFile(file.file, category.id);
    });

    li.appendChild(a);
    return li;
}

// 切换分类
function toggleCategory(categoryId, categoryEl, filesContainer) {
    const isCollapsed = categoryEl.classList.contains('collapsed');

    if (isCollapsed) {
        document.querySelectorAll('.nav-category').forEach(el => {
            if (el.dataset.categoryId !== categoryId) {
                el.classList.add('collapsed');
                el.querySelector('.category-files').style.display = 'none';
            }
        });

        categoryEl.classList.remove('collapsed');
        filesContainer.style.display = 'block';
    } else {
        categoryEl.classList.add('collapsed');
        filesContainer.style.display = 'none';
    }
}

// 导航到文件
function navigateToFile(file, categoryId) {
    window.location.hash = '/doc/' + encodeURIComponent(file);

    if (categoryId) {
        const categoryEl = document.querySelector(`[data-category-id="${categoryId}"]`);
        if (categoryEl) {
            // 手风琴效果：收起其他分类
            document.querySelectorAll('.nav-category').forEach(el => {
                if (el.dataset.categoryId !== categoryId) {
                    el.classList.add('collapsed');
                    const files = el.querySelector('.category-files');
                    if (files) files.style.display = 'none';
                }
            });
            // 展开目标分类
            categoryEl.classList.remove('collapsed');
            categoryEl.querySelector('.category-files').style.display = 'block';
        }
    }

    loadDocument(file);
    updateActiveState(file, categoryId);
    closeSidebar();
}

// 更新活动状态
function updateActiveState(file, categoryId) {
    document.querySelectorAll('#nav-list a').forEach(a => {
        a.classList.remove('active');
    });

    const activeLink = document.querySelector(`#nav-list a[data-file="${CSS.escape(file)}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }

    state.currentFile = file;
    state.activeCategory = categoryId;
}

// 处理 hash 变化
function handleHashChange() {
    const hash = window.location.hash;

    if (hash && hash.startsWith('#/doc/')) {
        const file = decodeURIComponent(hash.substring(6));
        const category = findCategoryForFile(file);
        if (category) {
            navigateToFile(file, category.id);
        } else {
            loadDocument(file);
            updateActiveState(file, null);
        }
    } else {
        const firstCategory = state.structure?.categories?.[0];
        const firstFile = firstCategory?.files?.[0]?.file;
        if (firstFile) {
            navigateToFile(firstFile, firstCategory.id);
        } else {
            showEmptyState();
        }
    }
}

// 查找文件所属分类
function findCategoryForFile(file) {
    if (!state.structure?.categories) return null;
    for (const category of state.structure.categories) {
        if (category.files?.some(f => f.file === file)) {
            return category;
        }
    }
    return null;
}

// 加载文档
async function loadDocument(file) {
    showLoading();

    try {
        const content = await fetchFileContent(file);
        renderDocument(content, file);
    } catch (error) {
        console.error('加载文档失败:', error);
        showError(`加载文档失败: ${error.message}`);
    }
}

// 渲染文档
function renderDocument(content, file) {
    if (!elements.content) {
        hideLoading();
        return;
    }

    try {
        const html = marked.parse(content);
        elements.content.innerHTML = html;

        processCodeBlocks();
        processBlockquotes();
        generateTOC();
        processDocumentLinks(file);
        appendDownloadBtn(file);
        renderBreadcrumb(file);
        renderPrevNext(file);

        const doc = findFileByName(file);
        document.title = (doc ? doc.name + ' - ' : '') + '文档中心';

        hideLoading();
        window.scrollTo(0, 0);
    } catch (e) {
        console.error('渲染文档失败:', e);
        showError(`渲染文档失败: ${e.message}`);
    }
}

// 查找文件信息
function findFileByName(file) {
    if (!state.structure?.categories) return null;
    for (const category of state.structure.categories) {
        const found = category.files?.find(f => f.file === file);
        if (found) return found;
    }
    return null;
}

// 按导航顺序扁平化全部文件（用于计算上/下一篇）
function flattenFiles() {
    if (!state.structure?.categories) return [];
    const flat = [];
    for (const category of state.structure.categories) {
        if (category.files) {
            for (const file of category.files) {
                flat.push({
                    file: file.file,
                    name: file.name,
                    categoryName: category.name,
                });
            }
        }
    }
    return flat;
}

// 渲染面包屑：分类名 / 文档名
function renderBreadcrumb(file) {
    if (!elements.breadcrumb) return;

    const category = findCategoryForFile(file);
    const doc = findFileByName(file);
    const fileName = doc ? doc.name : decodeURIComponent(file.split('/').pop().replace(/\.md$/, ''));

    if (category) {
        elements.breadcrumb.innerHTML = `
            <span class="crumb-link">${escapeHtml(category.name)}</span>
            <span class="crumb-sep">/</span>
            <span class="crumb-current">${escapeHtml(fileName)}</span>
        `;
    } else {
        elements.breadcrumb.innerHTML = `
            <span class="crumb-current">${escapeHtml(fileName)}</span>
        `;
    }
}

// 渲染上一篇 / 下一篇
function renderPrevNext(file) {
    if (!elements.prevNext) return;

    const flat = flattenFiles();
    const index = flat.findIndex(item => item.file === file);
    const prev = index > 0 ? flat[index - 1] : null;
    const next = index >= 0 && index < flat.length - 1 ? flat[index + 1] : null;

    if (!prev && !next) {
        elements.prevNext.innerHTML = '';
        return;
    }

    const prevHtml = prev ? `
        <a class="pn-link pn-prev" href="#/doc/${encodeURIComponent(prev.file)}">
            <span class="pn-label">
                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                上一篇
            </span>
            <span class="pn-title">${escapeHtml(prev.name)}</span>
        </a>
    ` : '<span class="pn-placeholder"></span>';

    const nextHtml = next ? `
        <a class="pn-link pn-next" href="#/doc/${encodeURIComponent(next.file)}">
            <span class="pn-label">
                下一篇
                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
            </span>
            <span class="pn-title">${escapeHtml(next.name)}</span>
        </a>
    ` : '<span class="pn-placeholder"></span>';

    elements.prevNext.innerHTML = prevHtml + nextHtml;
}

// 处理代码块
function processCodeBlocks() {
    if (!elements.content) return;

    const preElements = elements.content.querySelectorAll('pre');
    preElements.forEach(preEl => {
        if (!preEl) return;
        if (preEl.dataset.processed) return;

        const codeEl = preEl.querySelector('code');
        if (!codeEl) return;

        // 提取语言信息
        let language = '';
        const classList = codeEl.className;
        if (classList) {
            const match = classList.match(/language-(\w+)/);
            if (match && match[1]) {
                language = match[1].toLowerCase();
            }
        }

        // 代码高亮
        try {
            if (language === 'vue') {
                codeEl.className = 'language-xml';
            }
            hljs.highlightElement(codeEl);
        } catch (e) {}

        // 构建头部区域
        const headerEl = document.createElement('div');
        headerEl.className = 'code-header';

        const headerLeft = document.createElement('div');
        headerLeft.className = 'code-header-left';

        if (language) {
            const langTag = document.createElement('span');
            langTag.className = 'code-language';
            langTag.textContent = language;
            headerLeft.appendChild(langTag);
        }
        headerEl.appendChild(headerLeft);

        const headerRight = document.createElement('div');
        headerRight.className = 'code-header-right';

        // 折叠按钮
        const collapseBtn = document.createElement('button');
        collapseBtn.className = 'collapse-btn';
        collapseBtn.textContent = '展开';
        collapseBtn.addEventListener('click', () => {
            preEl.classList.toggle('collapsed');
            collapseBtn.textContent = preEl.classList.contains('collapsed') ? '展开' : '收起';
        });
        headerRight.appendChild(collapseBtn);

        // 复制按钮
        const copyBtn = document.createElement('button');
        copyBtn.className = 'copy-btn';
        copyBtn.textContent = '复制';
        copyBtn.addEventListener('click', () => copyCode(codeEl, copyBtn));
        headerRight.appendChild(copyBtn);

        headerEl.appendChild(headerRight);

        // 构建代码内容区域
        const codeBody = document.createElement('div');
        codeBody.className = 'code-body';
        codeBody.appendChild(codeEl);

        // 清空 pre 并重新组装
        preEl.innerHTML = '';
        preEl.classList.add('collapsed');
        preEl.appendChild(headerEl);
        preEl.appendChild(codeBody);
        preEl.dataset.processed = '1';
    });
}

// 处理引用块
function processBlockquotes() {
    if (!elements.content) return;

    const blockquotes = elements.content.querySelectorAll('blockquote');
    blockquotes.forEach(bq => {
        // 处理空引用块
        if (!bq.innerHTML || bq.innerHTML.trim() === '') {
            bq.style.display = 'none';
            return;
        }

        // 为嵌套引用添加层级标识
        let depth = 0;
        let parent = bq.parentElement;
        while (parent && parent !== elements.content) {
            if (parent.tagName === 'BLOCKQUOTE') depth++;
            parent = parent.parentElement;
        }
        if (depth > 0) {
            bq.setAttribute('data-depth', depth);
        }
    });
}

// 复制代码
async function copyCode(codeEl, btn) {
    if (!codeEl || !btn) return;

    try {
        const codeText = codeEl.textContent || codeEl.innerText;
        if (!codeText) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(codeText.trim());
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = codeText.trim();
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        btn.textContent = '已复制';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = '复制';
            btn.classList.remove('copied');
        }, 2000);
    } catch (e) {
        btn.textContent = '失败';
        setTimeout(() => { btn.textContent = '复制'; }, 2000);
    }
}

// 生成目录
function generateTOC() {
    if (!elements.content || !elements.tocNav) return;

    const headings = elements.content.querySelectorAll('h1, h2, h3, h4, h5, h6');
    elements.tocNav.innerHTML = '';

    if (headings.length === 0) {
        elements.tocNav.innerHTML = '<p class="toc-empty">无目录</p>';
        return;
    }

    const ul = document.createElement('ul');
    ul.className = 'toc-list';

    headings.forEach((heading, index) => {
        const id = `heading-${index}`;
        heading.id = id;

        const li = document.createElement('li');
        li.className = `toc-item toc-${heading.tagName.toLowerCase()}`;

        const a = document.createElement('a');
        a.href = `#${id}`;
        a.textContent = heading.textContent;
        a.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById(id);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });

        li.appendChild(a);
        ul.appendChild(li);
    });

    elements.tocNav.appendChild(ul);
}

// 在文档标题右侧附加下载按钮
function appendDownloadBtn(file) {
    if (!elements.content) return;

    const h1 = elements.content.querySelector('h1');
    if (!h1) return;

    // 避免重复添加
    if (h1.querySelector('.download-btn')) return;

    const btn = document.createElement('a');
    btn.className = 'download-btn';
    btn.href = CONFIG.apiBase + '/download?path=' + encodeURIComponent(file);
    btn.download = '';
    btn.innerHTML = `
        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
        </svg>
        <span>下载文档</span>
    `;
    h1.appendChild(btn);
}

// 处理文档内链接
function processDocumentLinks(currentFile) {
    const currentDir = currentFile.substring(0, currentFile.lastIndexOf('/') + 1);

    elements.content.querySelectorAll('a').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('http') || href.startsWith('#') || href.startsWith('//')) return;

        if (href.endsWith('.md')) {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                let targetFile;
                if (href.startsWith('./')) {
                    targetFile = currentDir + href.substring(2);
                } else if (href.startsWith('../')) {
                    targetFile = resolvePath(currentDir + href);
                } else {
                    targetFile = currentDir + href;
                }
                const category = findCategoryForFile(targetFile);
                if (category) {
                    navigateToFile(targetFile, category.id);
                } else {
                    window.location.hash = '/doc/' + encodeURIComponent(targetFile);
                }
            });
        }
    });
}

// 解析相对路径
function resolvePath(path) {
    const parts = path.split('/');
    const resolved = [];
    for (const part of parts) {
        if (part === '..') resolved.pop();
        else if (part !== '.' && part !== '') resolved.push(part);
    }
    return resolved.join('/');
}

// 滚动处理
function handleScroll() {
    if (elements.backToTop) {
        if (window.scrollY > 300) {
            elements.backToTop.classList.add('visible');
        } else {
            elements.backToTop.classList.remove('visible');
        }
    }
    updateTOCHighlight();
}

// 更新目录高亮
function updateTOCHighlight() {
    const headings = elements.content?.querySelectorAll('h1, h2, h3, h4, h5, h6');
    if (!headings) return;
    let current = null;
    headings.forEach(heading => {
        if (heading.getBoundingClientRect().top <= 100) current = heading;
    });
    elements.tocNav?.querySelectorAll('a').forEach(a => {
        a.classList.remove('active');
        if (current && a.getAttribute('href') === `#${current.id}`) a.classList.add('active');
    });
}

// 返回顶部
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// 显示/隐藏加载状态
function showLoading() {
    if (elements.loading) elements.loading.style.display = 'flex';
    if (elements.content) elements.content.style.display = 'none';
}

function hideLoading() {
    if (elements.loading) elements.loading.style.display = 'none';
    if (elements.content) elements.content.style.display = 'block';
}

// 显示空状态
function showEmptyState() {
    if (elements.loading) elements.loading.style.display = 'none';
    if (elements.content) {
        elements.content.style.display = 'block';
        elements.content.innerHTML = `
            <div class="empty-state">
                <h3>暂无文档</h3>
                <p>请在后台管理中添加 Markdown 文档</p>
            </div>
        `;
    }
}

// 显示错误
function showError(message) {
    if (elements.loading) elements.loading.style.display = 'none';
    if (elements.content) {
        elements.content.style.display = 'block';
        elements.content.innerHTML = `
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <div class="error-message">${message}</div>
                <button class="retry-btn" onclick="location.reload()">重新加载</button>
            </div>
        `;
    }
}

// HTML 转义
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ========== 搜索功能 ==========

function initSearch() {
    if (!elements.searchInput || !elements.searchClear || !elements.searchResults) return;

    let searchTimeout;

    elements.searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();

        if (query) {
            elements.searchClear.style.display = 'flex';
        } else {
            elements.searchClear.style.display = 'none';
            hideSearchResults();
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    elements.searchClear.addEventListener('click', () => {
        elements.searchInput.value = '';
        elements.searchClear.style.display = 'none';
        hideSearchResults();
        elements.searchInput.focus();
    });

    document.addEventListener('click', (e) => {
        const container = document.querySelector('.search-container');
        if (container && !container.contains(e.target)) {
            hideSearchResults();
        }
    });
}

// 构建搜索索引
async function buildSearchIndex() {
    const cached = getCachedData(CONFIG.searchCacheKey);
    if (cached) {
        state.searchIndex = cached;
        state.searchIndexReady = true;
        return;
    }

    if (!state.structure?.categories) return;

    const allFiles = [];
    for (const category of state.structure.categories) {
        if (category.files) {
            for (const file of category.files) {
                allFiles.push({
                    ...file,
                    categoryName: category.name,
                    categoryId: category.id
                });
            }
        }
    }

    // 并行拉取全部文件内容，避免串行请求拖慢索引构建
    const entries = await Promise.all(allFiles.map(async (file) => {
        try {
            const content = await fetchFileContent(file.file);
            return {
                file: file.file,
                categoryName: file.categoryName,
                categoryId: file.categoryId,
                title: file.title || file.name,
                content: content,
                name: file.name
            };
        } catch (e) {
            console.warn('无法索引文件:', file.file, e);
            return null;
        }
    }));

    state.searchIndex = entries.filter(Boolean);
    state.searchIndexReady = true;
    cacheData(CONFIG.searchCacheKey, state.searchIndex);
}

// 执行搜索
function performSearch(query) {
    if (!query) {
        hideSearchResults();
        return;
    }

    // 索引后台构建中，给出提示
    if (!state.searchIndexReady) {
        if (elements.searchResults) {
            elements.searchResults.innerHTML = '<div class="search-no-results">搜索索引构建中，请稍候…</div>';
            showSearchResults();
        }
        return;
    }

    if (state.searchIndex.length === 0) {
        hideSearchResults();
        return;
    }

    const lowerQuery = query.toLowerCase();
    const results = [];

    for (const doc of state.searchIndex) {
        const titleMatch = doc.title.toLowerCase().includes(lowerQuery);
        const contentMatch = doc.content.toLowerCase().includes(lowerQuery);
        const nameMatch = doc.name.toLowerCase().includes(lowerQuery);

        if (titleMatch || contentMatch || nameMatch) {
            let score = 0;
            if (titleMatch) score += 10;
            if (nameMatch) score += 5;
            if (contentMatch) score += 3;

            results.push({
                ...doc,
                score,
                preview: getPreview(doc.content, query)
            });
        }
    }

    results.sort((a, b) => b.score - a.score);
    renderSearchResults(results.slice(0, 15), query);
}

// 获取预览文本
function getPreview(content, query) {
    const lowerContent = content.toLowerCase();
    const lowerQuery = query.toLowerCase();
    const index = lowerContent.indexOf(lowerQuery);

    if (index === -1) {
        return content.substring(0, 150);
    }

    const start = Math.max(0, index - 40);
    const end = Math.min(content.length, index + query.length + 80);
    let preview = content.substring(start, end);

    if (start > 0) preview = '...' + preview;
    if (end < content.length) preview = preview + '...';

    return preview;
}

// 渲染搜索结果
function renderSearchResults(results, query) {
    if (!elements.searchResults) return;

    if (results.length === 0) {
        elements.searchResults.innerHTML = '<div class="search-no-results">未找到匹配的文档</div>';
    } else {
        elements.searchResults.innerHTML = results.map(result => {
            const highlightedTitle = highlightText(result.title, query);
            const highlightedPreview = highlightText(result.preview, query);
            return `
                <div class="search-result-item" data-file="${escapeHtml(result.file)}" data-category="${escapeHtml(result.categoryId)}">
                    <div class="search-result-title">${highlightedTitle}</div>
                    <div class="search-result-category">${escapeHtml(result.categoryName)}</div>
                    <div class="search-result-preview">${highlightedPreview}</div>
                </div>
            `;
        }).join('');

        elements.searchResults.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => {
                const file = item.dataset.file;
                const categoryId = item.dataset.category;
                hideSearchResults();
                elements.searchInput.value = '';
                elements.searchClear.style.display = 'none';
                navigateToFile(file, categoryId);
            });
        });
    }

    showSearchResults();
}

// 高亮文本
function highlightText(text, query) {
    if (!query) return escapeHtml(text);
    const escapedQuery = escapeHtml(query);
    const regex = new RegExp(`(${escapedQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return escapeHtml(text).replace(regex, '<span class="search-result-highlight">$1</span>');
}

function showSearchResults() {
    if (elements.searchResults) elements.searchResults.classList.add('show');
}

function hideSearchResults() {
    if (elements.searchResults) elements.searchResults.classList.remove('show');
}

// 初始化
document.addEventListener('DOMContentLoaded', init);
