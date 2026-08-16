<?php

use App\Apps\CmsproMddoc\Controllers\Home\DocController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DocController::class, 'index']);
