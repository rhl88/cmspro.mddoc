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
        .editor-area { flex: 1; width: 100%; min-height: 0; font-family: Consolas, Monaco, monospace; font-size: 13px; line-height: 1.6; padding: 12px; border: 1px solid #e8e8e8; border-radius: 4px; resize: none; box-sizing: border-box; }
        .breadcrumb { margin-bottom: 12px; padding: 8px 12px; background: #fafafa; border-radius: 4px; font-size: 13px; color: #666; }
        .breadcrumb a { color: #1890ff; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .empty-tip { text-align: center; padding: 40px; color: #999; }
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
            <div class="layui-row layui-col-space15">
                <!-- 左侧文件树 -->
                <div class="layui-col-md4">
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
                <!-- 右侧编辑区 -->
                <div class="layui-col-md8">
                    <div id="editor-panel" style="display:none;">
                        <div class="breadcrumb" id="file-breadcrumb"></div>
                        <div class="action-bar">
                            <button class="layui-btn layui-btn-sm layui-btn-normal" id="btn-save"><i class="fa fa-save"></i> 保存</button>
                            <button class="layui-btn layui-btn-sm layui-btn-danger" id="btn-delete"><i class="fa fa-trash-o"></i> 删除</button>
                        </div>
                        <textarea id="file-editor" class="editor-area" placeholder="在此编辑 Markdown 内容..."></textarea>
                    </div>
                    <div id="welcome-panel">
                        <div class="empty-tip">
                            <i class="fa fa-file-text" style="font-size:48px;color:#d9d9d9;"></i>
                            <p style="margin-top:16px;">请从左侧选择文件进行编辑</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 上传弹窗 -->
<div id="upload-dialog" style="display:none;padding:20px;">
    <form class="layui-form" lay-filter="uploadForm">
        <div class="layui-form-item">
            <label class="layui-form-label">上传目录</label>
            <div class="layui-input-block">
                <input type="text" name="directory" id="upload-directory" placeholder="留空为根目录，如：入门指南" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">选择文件</label>
            <div class="layui-input-block">
                <input type="file" name="file" id="upload-file" accept=".md,.txt,.markdown" class="layui-input" style="padding:4px;">
            </div>
        </div>
        <div class="layui-form-item" style="text-align:center;margin-top:20px;">
            <button type="button" class="layui-btn" id="btn-do-upload">确认上传</button>
        </div>
    </form>
</div>

<script src="{{ asset('CmsProUi/component/layui/layui.js') }}"></script>
<script src="{{ asset('CmsProUi/component/pear/pear.js') }}"></script>
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

    // 加载文件树
    function loadTree() {
        $.get('/admin/cmspro/mddoc/tree', function(res) {
            if (res.code !== 0) {
                $('#file-tree').html('<div class="empty-tip">加载失败</div>');
                return;
            }
            var html = renderTree(res.data, '');
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

    // 新建文件夹
    $('#btn-add-dir').on('click', function() {
        layer.prompt({ title: '新建文件夹', formType: 0, placeholder: '如：入门指南 或 入门指南/子目录' }, function(value, index) {
            $.post('/admin/cmspro/mddoc/directory', { path: value }, function(res) {
                if (res.code === 0) {
                    layer.msg('创建成功', { icon: 1 });
                    loadTree();
                } else {
                    layer.msg(res.message, { icon: 2 });
                }
                layer.close(index);
            });
        });
    });

    // 新建文档
    $('#btn-new-file').on('click', function() {
        var defaultPath = currentDir ? currentDir + '/' : '';
        layer.prompt({
            title: '新建文档',
            formType: 0,
            value: defaultPath,
            placeholder: '如：01-快速开始.md'
        }, function(value, index) {
            if (!value.endsWith('.md')) {
                value += '.md';
            }
            $.post('/admin/cmspro/mddoc/upload', { path: value, content: '# ' + value.replace('.md', '') + '\n\n' }, function(res) {
                if (res.code === 0) {
                    layer.msg('创建成功', { icon: 1 });
                    loadTree();
                    loadFile(value);
                } else {
                    layer.msg(res.message, { icon: 2 });
                }
                layer.close(index);
            });
        });
    });

    // 上传文件
    $('#btn-upload').on('click', function() {
        layer.open({
            type: 1,
            title: '上传MD文件',
            area: ['450px', '250px'],
            content: $('#upload-dialog')
        });
    });

    $('#btn-do-upload').on('click', function() {
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
    $('#btn-save').on('click', function() {
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
        });
    });

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
