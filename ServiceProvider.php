<?php

namespace App\Apps\CmsproMddoc;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('app/Apps/CmsproMddoc/Config/mddoc.php'),
            'apps.cmspro.mddoc'
        );
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->loadViews();
    }

    protected function registerRoutes(): void
    {
        // 后台管理路由
        Route::prefix('admin/cmspro/mddoc')
            ->namespace('App\Apps\CmsproMddoc\Controllers\Admin')
            ->middleware(['web', 'auth:admin'])
            ->group(base_path('app/Apps/CmsproMddoc/Routes/admin.php'));

        // 前端页面路由
        Route::prefix('docs')
            ->middleware(['web'])
            ->group(base_path('app/Apps/CmsproMddoc/Routes/home.php'));

        // 前端 API 路由
        Route::prefix('api/mddoc')
            ->middleware(['throttle:60,1'])
            ->group(base_path('app/Apps/CmsproMddoc/Routes/front-api.php'));
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(
            base_path('app/Apps/CmsproMddoc/Views'),
            'cmspro.mddoc'
        );
    }
}
