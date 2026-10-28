<?php

use App\Apps\CmsproMddoc\Controllers\Api\DocApiController;
use Illuminate\Support\Facades\Route;

Route::get('/structure', [DocApiController::class, 'structure']);
Route::get('/file', [DocApiController::class, 'file']);
Route::get('/download', [DocApiController::class, 'download']);
Route::get('/brand-icon', [DocApiController::class, 'brandIcon']);
