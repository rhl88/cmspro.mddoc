# CmsPro-Markdown文档应用文档

> 版本：v1.1.4 | 创建日期：2026-08-12
> 适用系统：CmsPro v5.0.0+
> 应用 ID：`cmspro.mddoc`
> 应用版本：1.0.11
> 依赖应用：无

---

## 一、应用概述

### 1.1 用途与背景

Markdown文档（Mddoc）是一个在线 Markdown 文档预览应用，将 Markdown 文件以文件夹形式组织，在前端以「左侧分类导航 + 中间文档内容 + 右侧目录」的三栏布局展示，支持全文搜索与代码高亮。文档内容存储在服务器本地磁盘（`storage/app/mddoc/`），无需数据库，通过文件系统直接管理，适合团队文档中心、产品帮助文档、开源项目文档站等场景。

### 1.2 核心特性

- **文件夹分类**：文档按文件夹（可多级嵌套）组织，根目录文档自动归入「基础文档」分类
- **目录导航**：左侧手风琴式分类导航；右侧根据文档标题（h1-h6）自动生成「本页目录」（TOC），滚动高亮当前章节
- **Markdown 渲染**：基于 marked.js 完整渲染（标题、表格、代码块、引用、列表、链接、图片等）
- **代码高亮**：highlight.js 支持多种语言（PHP/JavaScript/JSON/bash/css/sql/xml 等），暗色主题（github-dark）；代码块带语言标签、折叠/展开、一键复制
- **全文搜索**：前端构建搜索索引，输入即时过滤匹配的文档（结果按标题/内容匹配排序，支持高亮），支持 `/` 快捷键聚焦搜索框
- **文档下载**：每个文档页提供下载按钮，返回原始 `.md` 文件
- **亮/暗主题**：对标现代文档站的浅色界面 + 暗色模式一键切换，默认跟随系统 `prefers-color-scheme`，用户选择记忆在 localStorage
- **阅读辅助**：内容区顶部面包屑（分类/文档名）、底部上一篇/下一篇导航卡片、移动端抽屉式导航
- **后台可视化编辑**：分屏「源码编辑 + 实时预览」、Markdown 格式工具栏（标题/加粗/斜体/代码/链接/图片/列表/表格/引用/分割线）、Ctrl+S 快捷保存、新建文档对话框（目录选择 + 三种文档模板）
- **后台文件管理**：目录树浏览、新建/重命名/删除目录、新建文档/上传 MD 文件/重命名/删除

### 1.3 适用范围

需要快速搭建静态文档站、帮助中心、团队知识库的场景；无需数据库表，部署简单，运维成本低。

---

## 二、功能说明

### 2.1 后台管理功能

后台菜单结构（`manifest.json` 的 `menus`，入口前缀 `/admin/cmspro/mddoc`，中间件 `web + auth:admin`）：

| 菜单 | 路径 | 功能说明 |
|------|------|----------|
| 文档管理 | `/admin/cmspro/mddoc` | 文件管理首页（目录树 + 文件操作） |
| 应用设置 | `/admin/cmspro/mddoc/settings` | 前台品牌配置（品牌图标/名称/链接） |

后台能力（均为 API，见第四章）：

- **目录树**：按目录层级展示文件夹与 `.md` 文件
- **新建目录**：支持多级路径（如 `开发文档/后端`）
- **新建文档**：对话框式创建，支持选择目标目录（下拉，自动从目录树收集）、填写文件名、选择文档模板（空白文档/标准文档/接口文档，模板自动生成章节骨架与 `Y-m-d` 日期头）
- **可视化编辑**：分屏布局（左侧 Markdown 源码、右侧实时预览，marked 渲染 300ms 防抖）；格式工具栏支持 H1/H2/H3、加粗、斜体、行内代码、代码块、链接、图片、有序/无序列表（多行选中逐行加前缀/递增序号）、表格、引用、分割线；`Ctrl+S`（`Cmd+S`）快捷保存
- **上传 MD 文件**：上传 `.txt/.md/.markdown` 文件（≤10MB），自动补 `.md` 扩展名
- **读取文件**：读取指定路径文件内容（供在线编辑）
- **重命名**：文件/目录重命名（保持原目录层级；文件自动补 `.md` 后缀）
- **删除**：删除文件；删除目录前须保证目录为空
- **应用设置**：独立设置页（`/admin/cmspro/mddoc/settings`），双 Tab 结构：①品牌设置（上传图标文件 PNG/JPG/GIF/SVG/WEBP ≤2MB，未上传使用内置默认图标 + 品牌名称 + 品牌链接，带图标实时预览与恢复默认）；②菜单管理（自由增删改前台顶栏 `header-nav` 导航菜单，最多 10 项，清空保存恢复默认）。保存至 `config_items`（详见 6.1）

