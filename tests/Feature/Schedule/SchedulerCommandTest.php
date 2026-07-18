<?php

namespace Tests\Feature\Schedule;

use App\Models\ScheduledTrigger;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SchedulerCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;
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

        setPermissionsTeamId($this->tenant->id);
        $this->owner->assignRole('owner');

        $this->workflow = Workflow::create([
            'tenant_id'    => $this->tenant->id,
            'created_by'   => $this->owner->id,
            'name'         => 'Scheduled Workflow',
            'status'       => 'active',
            'trigger_type' => 'schedule',
        ]);
    }

    public function test_command_fires_due_triggers(): void
    {
        Queue::fake();

        ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '* * * * *',
            'next_run_at'     => now()->subMinute(), // overdue
            'is_active'       => true,
        ]);

        $this->artisan('flowforge:trigger-scheduled')->assertSuccessful();

        Queue::assertPushed(\App\Jobs\ProcessWorkflowExecutionJob::class);

        $this->assertDatabaseHas('executions', [
            'workflow_id'  => $this->workflow->id,
            'trigger_type' => 'schedule',
            'status'       => 'running',
        ]);
    }

    public function test_command_skips_future_triggers(): void
    {
        Queue::fake();

        ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '* * * * *',
            'next_run_at'     => now()->addHour(), // not due yet
            'is_active'       => true,
        ]);

        $this->artisan('flowforge:trigger-scheduled')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_command_skips_inactive_triggers(): void
    {
        Queue::fake();

        ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '* * * * *',
            'next_run_at'     => now()->subMinute(),
            'is_active'       => false,
        ]);

        $this->artisan('flowforge:trigger-scheduled')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_command_skips_paused_workflow(): void
    {
        Queue::fake();

        $this->workflow->update(['status' => 'paused']);

        ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '* * * * *',
            'next_run_at'     => now()->subMinute(),
            'is_active'       => true,
        ]);

        $this->artisan('flowforge:trigger-scheduled')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('executions', 0);
    }

    public function test_command_updates_next_run_at_after_firing(): void
    {
        Queue::fake();

        $trigger = ScheduledTrigger::create([
            'tenant_id'       => $this->tenant->id,
            'workflow_id'     => $this->workflow->id,
            'cron_expression' => '* * * * *',
            'next_run_at'     => now()->subMinute(),
            'is_active'       => true,
        ]);

        $this->artisan('flowforge:trigger-scheduled')->assertSuccessful();

        $trigger->refresh();
        $this->assertNotNull($trigger->last_run_at);
        $this->assertTrue($trigger->next_run_at->isFuture());
    }
}
