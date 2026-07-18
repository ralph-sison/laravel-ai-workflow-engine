<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_creates_tenant(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ralph Sison',
            'email' => 'ralph@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization' => 'Acme Corp',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'tenant_id'],
                    'tenant' => ['id', 'name', 'slug'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('tenants', ['name' => 'Acme Corp']);
        $this->assertDatabaseHas('users', ['email' => 'ralph@example.com']);

        $user = User::where('email', 'ralph@example.com')->first();
        $this->assertNotNull($user->tenant_id);
    }

    public function test_registration_requires_all_fields(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'organization']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $tenant = Tenant::create(['name' => 'Existing', 'slug' => 'existing']);
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Existing User',
            'email' => 'ralph@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ralph',
            'email' => 'ralph@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization' => 'New Org',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registered_user_gets_owner_role(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ralph Sison',
            'email' => 'ralph@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization' => 'Acme Corp',
        ])->assertStatus(201);

        $user = User::where('email', 'ralph@example.com')->first();
        setPermissionsTeamId($user->tenant_id);
        $this->assertTrue($user->hasRole('owner'));
    }
}
