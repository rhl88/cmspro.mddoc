<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MD文档管理</title>
    <link rel="stylesheet" href="{{ asset('CmsProUi/component/pear/css/pear.css') }}">
    <link rel="stylesheet" href="{{ asset('CmsProUi/font-awesome/4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('Admin/css/reset.css') }}">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; }
        .pear-container { height: 100%; display: flex; flex-direction: column; }
        .pear-container .layui-card { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .pear-container .layui-card-body { flex: 1; min-height: 0; overflow: auto; display: flex; flex-direction: column; }
        .pear-container .layui-card-body > .layui-row { flex: 1; display: flex; min-height: 0; }
        .layui-col-md4, .layui-col-md8 { display: flex; flex-direction: column; min-height: 0; }
        #editor-panel { flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden; }
        #welcome-panel { flex: 1; display: flex; align-items: center; justify-content: center; }
        .file-tree { padding: 10px 0; }
        .tree-item { display: flex; align-items: center; padding: 6px 12px; cursor: pointer; border-radius: 4px; margin: 1px 0; position: relative; }
        .tree-item:hover { background: #f2f2f2; }
        .tree-item:hover .tree-actions { display: flex; }
        .tree-item.active { background: #e6f7ff; color: #1890ff; }
        .tree-item .fa { margin-right: 8px; font-size: 14px; flex-shrink: 0; }
        .tree-item .fa-folder { color: #faad14; }
        .tree-item .fa-folder-open { color: #faad14; }
        .tree-item .fa-file-text-o { color: #1890ff; }
        .tree-item .tree-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tree-actions { display: none; gap: 4px; margin-left: 8px; flex-shrink: 0; align-items: center; }
        .tree-actions i { cursor: pointer; padding: 2px 4px; border-radius: 3px; font-size: 12px; color: #999; transition: all 0.2s; }
        .tree-actions .fa-pencil:hover { color: #1890ff; background: #e6f7ff; }
        .tree-actions .fa-trash-o:hover { color: #ff4d4f; background: #fff1f0; }
        .tree-dir { font-weight: 500; }
        .tree-children { padding-left: 20px; }
        .tree-children.collapsed { display: none; }
        .action-bar { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .breadcrumb { margin-bottom: 12px; padding: 8px 12px; background: #fafafa; border-radius: 4px; font-size: 13px; color: #666; }
        .breadcrumb a { color: #1890ff; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .empty-tip { text-align: center; padding: 40px; color: #999; }

        /* ===== 文档树收起/展开 ===== */
        .tree-panel-toggle { width: 16px; min-width: 16px; margin: 0 2px; background: #fff; border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #999; transition: background .2s; }
        .tree-panel-toggle:hover { color: #1890ff; background: #f6f6f6; }
        #tree-column.collapsed { display: none; }
        .mddoc-row.tree-collapsed .layui-col-md8 { width: 100%; }

        /* ===== 可视化编辑器 ===== */
        .md-toolbar { display: flex; align-items: center; gap: 2px; flex-wrap: wrap; padding: 6px 8px; background: #fafafa; border: 1px solid #e8e8e8; border-radius: 4px 4px 0 0; border-bottom: none; }
        .md-toolbar .tb-btn { min-width: 30px; height: 28px; padding: 0 8px; border: none; background: transparent; border-radius: 4px; cursor: pointer; color: #555; font-size: 13px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s; }
        .md-toolbar .tb-btn:hover { background: #e6f7ff; color: #1890ff; }
        .md-toolbar .tb-btn:active { background: #bae7ff; }
        .md-toolbar .tb-btn .fa { font-size: 13px; }
        .md-toolbar .tb-btn.tb-heading { font-weight: 700; font-family: Georgia, serif; }
        .md-toolbar .tb-sep { width: 1px; height: 16px; background: #e0e0e0; margin: 0 6px; }
        .md-toolbar .tb-tip { margin-left: auto; font-size: 12px; color: #b0b0b0; user-select: none; }
        .editor-split { flex: 1; display: flex; gap: 0; min-height: 0; border: 1px solid #e8e8e8; border-radius: 0 0 4px 4px; overflow: hidden; }
        .editor-pane { flex: 1; display: flex; flex-direction: column; min-width: 0; border-right: 1px solid #e8e8e8; }
        .editor-area { flex: 1; width: 100%; min-height: 0; font-family: Consolas, Monaco, monospace; font-size: 13px; line-height: 1.7; padding: 12px; border: none; outline: none; resize: none; box-sizing: border-box; }
        .preview-pane { flex: 1; min-width: 0; overflow-y: auto; background: #fff; }
        #md-preview { padding: 16px 22px; font-size: 14px; line-height: 1.8; color: #333; word-wrap: break-word; }
        #md-preview h1, #md-preview h2, #md-preview h3, #md-preview h4, #md-preview h5, #md-preview h6 { margin: 20px 0 12px; font-weight: 600; color: #222; line-height: 1.4; }
        #md-preview h1 { font-size: 24px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        #md-preview h2 { font-size: 20px; padding-left: 10px; border-left: 4px solid #1890ff; }
        #md-preview h3 { font-size: 17px; }
        #md-preview h4 { font-size: 15px; }
        #md-preview p { margin: 0 0 12px; }
        #md-preview ul, #md-preview ol { margin: 0 0 12px; padding-left: 24px; }
        #md-preview li { margin-bottom: 4px; }
        #md-preview a { color: #1890ff; text-decoration: none; }
        #md-preview a:hover { text-decoration: underline; }
        #md-preview code { font-family: Consolas, Monaco, monospace; font-size: 13px; background: #f5f5f5; color: #d73a49; padding: 2px 6px; border-radius: 3px; }
        #md-preview pre { background: #0d1117; border-radius: 6px; padding: 12px 16px; margin: 12px 0; overflow-x: auto; }
        #md-preview pre code { background: transparent; color: #e6edf3; padding: 0; white-space: pre; }
        #md-preview blockquote { margin: 12px 0; padding: 8px 16px; border-left: 4px solid #1890ff; background: #f0f7ff; color: #555; }
        #md-preview blockquote p:last-child { margin-bottom: 0; }
        #md-preview table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 13px; }
        #md-preview th { background: #f5f7fa; font-weight: 600; text-align: left; padding: 8px 12px; border: 1px solid #e8e8e8; }
        #md-preview td { padding: 8px 12px; border: 1px solid #e8e8e8; }
        #md-preview tr:hover td { background: #fafafa; }
        #md-preview hr { border: none; height: 1px; background: #e8e8e8; margin: 18px 0; }
        #md-preview img { max-width: 100%; }
        #md-preview p:last-child, #md-preview ul:last-child, #md-preview ol:last-child, #md-preview pre:last-child, #md-preview blockquote:last-child { margin-bottom: 0; }

        /* ===== 新建文档对话框 ===== */
        .newdoc-form { padding: 16px 20px 6px; }
        .newdoc-form .form-item { margin-bottom: 16px; }
        .newdoc-form .form-label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; }
        .newdoc-form .form-label em { color: #ff4d4f; font-style: normal; margin-right: 2px; }
        .newdoc-form .form-input, .newdoc-form .form-select { width: 100%; height: 36px; padding: 0 10px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 13px; box-sizing: border-box; outline: none; transition: border-color 0.2s; background: #fff; }
        .newdoc-form .form-input:focus, .newdoc-form .form-select:focus { border-color: #1890ff; }
        .newdoc-form .tpl-options { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .newdoc-form .tpl-item { position: relative; cursor: pointer; }
        .newdoc-form .tpl-item input { display: none; }
        .newdoc-form .tpl-card { display: block; padding: 10px 8px; text-align: center; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; color: #555; transition: all 0.15s; }
        .newdoc-form .tpl-card .fa { display: block; font-size: 18px; margin-bottom: 6px; color: #999; }
        .newdoc-form .tpl-item input:checked + .tpl-card { border-color: #1890ff; background: #e6f7ff; color: #1890ff; }
        .newdoc-form .tpl-item input:checked + .tpl-card .fa { color: #1890ff; }
    </style>
</head>
<body>
<div class="pear-container">
    <div class="layui-card">
        <div class="layui-card-header">
            <i class="fa fa-file-text"></i> MD文档管理
            <span style="font-size:12px;color:#999;margin-left:8px;">文档存储路径：storage/app/mddoc/</span>
        </div>
        <div class="layui-card-body">
            <div class="layui-row layui-col-space15 mddoc-row">
                <!-- 左侧文件树 -->
                <div class="layui-col-md4" id="tree-column">
                    <div class="action-bar">
                        <button class="layui-btn layui-btn-sm" id="btn-add-dir"><i class="fa fa-folder-o"></i> 新建文件夹</button>
                        <button class="layui-btn layui-btn-sm layui-btn-normal" id="btn-upload"><i class="fa fa-upload"></i> 上传文件</button>
                        <button class="layui-btn layui-btn-sm layui-btn-warm" id="btn-new-file"><i class="fa fa-file-text-o"></i> 新建文档</button>
                        <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-refresh"><i class="fa fa-refresh"></i> 刷新</button>
                    </div>
                    <div id="file-tree" class="file-tree">
                        <div class="empty-tip">加载中...</div>
                    </div>
                </div>
                <!-- 文档树收起/展开竖条 -->
                <div class="tree-panel-toggle" id="tree-panel-toggle" title="收起文档树，沉浸式编辑">
                    <i class="fa fa-chevron-left"></i>
                </div>
                <!-- 右侧编辑区 -->
                <div class="layui-col-md8">
                    <div id="editor-panel" style="display:none;">
                        <div class="breadcrumb" id="file-breadcrumb"></div>
                        <div class="action-bar">
                            <button class="layui-btn layui-btn-sm layui-btn-normal" id="btn-save"><i class="fa fa-save"></i> 保存</button>
                            <button class="layui-btn layui-btn-sm layui-btn-danger" id="btn-delete"><i class="fa fa-trash-o"></i> 删除</button>
                            <span style="font-size:12px;color:#b0b0b0;line-height:30px;margin-left:4px;">快捷键 Ctrl+S 保存</span>
                        </div>
                        <!-- Markdown 格式工具栏 -->
                        <div class="md-toolbar" id="md-toolbar">
                            <button type="button" class="tb-btn tb-heading" data-md="h1" title="一级标题">H1</button>
                            <button type="button" class="tb-btn tb-heading" data-md="h2" title="二级标题">H2</button>
                            <button type="button" class="tb-btn tb-heading" data-md="h3" title="三级标题">H3</button>
                            <span class="tb-sep"></span>
                            <button type="button" class="tb-btn" data-md="bold" title="加粗"><i class="fa fa-bold"></i></button>
                            <button type="button" class="tb-btn" data-md="italic" title="斜体"><i class="fa fa-italic"></i></button>
                            <button type="button" class="tb-btn" data-md="inline-code" title="行内代码"><i class="fa fa-code"></i></button>
                            <button type="button" class="tb-btn" data-md="code-block" title="代码块"><i class="fa fa-file-code-o"></i></button>
                            <span class="tb-sep"></span>
                            <button type="button" class="tb-btn" data-md="link" title="链接"><i class="fa fa-link"></i></button>
                            <button type="button" class="tb-btn" data-md="image" title="图片"><i class="fa fa-picture-o"></i></button>
                            <span class="tb-sep"></span>
                            <button type="button" class="tb-btn" data-md="ul" title="无序列表"><i class="fa fa-list-ul"></i></button>
                            <button type="button" class="tb-btn" data-md="ol" title="有序列表"><i class="fa fa-list-ol"></i></button>
                            <button type="button" class="tb-btn" data-md="quote" title="引用"><i class="fa fa-quote-left"></i></button>
                            <button type="button" class="tb-btn" data-md="table" title="表格"><i class="fa fa-table"></i></button>
                            <button type="button" class="tb-btn" data-md="hr" title="分割线"><i class="fa fa-minus"></i></button>
                            <span class="tb-tip">编辑左侧内容，右侧实时预览</span>
                        </div>
                        <!-- 分屏：左编辑右预览 -->
                        <div class="editor-split">
                            <div class="editor-pane">
                                <textarea id="file-editor" class="editor-area" placeholder="在此编辑 Markdown 内容..."></textarea>
                            </div>
                            <div class="preview-pane" id="preview-pane">
                                <div id="md-preview"></div>
                            </div>
                        </div>
                    </div>
                    <div id="welcome-panel">
                        <div class="empty-tip">
                            <i class="fa fa-file-text" style="font-size:48px;color:#d9d9d9;"></i>
                            <p style="margin-top:16px;">请从左侧选择文件进行编辑，或点击「新建文档」创建</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
<script src="{{ asset('apps/cmspro.mddoc/js/marked.min.js') }}"></script>
<script>
layui.use(['layer', 'form', 'jquery'], function(){
    var layer = layui.layer;
    var form = layui.form;
    var $ = layui.jquery;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    var currentPath = '';
    var currentType = '';
    var currentDir = '';
    var treeData = [];          // 原始树数据（用于新建文档对话框收集目录）
    var previewTimer = null;    // 预览防抖定时器

    // 加载文件树
    function loadTree() {
        $.get('/admin/cmspro/mddoc/tree', function(res) {
            if (res.code !== 0) {
                $('#file-tree').html('<div class="empty-tip">加载失败</div>');
                return;
            }
            treeData = res.data || [];
            var html = renderTree(treeData, '');
            if (!html) {
                $('#file-tree').html('<div class="empty-tip">暂无文档，请上传或新建</div>');
            } else {
                $('#file-tree').html(html);
            }
            bindTreeEvents();
        });
    }

    // 渲染文件树
    function renderTree(items, parentPath) {
        var html = '';
        items.forEach(function(item) {
            if (item.type === 'directory') {
                var fullPath = parentPath ? parentPath + '/' + item.name : item.name;
                html += '<div class="tree-item tree-dir" data-path="' + escapeAttr(fullPath) + '" data-type="directory">';
                html += '<i class="fa fa-folder"></i> ';
                html += '<span class="tree-name">' + escapeHtml(item.name) + '</span>';
                html += '<span class="tree-actions">';
                html += '<i class="fa fa-pencil" title="重命名" data-action="rename-dir" data-path="' + escapeAttr(fullPath) + '"></i>';
                html += '<i class="fa fa-trash-o" title="删除" data-action="delete-dir" data-path="' + escapeAttr(fullPath) + '"></i>';
                html += '</span>';
                html += '</div>';
                html += '<div class="tree-children collapsed" data-parent="' + escapeAttr(fullPath) + '">';
                if (item.children && item.children.length > 0) {
                    html += renderTree(item.children, fullPath);
                }
                html += '</div>';
            } else {
                var fullPath = parentPath ? parentPath + '/' + item.name : item.name;
                html += '<div class="tree-item" data-path="' + escapeAttr(fullPath) + '" data-type="file">';
                html += '<i class="fa fa-file-text-o"></i> ';
                html += '<span class="tree-name">' + escapeHtml(item.name) + '</span>';
                html += '<span class="tree-actions">';
                html += '<i class="fa fa-pencil" title="重命名" data-action="rename" data-path="' + escapeAttr(fullPath) + '"></i>';
                html += '<i class="fa fa-trash-o" title="删除" data-action="delete" data-path="' + escapeAttr(fullPath) + '"></i>';
                html += '</span>';
                html += '</div>';
            }
        });
        return html;
    }

    // 绑定文件树事件
    function bindTreeEvents() {
        // 目录点击展开/收起 + 选中目录
        $('.tree-dir').off('click').on('click', function(e) {
            if ($(e.target).closest('.tree-actions').length) return;
            var $this = $(this);
            var path = $this.data('path');
            var $children = $('[data-parent="' + path + '"]');

            // 选中目录
            $('.tree-item').removeClass('active');
            $this.addClass('active');
            currentDir = path;

            if ($children.hasClass('collapsed')) {
                $children.removeClass('collapsed');
                $this.find('.fa-folder').removeClass('fa-folder').addClass('fa-folder-open');
            } else {
                $children.addClass('collapsed');
                $this.find('.fa-folder-open').removeClass('fa-folder-open').addClass('fa-folder');
            }
        });

        // 文件点击编辑
        $('.tree-item[data-type="file"]').off('click').on('click', function(e) {
            if ($(e.target).closest('.tree-actions').length) return;
            var $this = $(this);
            var path = $this.data('path');

            $('.tree-item').removeClass('active');
            $this.addClass('active');

            loadFile(path);
        });

        // 操作按钮事件（重命名、删除）
        $('.tree-actions i').off('click').on('click', function(e) {
            e.stopPropagation();
            var action = $(this).data('action');
            var path = $(this).data('path');

            if (action === 'rename') {
                doRename(path);
            } else if (action === 'delete') {
                doDeleteFile(path);
            } else if (action === 'rename-dir') {
                doRenameDir(path);
            } else if (action === 'delete-dir') {
                doDeleteDir(path);
            }
        });
    }

    // 重命名文件
    function doRename(oldPath) {
        var oldName = oldPath.split('/').pop();
        layer.prompt({
            title: '重命名文件',
            formType: 0,
            value: oldName.replace(/\.md$/, ''),
        }, function(value, index) {
            if (!value || !value.trim()) {
                layer.msg('文件名不能为空', { icon: 2 });
                return;
            }
            $.post('/admin/cmspro/mddoc/rename', { old_path: oldPath, new_name: value.trim() }, function(res) {
                if (res.code === 0) {
                    layer.msg('重命名成功', { icon: 1 });
                    loadTree();
                    if (currentPath === oldPath) {
                        loadFile(res.data.new_path);
                    }
                } else {
                    layer.msg(res.message, { icon: 2 });
                }
                layer.close(index);
            });
        });
    }

    // 删除文件（从树操作）
    function doDeleteFile(path) {
        layer.confirm('确定要删除 "' + path + '" 吗？此操作不可恢复！', { icon: 3, title: '确认删除' }, function(index) {
            $.ajax({
                url: '/admin/cmspro/mddoc/file',
                type: 'DELETE',
                data: { path: path },
                success: function(res) {
                    if (res.code === 0) {
                        layer.msg('删除成功', { icon: 1 });
                        loadTree();
                        if (currentPath === path) {
                            showWelcome();
                        }
                    } else {
                        layer.msg(res.message, { icon: 2 });
                    }
                }
            });
            layer.close(index);
        });
    }

    // 重命名目录
    function doRenameDir(oldPath) {
        var oldName = oldPath.split('/').pop();
        layer.prompt({
            title: '重命名文件夹',
            formType: 0,
            value: oldName,
        }, function(value, index) {
            if (!value || !value.trim()) {
                layer.msg('文件夹名不能为空', { icon: 2 });
                return;
            }
            $.post('/admin/cmspro/mddoc/rename-directory', { old_path: oldPath, new_name: value.trim() }, function(res) {
                if (res.code === 0) {
                    layer.msg('重命名成功', { icon: 1 });
                    loadTree();
                } else {
                    layer.msg(res.message, { icon: 2 });
                }
                layer.close(index);
            });
        });
    }

    // 删除目录（从树操作，需检查是否为空）
    function doDeleteDir(path) {
        $.ajax({
            url: '/admin/cmspro/mddoc/directory',
            type: 'DELETE',
            data: { path: path },
            success: function(res) {
                if (res.code === 0) {
                    layer.msg('删除成功', { icon: 1 });
                    loadTree();
                    if (currentPath && currentPath.startsWith(path)) {
                        showWelcome();
                    }
                } else {
                    layer.msg(res.message, { icon: 2 });
                }
            }
        });
    }

    // 加载文件内容
    function loadFile(path) {
        $.get('/admin/cmspro/mddoc/read', { path: path }, function(res) {
            if (res.code !== 0) {
                layer.msg('读取文件失败', { icon: 2 });
                return;
            }

            currentPath = path;
            currentType = 'file';

            $('#file-editor').val(res.data.content);
            $('#file-breadcrumb').html(renderBreadcrumb(path));
            $('#editor-panel').show();
            $('#welcome-panel').hide();
            renderPreview();
        });
    }

    // 渲染面包屑
    function renderBreadcrumb(path) {
        var parts = path.split('/');
        var html = '<a href="javascript:void(0)" onclick="showWelcome()">根目录</a>';
        var accumulated = '';
        parts.forEach(function(part, index) {
            accumulated = accumulated ? accumulated + '/' + part : part;
            if (index < parts.length - 1) {
                html += ' / <a href="javascript:void(0)">' + escapeHtml(part) + '</a>';
            } else {
                html += ' / ' + escapeHtml(part);
            }
        });
        return html;
    }

    // 显示欢迎面板
    window.showWelcome = function() {
        currentPath = '';
        currentType = '';
        $('#editor-panel').hide();
        $('#welcome-panel').show();
    };

    // ===== 可视化编辑器：工具栏与预览 =====

    // 获取编辑器 DOM
    function getEditorEl() {
        return document.getElementById('file-editor');
    }

    // 行内包裹：在选区前后插入 before/after 标记（无选中时插入占位文本）
    function wrapSelection(before, after, placeholder) {
        var el = getEditorEl();
        var start = el.selectionStart, end = el.selectionEnd;
        var value = el.value;
        var selected = value.substring(start, end);
        var inner = selected || (placeholder || '');
        var newValue = value.substring(0, start) + before + inner + after + value.substring(end);
        el.value = newValue;
        el.focus();
        var selStart = start + before.length;
        var selEnd = selStart + inner.length;
        el.setSelectionRange(selStart, selEnd);
        schedulePreview();
    }

    // 行首前缀：为选中行（或光标所在行）逐行添加前缀（标题/列表/引用）
    function insertLinePrefix(prefix) {
        var el = getEditorEl();
        var start = el.selectionStart, end = el.selectionEnd;
        var value = el.value;
        var lineStart = value.lastIndexOf('\n', start - 1) + 1;
        var lineEnd = value.indexOf('\n', end);
        if (lineEnd === -1) lineEnd = value.length;
        var block = value.substring(lineStart, lineEnd);
        var lines = block.split('\n');
        var prefixed = lines.map(function(line) {
            return line.trim().length ? prefix + line : line;
        }).join('\n');
        el.value = value.substring(0, lineStart) + prefixed + value.substring(lineEnd);
        el.focus();
        el.setSelectionRange(lineStart, lineStart + prefixed.length);
        schedulePreview();
    }

    // 块级插入：在光标处插入完整模板片段（代码块/表格/分割线等）
    function insertBlockSnippet(text) {
        var el = getEditorEl();
        var start = el.selectionStart, end = el.selectionEnd;
        var value = el.value;
        var before = value.substring(0, start);
        var after = value.substring(end);
        // 保证块级片段前后均有空行，避免 Markdown 解析异常
        var padBefore = before && !/\n\n$/.test(before) ? (/\n$/.test(before) ? '\n' : '\n\n') : '';
        var padAfter = after && !/^\n/.test(after) ? '\n' : '';
        var insertion = padBefore + text + padAfter;
        el.value = before + insertion + after;
        el.focus();
        var caret = (before + padBefore + text).length;
        el.setSelectionRange(caret, caret);
        schedulePreview();
    }

    // 工具栏动作映射
    var toolbarActions = {
        'h1': function() { insertLinePrefix('# '); },
        'h2': function() { insertLinePrefix('## '); },
        'h3': function() { insertLinePrefix('### '); },
        'bold': function() { wrapSelection('**', '**', '加粗文字'); },
        'italic': function() { wrapSelection('*', '*', '斜体文字'); },
        'inline-code': function() { wrapSelection('`', '`', '代码'); },
        'code-block': function() { insertBlockSnippet('```php\n// 代码内容\n```\n'); },
        'link': function() { wrapSelection('[', '](https://)', '链接文字'); },
        'image': function() { wrapSelection('![', '](https://)', '图片描述'); },
        'ul': function() { insertLinePrefix('- '); },
        'ol': function() { insertOrderedList(); },
        'quote': function() { insertLinePrefix('> '); },
        'table': function() { insertBlockSnippet('| 列一 | 列二 | 列三 |\n| --- | --- | --- |\n| 内容 | 内容 | 内容 |\n'); },
        'hr': function() { insertBlockSnippet('---\n'); }
    };

    // 有序列表：逐行加递增序号
    function insertOrderedList() {
        var el = getEditorEl();
        var start = el.selectionStart, end = el.selectionEnd;
        var value = el.value;
        var lineStart = value.lastIndexOf('\n', start - 1) + 1;
        var lineEnd = value.indexOf('\n', end);
        if (lineEnd === -1) lineEnd = value.length;
        var lines = value.substring(lineStart, lineEnd).split('\n');
        var index = 0;
        var result = lines.map(function(line) {
            if (!line.trim().length) return line;
            index++;
            return index + '. ' + line;
        }).join('\n');
        el.value = value.substring(0, lineStart) + result + value.substring(lineEnd);
        el.focus();
        el.setSelectionRange(lineStart, lineStart + result.length);
        schedulePreview();
    }

    // 绑定工具栏点击
    $('#md-toolbar').off('click').on('click', '.tb-btn', function() {
        var action = $(this).data('md');
        if (toolbarActions[action]) {
            toolbarActions[action]();
        }
    });

    // 预览渲染（300ms 防抖）
    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(renderPreview, 300);
    }

    function renderPreview() {
        if (typeof marked === 'undefined') return;
        var content = $('#file-editor').val();
        var html;
        try {
            html = marked.parse(content || '');
        } catch (e) {
            html = '<p style="color:#ff4d4f;">预览渲染失败：' + escapeHtml(e.message) + '</p>';
        }
        $('#md-preview').html(html);
        if (typeof hljs !== 'undefined') {
            $('#md-preview pre code').each(function() {
                try { hljs.highlightElement(this); } catch (e) {}
            });
        }
    }

    // 编辑器输入 → 实时预览
    $('#file-editor').off('input.mddoc').on('input.mddoc', function() {
        schedulePreview();
    });

    // Ctrl+S / Cmd+S 快捷保存
    $('#file-editor').off('keydown.mddoc').on('keydown.mddoc', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            saveCurrentFile();
        }
    });

    // ===== 分屏滚动同步 =====
    // 同步锁：程序设置对方 scrollTop 会触发对方 scroll 事件，用标志位跳过程序触发的滚动，避免来回震荡
    var isSyncingScroll = false;

    // 按滚动比例同步：source 滚动时，把相同比例应用到 target
    function syncScrollByRatio($source, $target) {
        if (isSyncingScroll) { isSyncingScroll = false; return; }
        var source = $source[0], target = $target[0];
        var sourceRange = source.scrollHeight - source.clientHeight;
        if (sourceRange <= 0) return;
        var ratio = source.scrollTop / sourceRange;
        isSyncingScroll = true;
        target.scrollTop = ratio * (target.scrollHeight - target.clientHeight);
    }

    // 左侧编辑器滚动 → 右侧预览跟随
    $('#file-editor').off('scroll.mddoc').on('scroll.mddoc', function() {
        syncScrollByRatio($('#file-editor'), $('.preview-pane'));
    });

    // 右侧预览滚动 → 左侧编辑器跟随
    $('.preview-pane').off('scroll.mddoc').on('scroll.mddoc', function() {
        syncScrollByRatio($('.preview-pane'), $('#file-editor'));
    });

    // ===== 新建文档对话框 =====

    // 文档模板定义
    var DOC_TEMPLATES = {
        blank: function(name) {
            return '# ' + name + '\n\n';
        },
        standard: function(name) {
            return '# ' + name + '\n\n> 版本：v1.0.0 | 更新日期：' + formatDate() + '\n\n---\n\n## 一、概述\n\n简要说明本文档的目的与适用范围。\n\n## 二、前置准备\n\n使用前需要具备的条件或完成的配置。\n\n## 三、操作说明\n\n### 3.1 步骤一\n\n具体操作内容。\n\n### 3.2 步骤二\n\n具体操作内容。\n\n## 四、常见问题\n\n| 问题 | 说明 |\n| --- | --- |\n| 示例问题 | 处理方式 |\n\n## 五、注意事项\n\n- 重要约束与提醒。\n';
        },
        api: function(name) {
            return '# ' + name + '\n\n> 版本：v1.0.0 | 更新日期：' + formatDate() + '\n\n---\n\n## 接口说明\n\n简要描述接口用途。\n\n## 请求方式\n\n```\nGET /api/example\n```\n\n## 请求参数\n\n| 参数名 | 类型 | 必填 | 说明 |\n| --- | --- | --- | --- |\n| id | int | 是 | 示例参数 |\n\n## 响应示例\n\n```json\n{\n    "code": 0,\n    "message": "success",\n    "data": {}\n}\n```\n\n## 错误码\n\n| 错误码 | 说明 |\n| --- | --- |\n| 40001 | 参数错误 |\n';
        }
    };

    // 格式化当前日期（Y-m-d）
    function formatDate() {
        var d = new Date();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    // 从树数据递归收集目录列表（含层级深度，用于树形下拉）
    function collectDirectories(items, parentPath, result, depth) {
        items.forEach(function(item) {
            if (item.type === 'directory') {
                var fullPath = parentPath ? parentPath + '/' + item.name : item.name;
                result.push({ path: fullPath, name: item.name, depth: depth });
                if (item.children && item.children.length > 0) {
                    collectDirectories(item.children, fullPath, result, depth + 1);
                }
            }
        });
        return result;
    }

    // 渲染目录树形下拉选项：根目录作为树干置顶，子目录统一挂在其下缩进展示
    // 结构示例：根目录 / 　└ 入门指南 / 　　└ 子目录；selected 为需选中的目录全路径
    function buildDirOptions(selected) {
        var dirs = collectDirectories(treeData, '', [], 0);
        var options = '<option value="">根目录</option>';
        dirs.forEach(function(dir) {
            var indent = '';
            for (var i = 0; i <= dir.depth; i++) indent += '\u3000';
            indent += '\u2514 ';
            var selectedAttr = (dir.path === selected) ? ' selected' : '';
            options += '<option value="' + escapeAttr(dir.path) + '"' + selectedAttr + '>'
                + escapeHtml(indent + dir.name) + '</option>';
        });
        return options;
    }

    // 打开新建文档对话框
    function openNewDocDialog() {
        var dirOptions = buildDirOptions(currentDir);

        var html = '<div class="newdoc-form">'
            + '<div class="form-item">'
            + '<label class="form-label"><em>*</em>目标目录</label>'
            + '<select id="newdoc-dir" class="form-select">' + dirOptions + '</select>'
            + '</div>'
            + '<div class="form-item">'
            + '<label class="form-label"><em>*</em>文件名</label>'
            + '<input type="text" id="newdoc-name" class="form-input" placeholder="如：快速开始（无需填写 .md 后缀）" maxlength="80">'
            + '</div>'
            + '<div class="form-item">'
            + '<label class="form-label">文档模板</label>'
            + '<div class="tpl-options">'
            + '<label class="tpl-item"><input type="radio" name="newdoc-tpl" value="blank" checked><span class="tpl-card"><i class="fa fa-file-o"></i>空白文档</span></label>'
            + '<label class="tpl-item"><input type="radio" name="newdoc-tpl" value="standard"><span class="tpl-card"><i class="fa fa-file-text-o"></i>标准文档</span></label>'
            + '<label class="tpl-item"><input type="radio" name="newdoc-tpl" value="api"><span class="tpl-card"><i class="fa fa-code"></i>接口文档</span></label>'
            + '</div>'
            + '</div>'
            + '</div>';

        layer.open({
            type: 1,
            title: '新建文档',
            area: ['500px', '400px'],
            content: html,
            btn: ['创建文档', '取消'],
            success: function() {
                $('#newdoc-name').focus();
            },
            yes: function(index) {
                var dir = $('#newdoc-dir').val();
                var name = $('#newdoc-name').val().trim();
                var tpl = $('input[name="newdoc-tpl"]:checked').val();

                if (!name) {
                    layer.msg('请填写文件名', { icon: 2 });
                    return;
                }
                // 去除用户误输入的后缀，统一由服务端路径规则补 .md
                name = name.replace(/\.md$/i, '');

                var path = dir ? dir + '/' + name + '.md' : name + '.md';
                var content = DOC_TEMPLATES[tpl] ? DOC_TEMPLATES[tpl](name) : DOC_TEMPLATES.blank(name);

                $.post('/admin/cmspro/mddoc/upload', { path: path, content: content }, function(res) {
                    if (res.code === 0) {
                        layer.msg('创建成功', { icon: 1 });
                        layer.close(index);
                        loadTree();
                        loadFile(path);
                    } else {
                        layer.msg(res.message, { icon: 2 });
                    }
                }).fail(function() {
                    layer.msg('创建失败，请检查网络', { icon: 2 });
                });
            }
        });
    }

    // 新建文件夹（对话框：与上传文件相同模式——父目录树形下拉默认选中当前目录）
    $('#btn-add-dir').on('click', function() {
        var html = '<div style="padding:20px;">'
            + '<div class="layui-form-item"><label class="layui-form-label">父目录</label>'
            + '<div class="layui-input-block"><select id="new-dir-parent" class="layui-input">' + buildDirOptions(currentDir) + '</select></div></div>'
            + '<div class="layui-form-item"><label class="layui-form-label">文件夹名称</label>'
            + '<div class="layui-input-block"><input type="text" id="new-dir-name" placeholder="如：入门指南" class="layui-input"></div></div>'
            + '<div style="text-align:center;margin-top:20px;"><button type="button" class="layui-btn" id="btn-do-add-dir">确认创建</button></div>'
            + '</div>';
        layer.open({
            type: 1,
            title: '新建文件夹',
            area: ['450px', '260px'],
            content: html,
            success: function() {
                $('#new-dir-name').focus();
            }
        });
    });

    // 确认创建文件夹（事件委托：弹窗 DOM 每次打开都是新建的）
    $(document).off('click', '#btn-do-add-dir').on('click', '#btn-do-add-dir', function() {
        var name = $('#new-dir-name').val().trim();
        if (!name) {
            layer.msg('请输入文件夹名称', { icon: 2 });
            return;
        }
        // 名称中不允许出现路径分隔符（层级由父目录下拉决定）
        if (name.indexOf('/') !== -1 || name.indexOf('\\') !== -1) {
            layer.msg('文件夹名称不能包含 / 或 \\', { icon: 2 });
            return;
        }
        var parent = $('#new-dir-parent').val();
        var path = parent ? parent + '/' + name : name;

        $.post('/admin/cmspro/mddoc/directory', { path: path }, function(res) {
            if (res.code === 0) {
                layer.msg('创建成功', { icon: 1 });
                loadTree();
                layer.closeAll();
            } else {
                layer.msg(res.message, { icon: 2 });
            }
        });
    });

    // 新建文档（对话框：目录 + 文件名 + 模板）
    $('#btn-new-file').on('click', openNewDocDialog);

    // 上传文件（对话框：与新建文档相同模式——点击时现场构建 HTML，目录树形下拉默认选中当前目录）
    $('#btn-upload').on('click', function() {
        var html = '<div style="padding:20px;">'
            + '<div class="layui-form-item"><label class="layui-form-label">上传目录</label>'
            + '<div class="layui-input-block"><select id="upload-directory" class="layui-input">' + buildDirOptions(currentDir) + '</select></div></div>'
            + '<div class="layui-form-item"><label class="layui-form-label">选择文件</label>'
            + '<div class="layui-input-block"><input type="file" id="upload-file" accept=".md,.txt,.markdown" class="layui-input" style="padding:4px;"></div></div>'
            + '<div style="text-align:center;margin-top:20px;"><button type="button" class="layui-btn" id="btn-do-upload">确认上传</button></div>'
            + '</div>';
        layer.open({
            type: 1,
            title: '上传MD文件',
            area: ['450px', '280px'],
            content: html
        });
    });

    // 确认上传（事件委托：弹窗 DOM 每次打开都是新建的）
    $(document).off('click', '#btn-do-upload').on('click', '#btn-do-upload', function() {
        var fileInput = document.getElementById('upload-file');
        if (!fileInput.files || !fileInput.files[0]) {
            layer.msg('请选择文件', { icon: 2 });
            return;
        }

        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('directory', $('#upload-directory').val());

        $.ajax({
            url: '/admin/cmspro/mddoc/upload-file',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.code === 0) {
                    layer.msg('上传成功', { icon: 1 });
                    loadTree();
                    layer.closeAll();
                } else {
                    layer.msg(res.message, { icon: 2 });
                }
            },
            error: function() {
                layer.msg('上传失败', { icon: 2 });
            }
        });
    });

    // 保存文件
    function saveCurrentFile() {
        if (!currentPath) {
            layer.msg('请先选择文件', { icon: 2 });
            return;
        }

        var content = $('#file-editor').val();
        $.post('/admin/cmspro/mddoc/upload', { path: currentPath, content: content }, function(res) {
            if (res.code === 0) {
                layer.msg('保存成功', { icon: 1 });
            } else {
                layer.msg(res.message, { icon: 2 });
            }
        }).fail(function() {
            layer.msg('保存失败，请检查网络', { icon: 2 });
        });
    }
    $('#btn-save').on('click', saveCurrentFile);

    // 删除
    $('#btn-delete').on('click', function() {
        if (!currentPath) return;

        layer.confirm('确定要删除 "' + currentPath + '" 吗？此操作不可恢复！', { icon: 3, title: '确认删除' }, function(index) {
            var url = currentType === 'directory' ? '/admin/cmspro/mddoc/directory' : '/admin/cmspro/mddoc/file';
            $.ajax({
                url: url,
                type: 'DELETE',
                data: { path: currentPath },
                success: function(res) {
                    if (res.code === 0) {
                        layer.msg('删除成功', { icon: 1 });
                        loadTree();
                        showWelcome();
                    } else {
                        layer.msg(res.message, { icon: 2 });
                    }
                    layer.close(index);
                }
            });
        });
    });

    // 刷新
    $('#btn-refresh').on('click', function() {
        loadTree();
    });

    // 文档树收起/展开（默认展开；收起后编辑区铺满，沉浸式编辑）
    var $treeColumn = $('#tree-column');
    var $treeToggle = $('#tree-panel-toggle');
    function setTreeCollapsed(collapsed) {
        $treeColumn.toggleClass('collapsed', collapsed);
        $('.mddoc-row').toggleClass('tree-collapsed', collapsed);
        $treeToggle.find('i').removeClass('fa-chevron-left fa-chevron-right')
            .addClass(collapsed ? 'fa-chevron-right' : 'fa-chevron-left');
        $treeToggle.attr('title', collapsed ? '展开文档树' : '收起文档树，沉浸式编辑');
    }
    $treeToggle.on('click', function() {
        setTreeCollapsed(!$treeColumn.hasClass('collapsed'));
    });

    // HTML 转义
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // 初始化加载
    loadTree();
});
</script>
</body>
</html>
