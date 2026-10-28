<?php

namespace App\Apps\CmsproMddoc\Controllers\Api;

use App\Apps\CmsproMddoc\Services\BrandService;
use App\Apps\CmsproMddoc\Services\DocService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class DocApiController extends Controller
{
    protected DocService $docService;

    protected BrandService $brandService;

    public function __construct(DocService $docService, BrandService $brandService)
    {
        $this->docService = $docService;
        $this->brandService = $brandService;
    }

    /**
     * 获取文档目录结构
     */
    public function structure()
    {
        $structure = $this->docService->getStructure();

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $structure,
        ]);
    }

    /**
     * 获取文件内容
     */
    public function file(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        $content = $this->docService->getFileContent($path);

        if ($content === null) {
            return response()->json([
                'code' => 1,
                'message' => '文件不存在或无法读取',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'path' => $path,
                'content' => $content,
            ],
        ]);
    }

    /**
     * 下载文档（返回原始 MD 文件）
     */
    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        $content = $this->docService->getFileContent($path);

        if ($content === null) {
            return response()->json([
                'code' => 1,
                'message' => '文件不存在或无法读取',
                'data' => null,
            ], 404);
        }

        $fileName = basename($path);

        return response($content)
            ->header('Content-Type', 'text/markdown; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * 输出上传的品牌图标文件（未上传时 404，前台应回退内置默认图标）
     */
    public function brandIcon()
    {
        $path = $this->brandService->uploadedIconPath();

        if ($path === null) {
            return response()->json([
                'code' => 1,
                'message' => '未上传品牌图标',
                'data' => null,
            ], 404);
        }

        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
        ];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return response()->file($path, [
            'Content-Type' => $mimeMap[$ext] ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
