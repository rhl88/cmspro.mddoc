<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>应用设置 - MD文档管理</title>
    <link rel="stylesheet" href="{{ asset('CmsProUi/component/pear/css/pear.css') }}">
    <link rel="stylesheet" href="{{ asset('CmsProUi/component/layui/css/layui.css') }}">
    <link rel="stylesheet" href="{{ asset('CmsProUi/font-awesome/4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/admin.css') }}">
    <style>
        body { background: #f5f6f8; }
        .settings-wrap { max-width: 760px; margin: 0 auto; }
        .form-item { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 6px; }
        .form-label em { color: #ff4d4f; font-style: normal; margin-right: 2px; }
        .form-tips { font-size: 12px; color: #999; margin-top: 5px; }
        .form-input { width: 100%; height: 36px; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 13px; box-sizing: border-box; outline: none; transition: border-color 0.2s; background: #fff; color: #333; }
        .form-input:focus { border-color: #1890ff; }
        .form-actions { display: flex; gap: 8px; margin-top: 8px; }
        /* 图标上传区 */
        .icon-box { display: flex; align-items: center; gap: 14px; }
        .icon-preview { width: 56px; height: 56px; border: 1px solid #e8e8e8; border-radius: 8px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .icon-preview img { width: 100%; height: 100%; object-fit: contain; }
        .icon-preview .default-svg { color: #4e6ef2; display: flex; }
        .icon-ops { display: flex; flex-direction: column; gap: 8px; }
        .icon-ops .layui-btn { margin: 0; }
        .icon-current-tip { font-size: 12px; color: #999; }
        /* Tab 切换 */
        .tabs { display: flex; gap: 4px; border-bottom: 2px solid #e8e8e8; margin-bottom: 24px; }
        .tab-btn { padding: 10px 18px; font-size: 14px; color: #666; background: none; border: none; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab-btn:hover { color: #1890ff; }
        .tab-btn.active { color: #1890ff; font-weight: 600; border-bottom-color: #1890ff; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        /* 菜单管理行 */
        .nav-row { display: flex; gap: 8px; align-items: center; margin-bottom: 10px; }
        .nav-row .form-input { width: 220px; }
        .nav-row .nav-url { flex: 1; }
        .nav-row .nav-remove { flex-shrink: 0; }
        .nav-rows-empty { font-size: 13px; color: #bbb; padding: 8px 0; }
    </style>
</head>
<body>
<div class="pear-container">
    <div class="layui-card">
        <div class="layui-card-header">
            <i class="fa fa-cog"></i> 应用设置
            <span style="font-size:12px;color:#999;margin-left:8px;">自定义前台文档中心顶栏的品牌展示</span>
        </div>
        <div class="layui-card-body">
            <div class="settings-wrap">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="brand" type="button">品牌设置</button>
                    <button class="tab-btn" data-tab="navs" type="button">菜单管理</button>
                </div>

                <!-- Tab 1：品牌设置（原有内容） -->
                <div class="tab-panel active" id="tab-brand">
                <div class="form-item">
                    <label class="form-label">品牌图标</label>
                    <div class="icon-box">
                        <div class="icon-preview" id="icon-preview"></div>
                        <div class="icon-ops">
                            <button class="layui-btn layui-btn-sm layui-btn-normal" id="btn-upload-icon" type="button"><i class="fa fa-upload"></i> 上传图标</button>
                            <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-reset-icon" type="button" style="display:none;"><i class="fa fa-undo"></i> 恢复默认图标</button>
                            <input type="file" id="icon-file" accept=".png,.jpg,.jpeg,.gif,.svg,.webp" style="display:none;">
                        </div>
                    </div>
                    <div class="form-tips" id="icon-tip">{{ $hasIcon ? '当前使用上传的图标：' . $values['logo'] : '当前使用默认图标。支持 PNG/JPG/GIF/SVG/WEBP，不超过 2MB。' }}</div>
                </div>

                <div class="form-item">
                    <label class="form-label">品牌名称</label>
                    <input type="text" class="form-input" id="brand-name" placeholder="留空则不显示文字" value="{{ $values['name'] }}">
                    <div class="form-tips">前台顶栏品牌显示文字，留空则不显示文字。</div>
                </div>

                <div class="form-item">
                    <label class="form-label">品牌链接</label>
                    <input type="text" class="form-input" id="brand-link" placeholder="留空使用默认 /" value="{{ $values['link'] }}">
                    <div class="form-tips">点击品牌区域的跳转地址，支持站内相对路径（如 /）或完整 URL。</div>
                </div>

                <div class="form-actions">
                    <button class="layui-btn layui-btn-normal" id="btn-save"><i class="fa fa-save"></i> 保存设置</button>
                </div>
                </div>

                <!-- Tab 2：菜单管理（前台顶栏 header-nav） -->
                <div class="tab-panel" id="tab-navs">
                    <div class="form-item">
                        <label class="form-label">前台导航菜单</label>
                        <div id="nav-rows"></div>
                        <div class="form-actions">
                            <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-add-nav" type="button"><i class="fa fa-plus"></i> 添加菜单</button>
                        </div>
                        <div class="form-tips">控制前台文档中心顶栏右侧的导航链接（最多 10 项），按列表顺序显示；清空后保存将恢复默认菜单。</div>
                    </div>
                    <div class="form-actions">
                        <button class="layui-btn layui-btn-normal" id="btn-save-navs"><i class="fa fa-save"></i> 保存菜单</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script>
    // 通过 Layui 模块系统加载 jQuery 与 layer（layui.js 不暴露全局 $）
    layui.use(['jquery', 'layer'], function () {
        var $ = layui.jquery;

    // 默认品牌图标（内置 SVG，用于无上传时的预览）
    var DEFAULT_SVG = @json($defaults['icon_svg']);
    var CSRF = '{{ csrf_token() }}';

    var $name = $('#brand-name'), $link = $('#brand-link');

    // 读取当前图标状态：hasUploaded 为 true 时预览用 img，否则渲染默认 SVG
    var hasUploaded = {{ $hasIcon ? 'true' : 'false' }};
    var uploadedUrl = hasUploaded ? '/api/mddoc/brand-icon?v=' + Date.now() : '';

    // 渲染图标到指定容器（img 或内联 SVG）
    function renderIcon($target, url, fallbackSvg) {
        if (url) {
            $target.html($('<img>', { src: url, alt: '品牌图标' }));
        } else {
            $target.html(fallbackSvg);
        }
    }

    // 同步「图标预览框 + 恢复按钮/提示」状态
    function refreshIconState() {
        var url = hasUploaded ? uploadedUrl : '';
        renderIcon($('#icon-preview'), url, '<span class="default-svg">' + DEFAULT_SVG + '</span>');
        $('#btn-reset-icon').toggle(hasUploaded);
        $('#icon-tip').text(hasUploaded ? '当前使用上传的图标' : '当前使用默认图标。支持 PNG/JPG/GIF/SVG/WEBP，不超过 2MB。');
    }

    // 触发文件选择
    $('#btn-upload-icon').on('click', function () {
        $('#icon-file').val('').click();
    });

    // 上传图标
    $('#icon-file').on('change', function () {
        var file = this.files[0];
        if (!file) return;

        var formData = new FormData();
        formData.append('icon', file);
        formData.append('_token', CSRF);

        var $btn = $('#btn-upload-icon').prop('disabled', true);
        $.ajax({
            url: '{{ url("/admin/cmspro/mddoc/settings/icon") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (res) {
            if (res.code === 0) {
                hasUploaded = true;
                uploadedUrl = res.data.icon_url;
                refreshIconState();
                layui.layer.msg(res.message, { icon: 1 });
            } else {
                layui.layer.msg(res.message || '上传失败', { icon: 2 });
            }
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '上传失败，请稍后重试';
            layui.layer.msg(msg, { icon: 2 });
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // 恢复默认图标
    $('#btn-reset-icon').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ url("/admin/cmspro/mddoc/settings/icon/reset") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            dataType: 'json'
        }).done(function (res) {
            hasUploaded = false;
            uploadedUrl = '';
            refreshIconState();
            layui.layer.msg(res.message || '已恢复默认图标', { icon: 1 });
        }).fail(function () {
            layui.layer.msg('操作失败，请稍后重试', { icon: 2 });
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // 保存名称与链接
    $('#btn-save').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ url("/admin/cmspro/mddoc/settings") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: {
                brand_name: $name.val(),
                brand_link: $link.val()
            },
            dataType: 'json'
        }).done(function (res) {
            if (res.code === 0) {
                layui.layer.msg(res.message, { icon: 1 });
            } else {
                layui.layer.msg(res.message || '保存失败', { icon: 2 });
            }
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '保存失败，请稍后重试';
            layui.layer.msg(msg, { icon: 2 });
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // ===== Tab 切换 =====
    $('.tab-btn').on('click', function () {
        var tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-panel').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // ===== 菜单管理 =====
    // 当前菜单数据（title/url），页面加载时从服务端配置初始化
    var navItems = @json($navs);

    // 属性值转义（防止引号破坏 HTML 结构）
    function escapeAttr(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // 渲染全部菜单行
    function renderNavRows() {
        var $rows = $('#nav-rows').empty();
        if (!navItems.length) {
            $rows.append('<div class="nav-rows-empty">暂无菜单，点击「添加菜单」或保存后恢复默认菜单。</div>');
            return;
        }
        navItems.forEach(function (item, index) {
            $rows.append(
                '<div class="nav-row">' +
                '<input type="text" class="form-input nav-title" placeholder="菜单名称" value="' + escapeAttr(item.title) + '">' +
                '<input type="text" class="form-input nav-url" placeholder="跳转链接，如 / 或 /apps" value="' + escapeAttr(item.url) + '">' +
                '<button class="layui-btn layui-btn-sm layui-btn-danger nav-remove" type="button" data-index="' + index + '"><i class="fa fa-trash"></i></button>' +
                '</div>'
            );
        });
    }

    // 从行 DOM 收集当前编辑中的菜单数据
    function collectNavItems() {
        var items = [];
        $('#nav-rows .nav-row').each(function () {
            items.push({
                title: $(this).find('.nav-title').val().trim(),
                url: $(this).find('.nav-url').val().trim()
            });
        });
        return items;
    }

    $('#btn-add-nav').on('click', function () {
        navItems = collectNavItems();
        if (navItems.length >= 10) {
            layui.layer.msg('最多支持 10 个菜单项', { icon: 0 });
            return;
        }
        navItems.push({ title: '', url: '' });
        renderNavRows();
        $('#nav-rows .nav-row:last .nav-title').focus();
    });

    // 删除行（基于 DOM 实时收集，保证删除后索引正确）
    $('#nav-rows').on('click', '.nav-remove', function () {
        navItems = collectNavItems();
        navItems.splice($(this).data('index'), 1);
        renderNavRows();
    });

    $('#btn-save-navs').on('click', function () {
        var items = collectNavItems().filter(function (item) {
            return item.title !== '' || item.url !== '';
        });

        // 校验完整性行
        for (var i = 0; i < items.length; i++) {
            if (items[i].title === '' || items[i].url === '') {
                layui.layer.msg('第 ' + (i + 1) + ' 项的菜单名称和链接不能为空', { icon: 0 });
                return;
            }
        }

        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ url("/admin/cmspro/mddoc/settings/navs") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: { navs: items },
            dataType: 'json'
        }).done(function (res) {
            if (res.code === 0) {
                navItems = items;
                renderNavRows();
                layui.layer.msg(res.message, { icon: 1 });
            } else {
                layui.layer.msg(res.message || '保存失败', { icon: 2 });
            }
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '保存失败，请稍后重试';
            layui.layer.msg(msg, { icon: 2 });
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    renderNavRows();
    refreshIconState();
    });
</script>
</body>
</html>
