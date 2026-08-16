<?php

namespace App\Apps\CmsproMddoc\Controllers\Home;

use Illuminate\Routing\Controller;

class DocController extends Controller
{
    /**
     * 文档浏览主页
     */
    public function index()
    {
        return view('cmspro.mddoc::Home.index');
    }
}
