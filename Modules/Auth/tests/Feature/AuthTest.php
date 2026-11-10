<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_an_api_token_and_use_it(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']])
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('id', $admin->id);
        $this->withToken($token)->getJson('/api/v1/products')->assertOk();
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'admin@example.com', 'password' => 'nope'])
            ->assertUnprocessable();
    }

    public function test_non_admin_cannot_obtain_a_token(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'user@example.com', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_logout_revokes_the_token(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'admin@example.com', 'password' => 'password'])->json('token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_log_in_to_the_dashboard(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect('/admin/products');
        $this->assertAuthenticated();
    }

    public function test_non_admin_cannot_log_in_to_the_dashboard(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->post('/login', ['email' => 'user@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_log_out(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