### 2.2 前台功能

文档中心（`/docs/`），对标 docs.trae.cn 风格：

- **顶栏**：浅色毛玻璃 sticky 顶栏（品牌区 + 全局搜索 + 导航链接 + 亮/暗主题切换按钮）；暗色主题跟随系统偏好初始化，切换后记忆于 localStorage（键 `mddoc_theme`）
- **左侧导航**：浅色分组导航列表（根目录文件为「基础文档」，子目录各为一类），手风琴展开/收起，当前文档高亮；移动端（≤768px）为抽屉式，点击文档后自动收起
- **文档内容区**：顶部面包屑（分类名 / 文档名）；marked 渲染；代码块自动高亮（带语言标签、折叠/复制按钮）；引用块提示卡片化展示；底部上一篇/下一篇导航卡片（按导航顺序计算）；页面标题同步浏览器标签
- **右侧目录**：「本页目录」按 h1-h6 生成目录树，点击平滑滚动，滚动时高亮当前章节；无标题时显示「无目录」；≤1280px 宽度自动隐藏
- **搜索**：顶栏搜索框（支持 `/` 快捷键聚焦），输入即时过滤，结果列表展示文件名与匹配内容摘要（关键字高亮），点击跳转文档
- **下载**：文档标题栏提供「下载文档」按钮，导出原始 Markdown 文件
- **URL 定位**：`#/doc/{文件路径}` 锚点路由，可分享直达文档
- **缓存优化**：文档结构与搜索索引在前端 5 分钟本地缓存（localStorage，键 `mddoc_structure_cache_v2` / `mddoc_search_cache_v2`；v1 旧键自动清理）

### 2.3 权限说明（manifest.json `permissions`）

| 权限代码 | 权限名称 | 说明 |
|----------|----------|------|
| `cmspro.mddoc.manage` | 文档管理 | 目录创建/重命名（目录相关操作） |
| `cmspro.mddoc.upload` | 上传文档 | 新建/上传文档 |
| `cmspro.mddoc.delete` | 删除文档 | 删除文件/目录 |

> 权限在控制器方法内通过 `auth('admin')->user()->hasPermission(...)` 校验，无权限返回 HTTP 403。

---

## 三、技术架构

### 3.1 技术栈

- **后端**：Laravel（CmsPro v5.0.0+，PHP >= 8.3），无数据库表（纯文件系统）
- **存储**：Laravel `Storage::disk('local')`，根路径 `storage/app/mddoc/`（可通过配置修改）
- **前端**：原生 JavaScript + marked.js（Markdown 渲染）+ highlight.js（代码高亮，github-dark 主题）；前台 API 有 `throttle:60,1` 限流

### 3.2 架构分层

```
app/Apps/CmsproMddoc/
├── ServiceProvider.php       # 服务提供者：合并配置、注册路由/视图
├── Install.php               # 安装/卸载/升级：创建存储目录 + 示例文档
├── Config/mddoc.php          # 应用配置（storage_path）
├── Routes/                   # admin.php / home.php / front-api.php
├── Controllers/
│   ├── Admin/DocManageController.php  # 后台文件管理（页面 + API）
│   ├── Api/DocApiController.php       # 前台公开 API（结构/内容/下载）
│   └── Home/DocController.php         # 文档中心页面
├── Services/DocService.php   # 文件系统业务服务（结构扫描/CRUD/安全校验）
├── Assets/                   # doc.css / doc.js / marked.min.js / highlight.js
└── Views/                    # Admin / Home / layouts 模板
```

