<?php

namespace App\Apps\CmsproMddoc;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Install
{
    /**
     * 安装时执行
     */
    public function install(): void
    {
        $this->ensureStorageDirectory();
        $this->createSampleDocs();
    }

    /**
     * 卸载时执行
     */
    public function uninstall(): void
    {
        Cache::flush();
        // 注意：不删除 storage/app/mddoc/ 目录，保留用户文档数据
    }

    /**
     * 升级时执行
     */
    public function upgrade(string $fromVersion, string $toVersion): void
    {
        $this->ensureStorageDirectory();
    }

    /**
     * 确保存储目录存在
     */
    protected function ensureStorageDirectory(): void
    {
        $disk = Storage::disk('local');
        $basePath = config('apps.cmspro.mddoc.storage_path', 'mddoc');

        if (!$disk->exists($basePath)) {
            $disk->makeDirectory($basePath);
        }
    }

    /**
     * 创建示例文档
     */
    protected function createSampleDocs(): void
    {
        $disk = Storage::disk('local');
        $basePath = config('apps.cmspro.mddoc.storage_path', 'mddoc');

        // 仅在目录为空时创建示例文档
        if ($disk->exists($basePath . '/README.md')) {
            return;
        }

        $disk->put($basePath . '/README.md', <<<MD
# 文档中心

欢迎使用 MD 文档预览系统。

## 功能特性

- **文件夹分类**：按文件夹组织文档，左侧目录导航
- **Markdown 渲染**：支持完整的 Markdown 语法
- **代码高亮**：支持多种编程语言语法高亮
- **全文搜索**：快速搜索文档内容
- **目录导航**：右侧自动生成文档目录

## 快速开始

1. 在后台管理中创建文件夹
2. 上传或新建 Markdown 文件
3. 前端自动展示文档内容

## Markdown 语法示例

### 代码块

```php
echo "Hello, World!";
```

### 表格

| 功能 | 说明 |
|------|------|
| 文件夹 | 按目录分类管理文档 |
| 搜索 | 全文搜索文档内容 |
| 高亮 | 代码语法高亮 |

### 列表

- 无序列表项 1
- 无序列表项 2
- 无序列表项 3

> 这是一段引用文字，用于说明重要信息。
MD
        );

        // 创建示例子目录
        $disk->makeDirectory($basePath . '/入门指南');

        $disk->put($basePath . '/入门指南/01-快速开始.md', <<<MD
# 快速开始

本文档将帮助你快速上手 MD 文档预览系统。

## 第一步：创建文件夹

在后台管理页面，点击「新建文件夹」按钮，输入文件夹名称，如"开发文档"。

## 第二步：上传文档

进入文件夹后，点击「上传文件」或「新建文档」按钮，添加 Markdown 文件。

## 第三步：预览文档

在前端文档中心页面，左侧导航会自动显示文件夹和文档列表，点击即可预览。
MD
        );

        $disk->put($basePath . '/入门指南/02-文档规范.md', <<<MD
# 文档规范

## 文件命名

- 使用有意义的文件名，如 `01-快速开始.md`
- 文件名支持中文、英文、数字和连字符
- 文件扩展名必须为 `.md`

## 文件夹组织

- 按主题或模块创建文件夹
- 文件夹名称应简洁明了
- 支持多级目录嵌套

## Markdown 语法

文档支持标准 Markdown 语法，包括：

- 标题（h1-h6）
- 段落和换行
- 粗体和斜体
- 列表（有序和无序）
- 代码块和行内代码
- 表格
- 引用
- 链接和图片
- 分割线
MD
        );
    }
}
