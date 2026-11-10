<?php

namespace Modules\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Models\Category;

class CategoryDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