### 3.3 路由注册机制

| 路由组 | 前缀 | 中间件 | 说明 |
|--------|------|--------|------|
| 后台页面+API | `/admin/cmspro/mddoc` | `web`, `auth:admin` | 文件管理 |
| 前台页面 | `/docs` | `web` | 文档中心首页 |
| 前台 API | `api/mddoc` | `throttle:60,1` | 目录结构/文件内容/下载 |

> 注意：前台路径固定为 `/docs`（home_menus 中「文档中心」对应 `/docs`），本应用未提供访问模式配置。

### 3.4 存储与数据结构

- 文档根目录：`storage/app/mddoc/`（`config('apps.cmspro.mddoc.storage_path')`，默认 `mddoc`）
- 仅 `.md` 后缀文件参与展示；目录结构由文件系统实时扫描，无数据库表
- 安装时自动创建目录并生成示例文档（`README.md`、`入门指南/01-快速开始.md`、`入门指南/02-文档规范.md`）
- 卸载时**保留** `storage/app/mddoc/` 目录（不删除用户文档数据），仅清缓存

### 3.5 核心服务说明（DocService）

| 方法 | 职责 |
|------|------|
| `getStructure` | 扫描目录生成分类树：根目录 `.md` 文件归入「基础文档」（README.md 排最前），子目录各为一类；文件标题从文档首个 `# 标题` 提取，无标题时格式化文件名展示 |
| `getFileContent` | 读取文件内容（带安全校验：拒绝 `..` 路径穿越与绝对路径，仅允许 `.md`） |
| `getDirectoryTree` | 后台目录树（递归目录 + `.md` 文件） |
| `createDirectory` / `deleteDirectory` | 创建/删除目录（安全校验；删除前可配合 `isDirectoryEmpty`） |
| `uploadFile` | 写入文件（在线编辑/上传共用，支持覆盖） |
| `deleteFile` / `renameFile` | 删除/重命名文件（重命名采用读-写-删实现） |
| `renameDirectory` | 重命名目录（递归移动全部文件与子目录后删除旧目录） |
| `isDirectoryEmpty` | 判断目录是否为空 |
| `generateCategoryId` | 生成分类 ID：ASCII 目录名生成 slug；纯中文等无法生成 slug 的名称用 `cat-{md5 前12位}` 保证唯一（避免手风琴导航冲突） |

**安全设计**：所有涉及路径的操作均校验「不包含 `..`、不以 `/` 开头」，防止路径穿越；读取仅限 `.md` 文件。

### 3.6 前端渲染流程（doc.js）

```
init() → 确定主题（localStorage mddoc_theme → 系统偏好）→ 绑定抽屉/快捷键
→ 清理 v1 旧缓存键 → 读取 localStorage 缓存（5分钟）→ 拉取 /api/mddoc/structure
→ 构建搜索索引 → 渲染左侧分类导航 → 根据 hash 定位文档
点击文档 → 拉取 /api/mddoc/file?path=xxx → marked.parse 渲染
→ 代码块高亮/折叠/复制处理 → 引用块增强 → 生成右侧 TOC → 渲染面包屑与上/下一篇
搜索输入 → 本地索引即时过滤 → 高亮展示匹配结果
```

**双主题机制**：doc.css 以 CSS 变量定义 `:root`（亮色）与 `:root[data-theme="dark"]`（暗色）两套设计令牌；首页 `<head>` 内联脚本在渲染前设置 `data-theme` 防止暗色用户首屏白闪；主题切换按钮只切换该属性并写回 localStorage。代码块两种主题下均使用 github-dark 深色高亮（暗色下以边框区分）。

---

## 四、接口定义

> 统一响应格式：`{"code": 0, "message": "success", "data": ...}`，`code = 0` 成功。

