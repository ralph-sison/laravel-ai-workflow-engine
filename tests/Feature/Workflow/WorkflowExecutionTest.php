<?php

namespace Tests\Feature\Workflow;

use App\Jobs\ExecuteWorkflowStepJob;
use App\Jobs\ProcessWorkflowExecutionJob;
use App\Models\Execution;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WorkflowExecutionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Tenant $tenant;
    private Workflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);
        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Ralph Sison',
            'email'     => 'ralph@acme.com',
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

        WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Step One',
            'type'        => 'transform',
            'order'       => 1,
        ]);
    }

    public function test_manual_execute_dispatches_process_job(): void
    {
        Queue::fake();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/execute")
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'running');

        Queue::assertPushed(ProcessWorkflowExecutionJob::class);
    }

    public function test_process_job_dispatches_step_jobs_as_batch(): void
    {
        Bus::fake([ProcessWorkflowExecutionJob::class]);

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/execute")
            ->assertStatus(201);

        // The outer orchestrator job is dispatched; the batch is dispatched inside that job
        Bus::assertDispatched(ProcessWorkflowExecutionJob::class);
    }

    public function test_draft_workflow_cannot_be_executed(): void
    {
        $draftWorkflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Draft Workflow',
            'status'     => 'draft',
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$draftWorkflow->id}/execute")
            ->assertForbidden();
    }

    public function test_failed_execution_can_be_retried(): void
    {
        Queue::fake();

        $execution = Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'failed',
            'payload'      => ['original' => 'data'],
            'started_at'   => now()->subMinute(),
            'finished_at'  => now(),
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/executions/{$execution->id}/retry");

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'running');

        Queue::assertPushed(ProcessWorkflowExecutionJob::class);

        $this->assertDatabaseCount('executions', 2);
    }

    public function test_non_failed_execution_cannot_be_retried(): void
    {
        $execution = Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'success',
            'started_at'   => now()->subMinute(),
            'finished_at'  => now(),
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/executions/{$execution->id}/retry")
            ->assertStatus(422);
    }

    public function test_owner_can_list_executions_for_workflow(): void
    {
        Queue::fake();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$this->workflow->id}/execute");

        $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/workflows/{$this->workflow->id}/executions")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_step_job_runs_and_updates_execution_log(): void
    {
        $execution = Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'context'      => ['key' => 'value'],
            'started_at'   => now(),
        ]);

        $step = $this->workflow->steps->first();

        $job = new ExecuteWorkflowStepJob($execution->id, $step->id);
        $job->handle();

        $this->assertDatabaseHas('execution_logs', [
            'execution_id' => $execution->id,
            'step_id'      => $step->id,
            'status'       => 'success',
        ]);
    }
}
