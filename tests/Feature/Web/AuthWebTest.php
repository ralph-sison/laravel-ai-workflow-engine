<?php

namespace Tests\Feature\Web;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_login_page_renders_inertia_component(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_register_page_renders_inertia_component(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Register'));
    }

    public function test_user_can_login_via_web(): void
    {
        $this->withoutCsrf();

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
        $user   = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ralph',
            'email'     => 'ralph@acme.com',
            'password'  => 'password123',
        ]);
        setPermissionsTeamId($tenant->id);
        $user->assignRole('owner');

        $this->post('/login', ['email' => 'ralph@acme.com', 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->withoutCsrf();

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
        User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ralph',
            'email'     => 'ralph@acme.com',
            'password'  => 'correct-password',
        ]);

        $this->post('/login', ['email' => 'ralph@acme.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_register_via_web(): void
    {
        $this->withoutCsrf();

        $this->post('/register', [
            'tenant_name'           => 'New Corp',
            'name'                  => 'Ralph',
            'email'                 => 'ralph@newcorp.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('tenants', ['name' => 'New Corp']);
        $this->assertDatabaseHas('users', ['email' => 'ralph@newcorp.com']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->withoutCsrf();

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
        $user   = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ralph',
            'email'     => 'ralph@acme.com',
            'password'  => 'password123',
        ]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
