<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username_and_password(): void
    {
        User::factory()->create([
            'username' => 'owner',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'owner',
            'password' => 'password',
            'device_name' => 'kasir-1',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'owner')
            ->assertJsonPath('data.user.role', 'owner')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_owner_can_create_user(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Kasir 1',
            'username' => 'kasir1',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'kasir',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'kasir1')
            ->assertJsonPath('data.role', 'kasir');
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'username' => 'kasir1',
            'role' => 'kasir',
        ]));

        $response = $this->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'kasir1')
            ->assertJsonPath('data.role', 'kasir');
    }

    public function test_non_owner_cannot_create_user(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Admin Gudang',
            'username' => 'admin_gudang',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin_gudang',
        ]);

        $response->assertForbidden();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'inactive-user',
            'password' => 'password',
            'role' => 'kasir',
            'is_active' => false,
            'is_approved' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'inactive-user',
            'password' => 'password',
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_owner_can_update_user_status(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());
        $user = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
            'is_approved' => true,
        ]);

        $response = $this->patchJson('/api/users/'.$user->id, [
            'is_active' => false,
            'is_approved' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.is_approved', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
            'is_approved' => false,
        ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_products_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertUnauthorized();
    }
}
