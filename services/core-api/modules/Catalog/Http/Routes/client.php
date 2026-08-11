<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\CategoryController;

Route::get('/categories', [CategoryController::class, 'index'])->middleware('throttle:client-read');
