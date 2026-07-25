<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['name' => 'Test Corp', 'slug' => 'test-corp']);
        $this->user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ralph Sison',
            'email' => 'ralph@example.com',
            'password' => 'password123',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ralph@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonStructure([
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ralph@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'ralph@example.com');
    }

    public function test_unauthenticated_user_cannot_fetch_profile(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);
    }
}
