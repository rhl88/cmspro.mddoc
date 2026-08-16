<?php

namespace App\Apps\CmsproMddoc\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocService
{
    /**
     * 获取文档存储磁盘
     */
    protected function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('local');
    }

    /**
     * 获取文档根路径
     */
    protected function getBasePath(): string
    {
        return config('apps.cmspro.mddoc.storage_path', 'mddoc');
    }

    /**
     * 扫描目录结构，返回分类树
     */
    public function getStructure(): array
    {
        $disk = $this->getDisk();
        $basePath = $this->getBasePath();

        if (!$disk->exists($basePath)) {
            return ['categories' => []];
        }

        $categories = [];

        // 扫描根目录下的 .md 文件
        $rootFiles = $this->scanMdFiles($disk, $basePath);
        if (!empty($rootFiles)) {
            $fileDetails = array_map(function ($file) use ($disk, $basePath) {
                return $this->buildFileDetail($disk, $basePath, $file);
            }, $rootFiles);

            usort($fileDetails, function ($a, $b) {
                if ($a['file'] === 'README.md') return -1;
                if ($b['file'] === 'README.md') return 1;
                return strcmp($a['file'], $b['file']);
            });

            $categories[] = [
                'id' => 'basic',
                'name' => '基础文档',
                'files' => $fileDetails,
            ];
        }

        // 扫描子目录
        $directories = $this->scanDirectories($disk, $basePath);
        foreach ($directories as $dir) {
            $dirPath = $basePath . '/' . $dir;
            $subFiles = $this->scanMdFiles($disk, $dirPath);

            if (empty($subFiles)) {
                continue;
            }

            $fileDetails = array_map(function ($file) use ($disk, $dirPath, $dir) {
                $detail = $this->buildFileDetail($disk, $dirPath, $file);
                $detail['file'] = $dir . '/' . $file;
                return $detail;
            }, $subFiles);

            usort($fileDetails, function ($a, $b) {
                return strcmp($a['file'], $b['file']);
            });

            $categories[] = [
                'id' => $this->generateCategoryId($dir),
                'name' => $this->formatCategoryName($dir),
                'path' => $dir,
                'files' => $fileDetails,
            ];
        }

        return ['categories' => $categories];
    }

    /**
     * 读取文件内容
     */
    public function getFileContent(string $relativePath): ?string
    {
        $disk = $this->getDisk();
        $fullPath = $this->getBasePath() . '/' . $relativePath;

        // 安全检查：防止路径穿越
        if (Str::contains($relativePath, '..') || Str::startsWith($relativePath, '/')) {
            return null;
        }

        // 仅允许读取 .md 文件
        if (!Str::endsWith($relativePath, '.md')) {
            return null;
        }

        if (!$disk->exists($fullPath)) {
            return null;
        }

        return $disk->get($fullPath);
    }

    /**
     * 获取目录树（后台管理用）
     */
    public function getDirectoryTree(string $path = ''): array
    {
        $disk = $this->getDisk();
        $basePath = $this->getBasePath();
        $scanPath = $path ? $basePath . '/' . $path : $basePath;

        if (!$disk->exists($scanPath)) {
            return [];
        }

        $items = [];
        $allFiles = $disk->files($scanPath);
        $allDirs = $disk->directories($scanPath);

        // 目录
        foreach ($allDirs as $dir) {
            $dirName = basename($dir);
            $relativePath = $path ? $path . '/' . $dirName : $dirName;
            $items[] = [
                'type' => 'directory',
                'name' => $dirName,
                'path' => $relativePath,
                'children' => $this->getDirectoryTree($relativePath),
            ];
        }

        // 文件
        foreach ($allFiles as $file) {
            $fileName = basename($file);
            if (!Str::endsWith($fileName, '.md')) {
                continue;
            }
            $relativePath = $path ? $path . '/' . $fileName : $fileName;
            $items[] = [
                'type' => 'file',
                'name' => $fileName,
                'path' => $relativePath,
            ];
        }

        return $items;
    }

    /**
     * 创建目录
     */
    public function createDirectory(string $path): bool
    {
        if (Str::contains($path, '..') || Str::startsWith($path, '/')) {
            return false;
        }

        $disk = $this->getDisk();
        $fullPath = $this->getBasePath() . '/' . $path;

        if ($disk->exists($fullPath)) {
            return false;
        }

        $disk->makeDirectory($fullPath);
        return true;
    }

    /**
     * 上传文件
     */
    public function uploadFile(string $path, string $content): bool
    {
        if (Str::contains($path, '..') || Str::startsWith($path, '/')) {
            return false;
        }

        $disk = $this->getDisk();
        $fullPath = $this->getBasePath() . '/' . $path;

        $disk->put($fullPath, $content);
        return true;
    }

    /**
     * 删除文件
     */
    public function deleteFile(string $path): bool
    {
        if (Str::contains($path, '..') || Str::startsWith($path, '/')) {
            return false;
        }

        $disk = $this->getDisk();
        $fullPath = $this->getBasePath() . '/' . $path;

        if (!$disk->exists($fullPath)) {
            return false;
        }

        $disk->delete($fullPath);
        return true;
    }

    /**
     * 重命名文件
     */
    public function renameFile(string $oldPath, string $newPath): bool
    {
        if (Str::contains($oldPath, '..') || Str::startsWith($oldPath, '/')) {
            return false;
        }
        if (Str::contains($newPath, '..') || Str::startsWith($newPath, '/')) {
            return false;
        }

        $disk = $this->getDisk();
        $fullOldPath = $this->getBasePath() . '/' . $oldPath;
        $fullNewPath = $this->getBasePath() . '/' . $newPath;

        if (!$disk->exists($fullOldPath) || $disk->exists($fullNewPath)) {
            return false;
        }

        // 读取原文件内容，写入新路径，删除旧文件
        $content = $disk->get($fullOldPath);
        $disk->put($fullNewPath, $content);
        $disk->delete($fullOldPath);
        return true;
    }

    /**
     * 重命名目录（移动目录下所有文件到新目录）
     */
    public function renameDirectory(string $oldPath, string $newPath): bool
    {
        if (Str::contains($oldPath, '..') || Str::startsWith($oldPath, '/')) {
            return false;
        }
        if (Str::contains($newPath, '..') || Str::startsWith($newPath, '/')) {
            return false;
        }

        $disk = $this->getDisk();
        $fullOldPath = $this->getBasePath() . '/' . $oldPath;
        $fullNewPath = $this->getBasePath() . '/' . $newPath;

        if (!$disk->exists($fullOldPath) || $disk->exists($fullNewPath)) {
            return false;
        }

        // 创建新目录
        $disk->makeDirectory($fullNewPath);

        // 移动目录下所有文件
        $allFiles = $disk->allFiles($fullOldPath);
        foreach ($allFiles as $file) {
            $relativeFile = str_replace($fullOldPath . '/', '', $file);
            $content = $disk->get($file);
            $disk->put($fullNewPath . '/' . $relativeFile, $content);
        }

        // 移动子目录
        $allDirs = $disk->allDirectories($fullOldPath);
        foreach ($allDirs as $dir) {
            $relativeDir = str_replace($fullOldPath . '/', '', $dir);
            $disk->makeDirectory($fullNewPath . '/' . $relativeDir);
        }

        // 重新移动文件（确保子目录中的文件也被移动）
        foreach ($allFiles as $file) {
            $relativeFile = str_replace($fullOldPath . '/', '', $file);
            $content = $disk->get($file);
            $disk->put($fullNewPath . '/' . $relativeFile, $content);
        }

        // 删除旧目录
        $disk->deleteDirectory($fullOldPath);
        return true;
    }

    /**
     * 检查目录是否为空
     */
    public function isDirectoryEmpty(string $path): bool
    {
        if (Str::contains($path, '..') || Str::startsWith($path, '/')) {
            return false;
        }

        $disk = $this->getDisk();
        $fullPath = $this->getBasePath() . '/' . $path;

        if (!$disk->exists($fullPath)) {
            return true;
        }

        return empty($disk->files($fullPath)) && empty($disk->directories($fullPath));
    }

    /**
     * 删除目录
     */
    public function deleteDirectory(string $path): bool
    {
        if (Str::contains($path, '..') || Str::startsWith($path, '/')) {
            return false;
        }

        $disk = $this->getDisk();
        $fullPath = $this->getBasePath() . '/' . $path;

        if (!$disk->exists($fullPath)) {
            return false;
        }

        $disk->deleteDirectory($fullPath);
        return true;
    }

    /**
     * 扫描指定目录下的 .md 文件
     */
    protected function scanMdFiles($disk, string $path): array
    {
        $files = $disk->files($path);
        return array_filter(array_map(function ($file) {
            return basename($file);
        }, $files), function ($name) {
            return Str::endsWith($name, '.md');
        });
    }

    /**
     * 扫描指定目录下的子目录
     */
    protected function scanDirectories($disk, string $path): array
    {
        $dirs = $disk->directories($path);
        return array_map(function ($dir) {
            return basename($dir);
        }, $dirs);
    }

    /**
     * 构建文件详情
     */
    protected function buildFileDetail($disk, string $basePath, string $fileName): array
    {
        $title = $this->extractTitle($disk, $basePath . '/' . $fileName);
        return [
            'name' => $title ?: $this->formatFileName($fileName),
            'file' => $fileName,
            'title' => $title,
        ];
    }

    /**
     * 从 MD 文件提取标题
     */
    protected function extractTitle($disk, string $filePath): ?string
    {
        try {
            $content = $disk->get($filePath);
            if (preg_match('/^#\s+(.+)$/m', $content, $match)) {
                return trim($match[1]);
            }
        } catch (\Throwable) {
            // 忽略读取错误
        }
        return null;
    }

    /**
     * 格式化文件名
     */
    protected function formatFileName(string $fileName): string
    {
        $name = str_replace('.md', '', $fileName);
        $name = str_replace(['-', '_'], ' ', $name);
        $name = preg_replace('/^\d+\s*/', '', $name);
        return trim($name) ?: $fileName;
    }

    /**
     * 格式化分类名
     */
    protected function formatCategoryName(string $dirName): string
    {
        $name = str_replace(['-', '_'], ' ', $dirName);
        $name = preg_replace('/^\d+\s*/', '', $name);
        return trim($name) ?: $dirName;
    }

    /**
     * 生成分类 ID
     *
     * 说明：目录名可能为中文，直接用 preg_replace('/[^a-z0-9]/i', ...) 会按字节
     * 将所有中文替换为连字符，导致相同字数的中文目录生成完全相同的 ID，
     * 进而使前端手风琴导航无法区分目录。此处对无法生成有效 ASCII slug 的
     * 目录名（如纯中文）改用 md5 摘要，保证同级目录 ID 唯一且为选择器安全字符。
     */
    protected function generateCategoryId(string $dirName): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($dirName));
        $slug = trim($slug, '-');

        if ($slug === '') {
            return 'cat-' . substr(md5($dirName), 0, 12);
        }

        return $slug;
    }
}