### 4.1 前台公开 API（前缀 `api/mddoc`，限流 60 次/分钟）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/mddoc/structure` | 文档目录结构（分类树：categories[{id, name, path?, files[{name, file, title}]}]） |
| GET | `/api/mddoc/file` | 文件内容（参数：path 必填；返回 `{path, content}`；不存在/越权/非 .md 返回 404） |
| GET | `/api/mddoc/download` | 下载文档（参数：path 必填；返回 `text/markdown` 附件，filename 为文件名） |

### 4.2 后台 API（前缀 `/admin/cmspro/mddoc`，需管理员登录，部分操作校验权限）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/cmspro/mddoc/` | 文件管理页面（HTML） |
| GET | `/admin/cmspro/mddoc/tree` | 目录树（参数：path 可选，递归目录与 .md 文件） |
| POST | `/admin/cmspro/mddoc/directory` | 新建目录（参数：path 必填；需 `cmspro.mddoc.manage` 权限；目录已存在返回失败） |
| POST | `/admin/cmspro/mddoc/upload` | 新建/覆盖文档（参数：path 必填、content 必填；需 `cmspro.mddoc.upload` 权限） |
| POST | `/admin/cmspro/mddoc/upload-file` | 上传 MD 文件（参数：file 必填（mimes:txt,md,markdown，≤10MB）、directory 可选；自动补 .md 后缀；需 `cmspro.mddoc.upload` 权限） |
| GET | `/admin/cmspro/mddoc/read` | 读取文件内容（参数：path 必填；不存在返回 404） |
| POST | `/admin/cmspro/mddoc/rename` | 重命名文件（参数：old_path、new_name；自动补 .md 后缀；需 `cmspro.mddoc.manage` 权限） |
| POST | `/admin/cmspro/mddoc/rename-directory` | 重命名目录（参数：old_path、new_name；需 `cmspro.mddoc.manage` 权限） |
| DELETE | `/admin/cmspro/mddoc/file` | 删除文件（参数：path；需 `cmspro.mddoc.delete` 权限） |
| DELETE | `/admin/cmspro/mddoc/directory` | 删除目录（参数：path；需 `cmspro.mddoc.delete` 权限；目录非空返回失败） |

### 4.3 页面路由

| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|------------|------|
| GET | `/docs/` | DocController@index | 文档中心页面 |
| GET | `/admin/cmspro/mddoc/` | DocManageController@index | 后台文件管理页面 |

### 4.4 接口调用示例

```bash
# 获取文档结构（前台）
curl "http://www.cmspro.com/api/mddoc/structure"

# 获取文件内容
curl "http://www.cmspro.com/api/mddoc/file?path=%E5%85%A5%E9%97%A8%E6%8C%87%E5%8D%97/01-%E5%BF%AB%E9%80%9F%E5%BC%80%E5%A7%8B.md"

# 下载文档
curl -O "http://www.cmspro.com/api/mddoc/download?path=README.md"

# 后台新建目录
curl -X POST "http://www.cmspro.com/admin/cmspro/mddoc/directory" \
  -H "Content-Type: application/json" \
  -d '{"path": "开发文档/后端"}'

# 后台新建文档
curl -X POST "http://www.cmspro.com/admin/cmspro/mddoc/upload" \
  -H "Content-Type: application/json" \
  -d '{"path": "开发文档/后端/API规范.md", "content": "# API 规范\n\n...内容..."}'
```

```json
// structure 响应示例
{
  "code": 0,
  "message": "success",
  "data": {
    "categories": [
      {
        "id": "basic",
        "name": "基础文档",
        "files": [ { "name": "文档中心", "file": "README.md", "title": "文档中心" } ]
      },
      {
        "id": "ru-men-zhi-nan",
        "name": "入门指南",
        "path": "入门指南",
        "files": [ { "name": "快速开始", "file": "入门指南/01-快速开始.md", "title": "快速开始" } ]
      }
    ]
  }
}
```

---

## 五、操作使用指南

### 5.1 环境要求

- CmsPro v5.0.0+，PHP >= 8.3
- 存储磁盘可写（`storage/app/mddoc/`）
- 无需数据库表（应用不使用数据库）

### 5.2 安装部署

