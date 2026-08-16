<?php

namespace App\Apps\CmsproMddoc\Controllers\Api;

use App\Apps\CmsproMddoc\Services\DocService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class DocApiController extends Controller
{
    protected DocService $docService;

    public function __construct(DocService $docService)
    {
        $this->docService = $docService;
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
}
