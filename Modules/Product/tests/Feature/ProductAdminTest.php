<?php

namespace Modules\Product\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Category\Models\Category;
use Modules\Product\Exports\ProductsExport;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/products')->assertRedirect('/login');
    }

    public function test_non_admins_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/products')->assertForbidden();
    }

    public function test_index_lists_products_with_pagination_and_status_filter(): void
    {
        Product::factory()->count(12)->create();
        Product::factory()->disabled()->create(['name' => 'Hidden Gadget']);

        $this->actingAs($this->admin())->get('/admin/products')
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->perPage() === 10 && $p->total() === 13);

        $this->actingAs($this->admin())->get('/admin/products?status=disabled')
            ->assertOk()
            ->assertSee('Hidden Gadget')
            ->assertViewHas('products', fn ($p) => $p->total() === 1);
    }

    public function test_admin_can_create_a_product(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())->post('/admin/products', [
            'name' => 'Desk Lamp',
            'category_id' => $category->id,
            'description' => 'LED',
            'price' => '25.50',
            'stock' => 7,
            'enabled' => 1,
        ])->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', ['name' => 'Desk Lamp', 'category_id' => $category->id]);
    }

    public function test_create_shows_validation_errors(): void
    {
        $this->actingAs($this->admin())->from('/admin/products/create')
            ->post('/admin/products', [])
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors(['name', 'category_id', 'price', 'stock']);
    }

    public function test_admin_can_update_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->get("/admin/products/{$product->id}/edit")->assertOk()->assertSee($product->name);

        $this->actingAs($this->admin())->put("/admin/products/{$product->id}", [
            'name' => 'Updated',
            'category_id' => $product->category_id,
            'price' => 5,
            'stock' => 1,
            'enabled' => 0,
        ])->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated', 'enabled' => 0]);
    }

    public function test_admin_can_soft_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->delete("/admin/products/{$product->id}")->assertRedirect('/admin/products');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_bulk_delete_products(): void
    {
        $products = Product::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->delete('/admin/products/bulk', ['ids' => $products->pluck('id')->all()])
            ->assertRedirect('/admin/products');

        $this->assertSame(0, Product::count());
        $this->assertSame(3, Product::onlyTrashed()->count());
    }

    public function test_bulk_delete_requires_ids(): void
    {
        $this->actingAs($this->admin())->from('/admin/products')
            ->delete('/admin/products/bulk', [])
            ->assertSessionHasErrors('ids');
    }

    public function test_admin_can_export_products_to_excel(): void
    {
        Excel::fake();
        Product::factory()->count(2)->create();

        $this->actingAs($this->admin())->get('/admin/products/export?status=enabled')->assertOk();

        Excel::assertDownloaded('products.xlsx', fn (ProductsExport $export) => $export->query()->count() === 2);
    }

    public function test_export_contains_headings_and_category_name(): void
    {
        $product = Product::factory()->create();
        $export = new ProductsExport;

        $this->assertContains('Category', $export->headings());
        $this->assertSame($product->category->name, $export->map($product)[2]);
    }
}
