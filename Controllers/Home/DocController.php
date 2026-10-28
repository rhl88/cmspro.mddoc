<?php

namespace App\Apps\CmsproMddoc\Controllers\Home;

use App\Apps\CmsproMddoc\Services\BrandService;
use Illuminate\Routing\Controller;

class DocController extends Controller
{
    public function __construct(protected BrandService $brandService)
    {
    }

    /**
     * 文档浏览主页
     */
    public function index()
    {
        return view('cmspro.mddoc::Home.index', [
            'brand' => $this->brandService->resolved(),
        ]);
    }
}
