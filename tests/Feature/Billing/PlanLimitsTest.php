<?php

namespace Tests\Feature\Billing;

use App\Billing\Plans;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use App\Services\UsageService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Tenant $tenant;
    private Workflow $workflow;

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

        $this->workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Test Workflow',
            'status'     => 'active',
        ]);
    }

    public function test_execution_allowed_when_under_limit(): void
    {
        Queue::fake();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/execute")
            ->assertStatus(201);
    }

    public function test_execution_blocked_when_monthly_limit_reached(): void
    {
        Queue::fake();

        // Exhaust the free plan limit (100 executions)
        $limits = Plans::limits(Plans::FREE);
        for ($i = 0; $i < $limits['executions_per_month']; $i++) {
            $this->workflow->executions()->create([
                'trigger_type' => 'manual',
                'triggered_by' => $this->owner->id,
                'status'       => 'success',
                'started_at'   => now(),
                'finished_at'  => now(),
            ]);
        }

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/execute")
            ->assertStatus(402)
            ->assertJsonPath('plan', Plans::FREE);
    }

    public function test_pro_tenant_has_higher_execution_limit(): void
    {
        $this->tenant->update(['plan' => Plans::PRO]);

        $usage = app(UsageService::class);
        $this->assertTrue($usage->canExecute($this->tenant));
    }

    public function test_enterprise_tenant_always_can_execute(): void
    {
        $this->tenant->update(['plan' => Plans::ENTERPRISE]);

        // Add more executions than any paid limit
        for ($i = 0; $i < 10000; $i++) {
            $this->workflow->executions()->create([
                'trigger_type' => 'manual',
                'triggered_by' => $this->owner->id,
                'status'       => 'success',
                'started_at'   => now(),
                'finished_at'  => now(),
            ]);
        }

        $usage = app(UsageService::class);
        $this->assertTrue($usage->canExecute($this->tenant));
    }

    public function test_usage_service_counts_current_month_only(): void
    {
        // Use raw insert so created_at is genuinely last month
        // (Eloquent ignores created_at overrides when timestamps are managed automatically)
        \Illuminate\Support\Facades\DB::table('executions')->insert([
            'id'           => \Illuminate\Support\Str::uuid(),
            'workflow_id'  => $this->workflow->id,
            'trigger_type' => 'manual',
            'triggered_by' => $this->owner->id,
            'status'       => 'success',
            'started_at'   => now()->subMonth(),
            'finished_at'  => now()->subMonth(),
            'created_at'   => now()->subMonth(),
            'updated_at'   => now()->subMonth(),
        ]);

        $usage = app(UsageService::class);
        $this->assertEquals(0, $usage->executionsThisMonth($this->tenant));
    }

    public function test_usage_summary_reflects_current_usage(): void
    {
        Queue::fake();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/execute");

        $usage   = app(UsageService::class);
        $summary = $usage->summary($this->tenant);

        $this->assertEquals(Plans::FREE, $summary['plan']);
        $this->assertEquals(1, $summary['executions_per_month']['used']);
        $this->assertEquals(100, $summary['executions_per_month']['limit']);
    }
}
