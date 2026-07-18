<?php

namespace Tests\Feature\Schedule;

use App\Models\ScheduledTrigger;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledTriggerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $member;
    private Tenant $tenant;
    private Workflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Owner',
            'email'     => 'owner@acme.com',
            'password'  => 'password123',
        ]);

        $this->member = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Member',
            'email'     => 'member@acme.com',
            'password'  => 'password123',
        ]);

        setPermissionsTeamId($this->tenant->id);
        $this->owner->assignRole('owner');
        $this->member->assignRole('member');

        $this->workflow = Workflow::create([
            'tenant_id'    => $this->tenant->id,
            'created_by'   => $this->owner->id,
            'name'         => 'Daily Report',
            'status'       => 'active',
            'trigger_type' => 'schedule',
        ]);
    }

    public function test_owner_can_create_scheduled_trigger(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/scheduled-triggers', [
                'workflow_id'     => $this->workflow->id,
                'cron_expression' => '0 9 * * 1-5',
                'timezone'        => 'Australia/Melbourne',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.cron_expression', '0 9 * * 1-5')
            ->assertJsonPath('data.timezone', 'Australia/Melbourne')
            ->assertJsonStructure(['data' => ['id', 'next_run_at', 'is_active']]);

        $this->assertNotNull($response->json('data.next_run_at'));
    }

    public function test_member_cannot_create_scheduled_trigger(): void
    {
        $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v1/scheduled-triggers', [
                'workflow_id'     => $this->workflow->id,
                'cron_expression' => '0 9 * * *',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_list_scheduled_triggers(): void
    {
        ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '0 9 * * *',
            'next_run_at'     => now()->addHour(),
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/scheduled-triggers')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_update_cron_expression(): void
    {
        $trigger = ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '0 9 * * *',
            'next_run_at'     => now()->addHour(),
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/scheduled-triggers/{$trigger->id}", [
                'cron_expression' => '0 18 * * *',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.cron_expression', '0 18 * * *');

        // next_run_at should be recalculated
        $this->assertNotNull($response->json('data.next_run_at'));
    }

    public function test_owner_can_delete_scheduled_trigger(): void
    {
        $trigger = ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '0 9 * * *',
            'next_run_at'     => now()->addHour(),
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/v1/scheduled-triggers/{$trigger->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('scheduled_triggers', ['id' => $trigger->id]);
    }

    public function test_invalid_cron_expression_is_rejected(): void
    {
        $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/scheduled-triggers', [
                'workflow_id'     => $this->workflow->id,
                'cron_expression' => 'not-a-cron',
            ])
            ->assertStatus(500); // CronExpression throws on invalid expression
    }
}
