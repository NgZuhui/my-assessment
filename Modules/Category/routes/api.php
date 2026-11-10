<?php

use Illuminate\Support\Facades\Route;
use Modules\Category\Http\Controllers\CategoryController;

Route::middleware(['auth:sanctum', 'admin'])->prefix('v1')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
});
