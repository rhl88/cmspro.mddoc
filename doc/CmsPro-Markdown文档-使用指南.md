# CmsPro-Markdown文档-使用指南

> 版本：1.0.0 | 更新日期：2026-08-13
> 应用版本：1.0.5 | 应用 ID：cmspro.mddoc

---

## 一、环境要求

| 项目 | 要求 |
|------|------|
| PHP | >= 8.3 |
| CmsPro | >= 5.0.0 |
| 存储 | `storage/app/mddoc/` 目录可写 |
| 数据库 | 无需数据库表（应用不使用数据库） |

> 本应用无其他 CmsPro 应用依赖。

---

## 二、安装

### 2.1 安装应用

在 CmsPro 后台「应用管理」中安装本应用，或通过 Artisan 命令：

```bash
cd /path/to/cmspro/code
php artisan app:install cmspro.mddoc
```

安装过程自动创建 `storage/app/mddoc/` 目录并写入示例文档（README.md、入门指南/01-快速开始.md、入门指南/02-文档规范.md）。

### 2.2 启用应用

在后台「应用 → 已装应用」中找到「Markdown 文档」，点击「启用」。

### 2.3 权限配置

应用声明以下权限，需在角色管理中分配给管理员：

| 权限码 | 名称 |
|--------|------|
| `cmspro.mddoc.manage` | 文档管理 |
| `cmspro.mddoc.upload` | 上传文档 |
| `cmspro.mddoc.delete` | 删除文档 |

---

## 三、功能验证

### 3.1 后台功能验证

后台菜单入口「文档管理」（路径 `/admin/cmspro/mddoc`），支持：

- **目录树**：按目录层级展示文件夹与 `.md` 文件
- **新建目录**：支持多级路径（如 `开发文档/后端`）
- **新建文档**：指定路径 + 内容在线创建/覆盖 Markdown 文件
- **上传 MD 文件**：上传 `.txt/.md/.markdown` 文件（≤10MB），自动补 `.md` 扩展名
- **读取文件**：读取指定路径文件内容（供在线编辑）
- **重命名**：文件/目录重命名（保持原目录层级）
- **删除**：删除文件；删除目录前须保证目录为空

### 3.2 前端功能验证

文档中心（`/docs/`）：

- **左侧导航**：分类列表（根目录文件为「基础文档」，子目录各为一类），手风琴展开/收起
- **文档内容区**：marked 渲染；代码块自动高亮（带语言标签、折叠/复制按钮）
- **右侧目录**：按 h1-h6 生成目录树，滚动高亮当前章节
- **搜索**：顶部搜索框，输入即时过滤
- **下载**：文档标题栏提供「下载」按钮，导出原始 Markdown 文件
- **URL 定位**：`#/doc/{文件路径}` 锚点路由，可分享直达文档

### 3.3 前台 API 验证

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/mddoc/structure` | 文档目录结构 |
| GET | `/api/mddoc/file` | 文件内容（参数：path） |
| GET | `/api/mddoc/download` | 下载文档（参数：path） |

---

## 四、卸载验证

1. 在后台「应用 → 已装应用」中找到「Markdown 文档」，点击「卸载」。
2. 系统自动清理菜单/配置/权限。
3. 卸载后验证：后台菜单消失、`/docs/` 与 `/api/mddoc/*` 返回 404。

> 卸载会清缓存，但**不会删除** `storage/app/mddoc/` 目录（保留用户文档数据）。

---

## 五、目录结构

```
CmsproMddoc/
├── Config/mddoc.php              # 应用配置（storage_path）
├── Controllers/
│   ├── Admin/DocManageController.php  # 后台文件管理
│   ├── Api/DocApiController.php       # 前台公开 API
│   └── Home/DocController.php         # 文档中心页面
├── Routes/                       # admin.php / home.php / front-api.php
├── Services/DocService.php       # 文件系统业务服务
├── Assets/                       # doc.css / doc.js / marked.min.js / highlight.js
├── Views/                        # Admin / Home / layouts 模板
├── Install.php                   # 安装/卸载/升级
├── ServiceProvider.php           # 服务提供者
└── manifest.json                 # 应用清单
```

---

## 六、常见问题

### Q1：前台显示「暂无文档」？

- 确认 `storage/app/mddoc/` 目录存在且含 `.md` 文件
- 确认目录中只有非 `.md` 文件（前台仅展示 `.md` 文件）
- 前端结构缓存 5 分钟，新文件上传后可等待或清除浏览器 localStorage 后刷新

### Q2：上传的文档前台不显示？

- 上传文件后缀须为 `.txt/.md/.markdown`（≤10MB），系统会自动补 `.md` 后缀
- 确认上传路径正确（`directory` 参数对应的目录）
- 刷新前台页面（结构缓存 5 分钟）

### Q3：删除目录提示「目录不为空」？

- 删除目录前必须先删除/移走目录下的所有文件与子目录（`deleteDirectory` 校验目录为空）

### Q4：读取/下载文档返回 404？

- `getFileContent` 有三重限制：路径含 `..` 或以 `/` 开头（防穿越）、非 `.md` 文件、文件不存在——三者任一命中即返回 404

### Q5：如何备份/迁移文档？

- 文档全部位于 `storage/app/mddoc/`，直接备份/拷贝该目录即可；迁移到新环境时放入相同路径并安装应用（应用不删除该目录数据）

---

## 七、版本历史

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.0.0 | 2026-08-13 | 初始版本，基于应用 v1.0.5 编写 |
