<?php

namespace Tests\Feature\Workflow;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Tenant $tenant;

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
    }

    public function test_owner_can_create_workflow(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/workflows', [
                'name'         => 'Email Summarizer',
                'description'  => 'Summarizes incoming emails using AI',
                'trigger_type' => 'manual',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Email Summarizer')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('workflows', [
            'name'      => 'Email Summarizer',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_owner_can_list_workflows(): void
    {
        Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Workflow One',
            'status'     => 'draft',
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/workflows')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_update_workflow(): void
    {
        $workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Old Name',
            'status'     => 'draft',
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/workflows/{$workflow->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_owner_can_delete_workflow(): void
    {
        $workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'To Delete',
            'status'     => 'draft',
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/v1/workflows/{$workflow->id}")
            ->assertOk();

        $this->assertSoftDeleted('workflows', ['id' => $workflow->id]);
    }

    public function test_owner_can_activate_and_pause_workflow(): void
    {
        $workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Status Test',
            'status'     => 'draft',
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$workflow->id}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/workflows/{$workflow->id}/pause")
            ->assertOk()
            ->assertJsonPath('data.status', 'paused');
    }

    public function test_user_from_other_tenant_cannot_view_workflow(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other Corp', 'slug' => 'other-corp']);
        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name'      => 'Other User',
            'email'     => 'other@corp.com',
            'password'  => 'password123',
        ]);

        $workflow = Workflow::create([
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Private Workflow',
            'status'     => 'draft',
        ]);

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/workflows/{$workflow->id}")
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_workflows(): void
    {
        $this->getJson('/api/v1/workflows')->assertUnauthorized();
    }
}
