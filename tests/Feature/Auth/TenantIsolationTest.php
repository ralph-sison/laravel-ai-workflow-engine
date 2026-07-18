<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_from_different_tenants_cannot_see_each_other(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'User A',
            'email' => 'user-a@example.com',
            'password' => 'password123',
        ]);

        User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'User B',
            'email' => 'user-b@example.com',
            'password' => 'password123',
        ]);

        // User A's tenant should only have 1 user
        $this->assertEquals(1, $tenantA->users()->count());
        $this->assertEquals(1, $tenantB->users()->count());
    }

    public function test_tenant_slug_is_unique(): void
    {
        Tenant::create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        $slug = Tenant::generateSlug('Acme Corp');

        $this->assertNotEquals('acme-corp', $slug);
        $this->assertStringStartsWith('acme-corp', $slug);
    }
}