1. 通过 CmsPro 后台「应用市场」安装 Markdown文档应用
2. 启用应用（`Install::install` 自动创建 `storage/app/mddoc/` 目录并写入示例文档：README.md、入门指南/01-快速开始.md、入门指南/02-文档规范.md）
3. 前台访问 `/docs/` 即可查看文档中心；后台「文档管理」维护文档
4. 如调整存储路径，修改 `config('apps.cmspro.mddoc.storage_path')`（默认 `mddoc`）

> 卸载应用会清缓存，但**不会删除** `storage/app/mddoc/` 目录（保留用户文档数据）。

### 5.3 日常操作流程

**管理员**：
1. 「文档管理」查看目录树
2. 「新建文件夹」创建分类（支持多级，如 `开发文档/后端`）
3. 「新建文档」打开创建对话框：选择目标目录、填写文件名、选择模板（空白/标准文档/接口文档）一键生成
4. 编辑器中左侧撰写 Markdown，右侧实时预览；使用工具栏快速插入标题/表格/代码块等格式；`Ctrl+S` 快捷保存
5. 需要时上传文件、重命名文件/目录、删除文件（删除目录须先清空）
6. 前台 `/docs/` 验证展示效果（分类、目录导航、搜索、高亮、亮暗主题）

**访客**：
1. 打开 `/docs/`，左侧点击分类展开，点击文档阅读
2. 右侧「本页目录」快速跳转章节；顶部搜索框全文检索文档（`/` 快捷键聚焦）
3. 阅读完通过底部「下一篇」继续浏览；右上角可切换亮/暗主题
4. 需要原始文件时点击文档内「下载文档」按钮

---

## 六、配置参数说明

### 6.1 manifest.json 应用设置（config_groups）

**前台品牌设置**（分组 code：`app_cmspro_mddoc_brand`），在后台「应用管理 → 应用设置」中配置，保存至 `config_items` 表：

| 配置项 | config_items code | 类型 | 默认值 | 说明 |
|--------|-------------------|------|--------|------|
| 品牌图标 | `app_cmspro_mddoc_brand_logo` | text | 空（内置默认 SVG） | 值为上传图标文件名（`brand_icon.{ext}`，存于 `storage/app/mddoc-brand/`），经 `/api/mddoc/brand-icon` 输出；空为默认图标 |
| 品牌名称 | `app_cmspro_mddoc_brand_name` | text | `文档中心` | 前台顶栏品牌显示文字，留空不显示 |
| 品牌链接 | `app_cmspro_mddoc_brand_link` | text | `/` | 点击品牌区域的跳转地址，支持相对路径或完整 URL |
| 前台导航菜单 | `app_cmspro_mddoc_brand_navs` | textarea | 内置默认三项（首页/应用市场/帮助中心） | JSON 数组 `[{title,url}]`，控制前台顶栏导航；空值回退默认 |

品牌配置读写由 `BrandService` 统一提供（`values()` 表单回显 / `resolved()` 前台渲染兜底（含 navs 聚合） / `navs()` 导航读取 / `save()` 名称与链接幂等落盘 / `saveNavs()` 导航保存 / `saveIcon()` 与 `resetIcon()` 图标上传与恢复），前台 `DocController` 与后台设置页共用该服务；未配置/留空/配置表异常时前台回退默认值。

后台设置页（`/admin/cmspro/mddoc/settings`）以「品牌设置 / 菜单管理」双 Tab 提供可视化配置，等价于直接修改上述 `config_items` 值。

### 6.2 Config/mddoc.php 配置

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `storage_path` | `mddoc` | 文档存储根路径（相对于 `storage/app/`），可通过 `config('apps.cmspro.mddoc.storage_path')` 覆盖 |

### 6.3 文档展示规则

| 规则 | 说明 |
|------|------|
| 文件类型 | 仅 `.md` 后缀文件参与前台展示与后台树 |
| 根目录文件 | 自动归入「基础文档」分类，`README.md` 排最前 |
| 子目录 | 每个含 `.md` 文件的子目录一个分类，分类名去除序号前缀与 `-`/`_` |
| 文档标题 | 取文档首个 `# 标题`；无标题时以文件名（去序号、去扩展名）展示 |
| 空目录 | 无 `.md` 文件的目录不显示在前台分类中 |

