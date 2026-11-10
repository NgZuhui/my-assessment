<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\WebAuthController;

Route::middleware('guest')->group(function () {
    Route::get('login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [WebAuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::post('logout', [WebAuthController::class, 'logout'])->middleware('auth')->name('logout');
