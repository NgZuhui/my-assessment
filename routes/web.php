<?php

use Illuminate\Support\Facades\Route;

// Feature routes live in their modules: Modules/Auth, Modules/Product, Modules/Category.
Route::redirect('/', '/admin/products');
