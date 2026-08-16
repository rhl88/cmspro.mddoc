<?php

namespace App\Apps\CmsproMddoc\Controllers\Admin;

use App\Apps\CmsproMddoc\Services\DocService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocManageController extends Controller
{
    protected DocService $docService;

    public function __construct(DocService $docService)
    {
        $this->docService = $docService;
    }

    /**
     * 文件管理首页
     */
    public function index()
    {
        return view('cmspro.mddoc::Admin.index');
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
