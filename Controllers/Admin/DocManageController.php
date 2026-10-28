<?php

namespace App\Apps\CmsproMddoc\Controllers\Admin;

use App\Apps\CmsproMddoc\Services\BrandService;
use App\Apps\CmsproMddoc\Services\DocService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocManageController extends Controller
{
    protected DocService $docService;

    protected BrandService $brandService;

    public function __construct(DocService $docService, BrandService $brandService)
    {
        $this->docService = $docService;
        $this->brandService = $brandService;
    }

    /**
     * 文件管理首页
     */
    public function index()
    {
        return view('cmspro.mddoc::Admin.index');
    }

    /**
     * 应用设置页（前台品牌配置）
     */
    public function settings()
    {
        return view('cmspro.mddoc::Admin.settings', [
            'values' => $this->brandService->values(),
            'hasIcon' => $this->brandService->uploadedIconPath() !== null,
            'navs' => $this->brandService->navs(),
            'defaults' => $this->brandService->resolved(),
        ]);
    }

    /**
     * 保存前台导航菜单（title/url 数组，最多 10 项）
     */
    public function saveNavs(Request $request)
    {
        $request->validate([
            'navs' => 'required|array|max:10',
            'navs.*.title' => 'required|string|max:30',
            'navs.*.url' => 'required|string|max:500',
        ]);

        $navs = [];
        foreach ($request->input('navs', []) as $item) {
            $navs[] = [
                'title' => trim((string) ($item['title'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
            ];
        }

        $this->brandService->saveNavs($navs);

        return response()->json(['code' => 0, 'message' => '菜单保存成功，前台刷新后生效']);
    }

    /**
     * 保存应用设置（前台品牌配置）
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'brand_name' => 'nullable|string|max:50',
            'brand_link' => 'nullable|string|max:500',
        ]);

        $this->brandService->save([
            'name' => (string) $request->input('brand_name', ''),
            'link' => (string) $request->input('brand_link', ''),
        ]);

        return response()->json(['code' => 0, 'message' => '保存成功，前台刷新后生效']);
    }

    /**
     * 上传品牌图标（png/jpg/jpeg/gif/svg/webp，≤2MB）
     */
    public function uploadIcon(Request $request)
    {
        $request->validate([
            'icon' => 'required|file|mimes:png,jpg,jpeg,gif,svg,webp|max:2048',
        ]);

        $fileName = $this->brandService->saveIcon($request->file('icon'));

        return response()->json([
            'code' => 0,
            'message' => '图标上传成功',
            'data' => [
                'file' => $fileName,
                'icon_url' => '/api/mddoc/brand-icon?v=' . time(),
            ],
        ]);
    }

    /**
     * 恢复默认品牌图标（删除上传文件并清空配置）
     */
    public function resetIcon()
    {
        $this->brandService->resetIcon();

        return response()->json(['code' => 0, 'message' => '已恢复默认图标']);
    }

    /**
     * 获取目录树
     */
    public function tree(Request $request)
    {
        $path = $request->input('path', '');
        $tree = $this->docService->getDirectoryTree($path);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $tree,
        ]);
    }

    /**
     * 创建目录
     */
    public function createDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        if (!auth('admin')->user()->hasPermission('cmspro.mddoc.manage')) {
            return response()->json(['code' => 1, 'message' => '无权操作'], 403);
        }

        $result = $this->docService->createDirectory($request->input('path'));

        if ($result) {
            return response()->json(['code' => 0, 'message' => '创建成功']);
        }

        return response()->json(['code' => 1, 'message' => '创建失败，目录可能已存在']);
    }

    /**
     * 上传文件
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'content' => 'required|string',
        ]);

        if (!auth('admin')->user()->hasPermission('cmspro.mddoc.upload')) {
            return response()->json(['code' => 1, 'message' => '无权操作'], 403);
        }

        $result = $this->docService->uploadFile(
            $request->input('path'),
            $request->input('content')
        );

        if ($result) {
            return response()->json(['code' => 0, 'message' => '上传成功']);
        }

        return response()->json(['code' => 1, 'message' => '上传失败']);
    }

    /**
     * 上传 MD 文件（文件上传方式）
     */
    public function uploadMdFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,md,markdown|max:10240',
            'directory' => 'nullable|string',
        ]);

        if (!auth('admin')->user()->hasPermission('cmspro.mddoc.upload')) {
            return response()->json(['code' => 1, 'message' => '无权操作'], 403);
        }

        $file = $request->file('file');
        $directory = $request->input('directory', '');
        $fileName = $file->getClientOriginalName();

        // 确保文件扩展名为 .md
        if (!str_ends_with(strtolower($fileName), '.md')) {
            $fileName .= '.md';
        }

        $path = $directory ? $directory . '/' . $fileName : $fileName;
        $content = file_get_contents($file->getRealPath());

        $result = $this->docService->uploadFile($path, $content);

        if ($result) {
            return response()->json(['code' => 0, 'message' => '上传成功', 'data' => ['path' => $path]]);
        }

        return response()->json(['code' => 1, 'message' => '上传失败']);
    }

    /**
     * 读取文件内容
     */
    public function readFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $content = $this->docService->getFileContent($request->input('path'));

        if ($content === null) {
            return response()->json(['code' => 1, 'message' => '文件不存在'], 404);
        }

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => ['path' => $request->input('path'), 'content' => $content],
        ]);
    }

    /**
     * 重命名文件
     */
    public function renameFile(Request $request)
    {
        $request->validate([
            'old_path' => 'required|string',
            'new_name' => 'required|string',
        ]);

        $oldPath = $request->input('old_path');
        $newName = $request->input('new_name');

        // 确保新文件名以 .md 结尾
        if (!str_ends_with(strtolower($newName), '.md')) {
            $newName .= '.md';
        }

        // 构建新路径（保持原目录结构）
        $dir = dirname($oldPath);
        $newPath = $dir === '.' ? $newName : $dir . '/' . $newName;

        if (!auth('admin')->user()->hasPermission('cmspro.mddoc.manage')) {
            return response()->json(['code' => 1, 'message' => '无权操作'], 403);
        }

        $result = $this->docService->renameFile($oldPath, $newPath);

        if ($result) {
            return response()->json(['code' => 0, 'message' => '重命名成功', 'data' => ['new_path' => $newPath]]);
        }

        return response()->json(['code' => 1, 'message' => '重命名失败，文件可能不存在或目标名称已存在']);
    }

    /**
     * 重命名目录
     */
    public function renameDirectory(Request $request)
    {
        $request->validate([
            'old_path' => 'required|string',
            'new_name' => 'required|string',
        ]);

        $oldPath = $request->input('old_path');
        $newName = $request->input('new_name');

        // 构建新路径（保持原目录层级）
        $dir = dirname($oldPath);
        $newPath = $dir === '.' ? $newName : $dir . '/' . $newName;

        if (!auth('admin')->user()->hasPermission('cmspro.mddoc.manage')) {
            return response()->json(['code' => 1, 'message' => '无权操作'], 403);
        }

        $result = $this->docService->renameDirectory($oldPath, $newPath);

        if ($result) {
            return response()->json(['code' => 0, 'message' => '重命名成功', 'data' => ['new_path' => $newPath]]);
        }

        return response()->json(['code' => 1, 'message' => '重命名失败，目录可能不存在或目标名称已存在']);
    }

    /**
     * 删除文件
     */
    public function deleteFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        if (!auth('admin')->user()->hasPermission('cmspro.mddoc.delete')) {
            return response()->json(['code' => 1, 'message' => '无权操作'], 403);
        }

        $result = $this->docService->deleteFile($request->input('path'));

        if ($result) {
            return response()->json(['code' => 0, 'message' => '删除成功']);
        }

        return response()->json(['code' => 1, 'message' => '删除失败，文件可能不存在']);
    }

    /**
     * 删除目录
     */
    public function deleteDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        if (!auth('admin')->user()->hasPermission('cmspro.mddoc.delete')) {
            return response()->json(['code' => 1, 'message' => '无权操作'], 403);
        }

        // 检查目录是否为空
        if (!$this->docService->isDirectoryEmpty($path)) {
            return response()->json(['code' => 1, 'message' => '目录不为空，请先删除或移走其中的文件']);
        }

        $result = $this->docService->deleteDirectory($path);

        if ($result) {
            return response()->json(['code' => 0, 'message' => '删除成功']);
        }

        return response()->json(['code' => 1, 'message' => '删除失败，目录可能不存在']);
    }
}
