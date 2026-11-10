<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\ProductController;

Route::middleware(['auth:sanctum', 'admin'])->prefix('v1')->group(function () {
    // Must be registered before the resource so "bulk-delete" is not treated as {product}.
    Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk-delete');
    Route::apiResource('products', ProductController::class);
});
