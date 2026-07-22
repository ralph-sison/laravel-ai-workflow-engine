<?php

namespace Tests\Feature\Web;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WorkflowWebTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(RoleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);
        $this->owner  = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Ralph',
            'email'     => 'ralph@acme.com',
            'password'  => 'password123',
        ]);
        setPermissionsTeamId($this->tenant->id);
        $this->owner->assignRole('owner');
    }

    public function test_workflow_index_renders_inertia_component(): void
    {
        $this->actingAs($this->owner)
            ->get('/workflows')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Workflows/Index')
                ->has('workflows')
            );
    }

    public function test_workflow_create_renders_inertia_component(): void
    {
        $this->actingAs($this->owner)
            ->get('/workflows/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Workflows/Create'));
    }

    public function test_owner_can_create_workflow_via_web(): void
    {
        $this->withoutCsrf();
        $this->actingAs($this->owner)
            ->post('/workflows', [
                'name'         => 'My Web Workflow',
                'trigger_type' => 'manual',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('workflows', [
            'name'      => 'My Web Workflow',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_workflow_show_renders_inertia_component(): void
    {
        $workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Test Workflow',
            'status'     => 'draft',
        ]);

        $this->actingAs($this->owner)
            ->get("/workflows/{$workflow->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Workflows/Show')
                ->has('workflow')
                ->has('executions')
            );
    }

    public function test_owner_can_activate_workflow_via_web(): void
    {
        $this->withoutCsrf();
        $workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Draft Workflow',
            'status'     => 'draft',
        ]);

        $this->actingAs($this->owner)
            ->post("/workflows/{$workflow->id}/activate")
            ->assertRedirect();

        $this->assertDatabaseHas('workflows', ['id' => $workflow->id, 'status' => 'active']);
    }

    public function test_owner_can_execute_active_workflow_via_web(): void
    {
        $this->withoutCsrf();
        Queue::fake();

        $workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Active Workflow',
            'status'     => 'active',
        ]);

        $this->actingAs($this->owner)
            ->post("/workflows/{$workflow->id}/execute")
            ->assertRedirect();

        Queue::assertPushed(\App\Jobs\ProcessWorkflowExecutionJob::class);
    }

    public function test_dashboard_renders_inertia_component(): void
    {
        $this->actingAs($this->owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->has('usage')
                ->has('recent_executions')
            );
    }
}