---

## 七、常见问题解答

### Q: 前台显示「暂无文档」？

1. 确认 `storage/app/mddoc/` 目录存在且含 `.md` 文件
2. 确认目录中只有非 `.md` 文件（前台仅展示 `.md` 文件）
3. 前端结构缓存 5 分钟，新文件上传后可等待或清除浏览器 localStorage 后刷新

### Q: 上传的文档前台不显示？

1. 上传文件后缀须为 `.txt/.md/.markdown`（≤10MB），系统会自动补 `.md` 后缀
2. 确认上传路径正确（`directory` 参数对应的目录）
3. 刷新前台页面（结构缓存 5 分钟）

### Q: 删除目录提示「目录不为空」？

删除目录前必须先删除/移走目录下的所有文件与子目录（`deleteDirectory` 校验目录为空）。需递归删除子目录时请逐级处理。

### Q: 读取/下载文档返回 404？

`getFileContent` 有三重限制：路径含 `..` 或以 `/` 开头（防穿越）、非 `.md` 文件、文件不存在——三者任一命中即返回 404。

### Q: 重命名文件后路径变化？

重命名保持原目录层级（`dirname(old_path) + '/' + new_name`），文件名自动补 `.md` 后缀；重命名后前台显示随文件系统实时更新。

### Q: 如何备份/迁移文档？

文档全部位于 `storage/app/mddoc/`，直接备份/拷贝该目录即可；迁移到新环境时放入相同路径并安装应用（应用不删除该目录数据）。

### Q: 代码高亮支持哪些语言？

Assets 中内置语言：bash、css、javascript、json、php、sql、xml（vue 代码块按 xml 高亮）；如需要其他语言可将对应 highlight.js 语言包加入 `Assets/lib/highlight.js/languages/`。

---

## 更新日志

| 版本 | 日期 | 更新人 | 变更内容 |
|------|------|--------|----------|
| v1.0.0 | 2026-08-12 | 开发团队 | 初始版本 |
| v1.1.0 | 2026-10-05 | 开发团队 | 应用升级至 1.0.7：后台新增可视化编辑器（分屏实时预览 + 格式工具栏 + Ctrl+S 保存）与新建文档对话框（目录选择 + 三种文档模板）；前台对标 docs.trae.cn 重构（浅色顶栏/分组导航/面包屑/上一篇下一篇/移动端抽屉），新增亮/暗双主题；前端缓存键升级 v2；新增后台与前台 API 功能测试（Tests/） |
| v1.1.1 | 2026-10-05 | 开发团队 | 应用升级至 1.0.8：新增应用设置（manifest config_groups「前台品牌设置」），后台可自定义前台顶栏品牌图标（SVG）、品牌名称与品牌链接；前台导航渲染提速（结构加载后立即渲染，搜索索引后台并行构建）；内容区与顶栏解除 880px/1440px 宽度限制 |
| v1.1.2 | 2026-10-05 | 开发团队 | 应用升级至 1.0.9：后台新增「应用设置」菜单与独立设置页（品牌配置表单 + 实时预览 + 恢复默认）；品牌配置读写抽为 `BrandService` 共享服务；新增精致 SVG 应用图标 icon.svg（框架图标接口自动优先展示） |
| v1.1.3 | 2026-10-05 | 开发团队 | 应用升级至 1.0.10：品牌图标改为文件上传方式（PNG/JPG/GIF/SVG/WEBP ≤2MB，存 `storage/app/mddoc-brand/`，经 `/api/mddoc/brand-icon` 输出），未上传时回退内置默认图标；设置页支持上传与恢复默认 |
| v1.1.4 | 2026-10-05 | 开发团队 | 应用升级至 1.0.11：后台编辑器分屏滚动同步（左编辑器与右预览按比例双向跟随）；应用设置页改双 Tab（品牌设置 / 菜单管理），菜单管理支持自由增删改前台顶栏导航（`config_items` JSON 存储，≤10 项，空值恢复默认三项） |
