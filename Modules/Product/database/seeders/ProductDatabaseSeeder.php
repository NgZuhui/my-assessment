<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class ProductDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Category::all()->each(function (Category $category) {
            Product::factory()->count(6)->create(['category_id' => $category->id]);
            Product::factory()->disabled()->count(2)->create(['category_id' => $category->id]);
        });
    }
}
