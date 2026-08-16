<?php

use App\Apps\CmsproMddoc\Controllers\Admin\DocManageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DocManageController::class, 'index']);
Route::get('/tree', [DocManageController::class, 'tree']);
Route::post('/directory', [DocManageController::class, 'createDirectory']);
Route::post('/upload', [DocManageController::class, 'uploadFile']);
Route::post('/upload-file', [DocManageController::class, 'uploadMdFile']);
Route::get('/read', [DocManageController::class, 'readFile']);
Route::post('/rename', [DocManageController::class, 'renameFile']);
Route::post('/rename-directory', [DocManageController::class, 'renameDirectory']);
Route::delete('/file', [DocManageController::class, 'deleteFile']);
Route::delete('/directory', [DocManageController::class, 'deleteDirectory']);
