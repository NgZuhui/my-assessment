<?php

namespace Modules\Product\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_a_category_and_category_has_many_products(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($product->category->is($category));
        $this->assertTrue($category->products->contains($product));
    }

    public function test_filter_scope_combines_category_status_and_search(): void
    {
        $a = Category::factory()->create();
        $b = Category::factory()->create();
        Product::factory()->create(['category_id' => $a->id, 'name' => 'Red Shirt']);
        Product::factory()->disabled()->create(['category_id' => $a->id, 'name' => 'Blue Shirt']);
        Product::factory()->create(['category_id' => $b->id, 'name' => 'Red Hat']);

        $this->assertSame(2, Product::filter(['category_id' => $a->id])->count());
        $this->assertSame(1, Product::filter(['category_id' => $a->id, 'status' => 'disabled'])->count());
        $this->assertSame(2, Product::filter(['search' => 'Red'])->count());
        $this->assertSame(3, Product::filter([])->count());
    }

    public function test_casts_and_soft_deletes(): void
    {
        $product = Product::factory()->create(['price' => 9.5, 'enabled' => 1]);

        $this->assertSame('9.50', $product->price);
        $this->assertTrue($product->enabled);

        $product->delete();
        $this->assertSoftDeleted($product);
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }
}
