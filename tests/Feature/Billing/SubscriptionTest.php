<?php

namespace Tests\Feature\Billing;

use App\Billing\Plans;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'plan' => Plans::FREE,
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Owner',
            'email'     => 'owner@acme.com',
            'password'  => 'password123',
        ]);

        setPermissionsTeamId($this->tenant->id);
        $this->owner->assignRole('owner');
    }

    public function test_billing_index_returns_plan_and_usage(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/billing');

        $response->assertOk()
            ->assertJsonPath('data.plan', Plans::FREE)
            ->assertJsonStructure([
                'data' => [
                    'plan',
                    'subscription',
                    'usage' => [
                        'plan',
                        'workflows',
                        'executions_per_month',
                    ],
                    'plans',
                ],
            ]);
    }

    public function test_free_plan_limits_are_correct(): void
    {
        $limits = Plans::limits(Plans::FREE);

        $this->assertEquals(3, $limits['workflows']);
        $this->assertEquals(100, $limits['executions_per_month']);
        $this->assertEquals(20, $limits['ai_steps_per_month']);
    }

    public function test_pro_plan_limits_are_correct(): void
    {
        $limits = Plans::limits(Plans::PRO);

        $this->assertEquals(25, $limits['workflows']);
        $this->assertEquals(5000, $limits['executions_per_month']);
    }

    public function test_enterprise_plan_has_unlimited_limits(): void
    {
        $limits = Plans::limits(Plans::ENTERPRISE);

        $this->assertEquals(-1, $limits['workflows']);
        $this->assertEquals(-1, $limits['executions_per_month']);
    }

    public function test_unauthenticated_user_cannot_access_billing(): void
    {
        $this->getJson('/api/v1/billing')->assertUnauthorized();
    }
}
