# CmsPro-Markdown文档-扩展指南

> 版本：1.0.0 | 更新日期：2026-08-13
> 应用版本：1.0.5 | 应用 ID：cmspro.mddoc

---

## 一、概述

本文档面向 CmsPro 应用开发者，说明如何在自定义应用中调用 **CmsproMddoc（Markdown 文档）** 应用的 Service 层方法，实现文档结构扫描、文件读写、目录管理、文件上传等能力。

**核心特性**：
- **文件夹分类**：文档按文件夹（可多级嵌套）组织，根目录文档自动归入「基础文档」分类
- **Markdown 渲染**：基于 marked.js 完整渲染
- **代码高亮**：highlight.js 支持多种语言
- **全文搜索**：前端构建搜索索引，输入即时过滤
- **纯文件系统**：无数据库表，部署简单

---

## 二、前置检查

调用扩展接口前，建议先检查应用是否已安装并启用：

```php
use App\Models\AppModel;

$app = AppModel::where('app_id', 'cmspro.mddoc')->first();
if (!$app || $app->status !== 1) {
    throw new \RuntimeException('Markdown 文档应用未安装或未启用');
}
```

---

## 三、Service 层调用

### 3.1 DocService — 文件系统业务服务

```php
use App\Apps\CmsproMddoc\Services\DocService;
```

| 方法 | 说明 |
|------|------|
| `getStructure(): array` | 扫描目录生成分类树（根目录 `.md` 文件归入「基础文档」，子目录各为一类） |
| `getFileContent(string $relativePath): ?string` | 读取文件内容（带安全校验：拒绝 `..` 路径穿越与绝对路径，仅允许 `.md`） |
| `getDirectoryTree(string $path = ''): array` | 后台目录树（递归目录 + `.md` 文件） |
| `createDirectory(string $path): bool` | 创建目录（支持多级路径） |
| `uploadFile(string $path, string $content): bool` | 写入文件（在线编辑/上传共用，支持覆盖） |
| `deleteFile(string $path): bool` | 删除文件 |
| `renameFile(string $oldPath, string $newPath): bool` | 重命名文件（读-写-删实现） |
| `renameDirectory(string $oldPath, string $newPath): bool` | 重命名目录（递归移动全部文件与子目录后删除旧目录） |
| `isDirectoryEmpty(string $path): bool` | 判断目录是否为空 |
| `deleteDirectory(string $path): bool` | 删除目录（须为空） |

---

## 四、钩子扩展点

本应用未注册钩子（manifest.json 未声明 `hooks` 字段，无 `Hooks.php`），无对外钩子扩展点。

---

## 五、辅助函数

本应用未提供全局辅助函数（`helpers.php` 不存在）。

---

## 六、模型引用

本应用不使用数据库表，无 Eloquent 模型。文档数据全部存储在文件系统（`storage/app/mddoc/`）。

---

## 七、调用示例

### 7.1 通过 app() 容器获取文档结构

```php
use App\Apps\CmsproMddoc\Services\DocService;

$docService = app(DocService::class);
$structure = $docService->getStructure();
```

### 7.2 读取文件内容

```php
use App\Apps\CmsproMddoc\Services\DocService;

$docService = app(DocService::class);
$content = $docService->getFileContent('入门指南/01-快速开始.md');
```

### 7.3 创建文档

```php
use App\Apps\CmsproMddoc\Services\DocService;

$docService = app(DocService::class);
$docService->uploadFile('开发文档/后端/API规范.md', "# API 规范\n\n...内容...");
```

### 7.4 创建目录

```php
use App\Apps\CmsproMddoc\Services\DocService;

$docService = app(DocService::class);
$docService->createDirectory('开发文档/后端');
```

### 7.5 删除文件

```php
use App\Apps\CmsproMddoc\Services\DocService;

$docService = app(DocService::class);
$docService->deleteFile('开发文档/后端/API规范.md');
```

---

## 八、注意事项

1. **前置检查**：调用前务必确认应用已安装并启用（见第二章）。
2. **安全校验**：`getFileContent()` 拒绝 `..` 路径穿越与绝对路径，仅允许 `.md` 文件，调用方无需重复校验。
3. **存储路径**：文档根目录为 `storage/app/mddoc/`（`config('apps.cmspro.mddoc.storage_path')`，默认 `mddoc`）。
4. **目录删除**：`deleteDirectory()` 要求目录为空，删除前需先清空。
5. **文件类型**：仅 `.md` 后缀文件参与前台展示与后台树。
6. **无数据库**：本应用不使用数据库表，文档数据全部在文件系统。

---

## 八、版本与更新日志

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.0.0 | 2026-08-13 | 初始版本，基于应用 v1.0.5 编写 |
