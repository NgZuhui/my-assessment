<?php

namespace Modules\Category\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Category\Models\Category;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_categories(): void
    {
        Category::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    public function test_guests_cannot_list_categories(): void
    {
        $this->getJson('/api/v1/categories')->assertUnauthorized();
    }
}
