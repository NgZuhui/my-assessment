<?php

namespace Modules\Product\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Wireless Mouse',
            'category_id' => Category::factory()->create()->id,
            'description' => 'Ergonomic',
            'price' => 19.99,
            'stock' => 10,
            'enabled' => true,
        ], $overrides);
    }

    // ---- Authentication & authorization ----

    public function test_guests_cannot_access_the_api(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
        $this->postJson('/api/v1/products', [])->assertUnauthorized();
        $this->postJson('/api/v1/products/bulk-delete', [])->assertUnauthorized();
    }

    public function test_non_admin_users_are_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/products')->assertForbidden();
        $this->postJson('/api/v1/products', $this->payload())->assertForbidden();
    }

    // ---- List ----

    public function test_list_is_paginated_and_shows_category_id_and_name(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Electronics']);
        Product::factory()->count(20)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/products?per_page=5')->assertOk();

        $response->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('data.0.category.id', $category->id)
            ->assertJsonPath('data.0.category.name', 'Electronics')
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'price', 'stock', 'enabled', 'category' => ['id', 'name']]], 'links', 'meta']);
    }

    public function test_list_can_be_filtered_by_category(): void
    {
        $this->actingAsAdmin();
        $a = Category::factory()->create();
        $b = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $a->id]);
        Product::factory()->count(3)->create(['category_id' => $b->id]);

        $this->getJson("/api/v1/products?category_id={$b->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['category' => ['id' => $a->id, 'name' => $a->name]]);
    }

    public function test_list_can_be_filtered_by_status(): void
    {
        $this->actingAsAdmin();
        Product::factory()->count(2)->create();
        Product::factory()->disabled()->count(3)->create();

        $this->getJson('/api/v1/products?status=enabled')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/products?status=disabled')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_list_rejects_invalid_filters(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/products?category_id=999&status=bogus&per_page=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'status', 'per_page']);
    }

    public function test_list_excludes_soft_deleted_products(): void
    {
        $this->actingAsAdmin();
        Product::factory()->create()->delete();
        Product::factory()->create();

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data');
    }

    // ---- Create ----

    public function test_can_create_a_product(): void
    {
        $this->actingAsAdmin();
        $payload = $this->payload();

        $this->postJson('/api/v1/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Wireless Mouse')
            ->assertJsonPath('data.category.id', $payload['category_id']);

        $this->assertDatabaseHas('products', ['name' => 'Wireless Mouse', 'stock' => 10]);
    }

    public function test_create_validates_input(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/products', [
            'category_id' => 999,
            'price' => -1,
            'stock' => 'abc',
            'enabled' => 'maybe',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'category_id', 'price', 'stock', 'enabled']);
    }

    // ---- Show ----

    public function test_can_show_a_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.category.name', $product->category->name);
    }

    public function test_show_returns_404_for_missing_or_deleted_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        $product->delete();

        $this->getJson("/api/v1/products/{$product->id}")->assertNotFound();
        $this->getJson('/api/v1/products/9999')->assertNotFound();
    }

    // ---- Update ----

    public function test_can_update_a_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}", $this->payload(['name' => 'Renamed', 'enabled' => false]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.enabled', false);
    }

    public function test_can_partially_update_with_patch(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['stock' => 5]);

        $this->patchJson("/api/v1/products/{$product->id}", ['stock' => 50])
            ->assertOk()
            ->assertJsonPath('data.stock', 50)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_update_validates_input(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $this->patchJson("/api/v1/products/{$product->id}", ['price' => 'free'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    // ---- Delete ----

    public function test_delete_is_a_soft_delete(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/products/{$product->id}")->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_can_bulk_delete_products(): void
    {
        $this->actingAsAdmin();
        $products = Product::factory()->count(3)->create();
        $keep = Product::factory()->create();

        $this->postJson('/api/v1/products/bulk-delete', ['ids' => $products->pluck('id')->all()])
            ->assertOk()
            ->assertJsonPath('deleted', 3);

        foreach ($products as $product) {
            $this->assertSoftDeleted('products', ['id' => $product->id]);
        }
        $this->assertNotSoftDeleted('products', ['id' => $keep->id]);
    }

    public function test_bulk_delete_validates_ids(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/products/bulk-delete', [])->assertUnprocessable()->assertJsonValidationErrors(['ids']);
        $this->postJson('/api/v1/products/bulk-delete', ['ids' => [9999]])->assertUnprocessable()->assertJsonValidationErrors(['ids.0']);
    }
}
