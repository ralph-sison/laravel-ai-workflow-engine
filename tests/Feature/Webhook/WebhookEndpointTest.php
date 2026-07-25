<?php

namespace Tests\Feature\Webhook;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookEndpointTest extends TestCase
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
            'name'      => 'Owner User',
            'email'     => 'owner@acme.com',
            'password'  => 'password123',
        ]);

        $this->member = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Member User',
            'email'     => 'member@acme.com',
            'password'  => 'password123',
        ]);

        setPermissionsTeamId($this->tenant->id);
        $this->owner->assignRole('owner');
        $this->member->assignRole('member');

        $this->workflow = Workflow::create([
            'tenant_id'    => $this->tenant->id,
            'created_by'   => $this->owner->id,
            'name'         => 'Test Workflow',
            'status'       => 'active',
            'trigger_type' => 'webhook',
        ]);
    }

    public function test_owner_can_create_webhook_endpoint(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/webhook-endpoints', [
                'workflow_id' => $this->workflow->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.workflow_id', $this->workflow->id)
            ->assertJsonStructure(['data' => ['id', 'url', 'method', 'is_active'], 'meta' => ['secret']]);

        // Secret returned only on creation
        $this->assertNotEmpty($response->json('meta.secret'));

        $this->assertDatabaseHas('webhook_endpoints', [
            'tenant_id'   => $this->tenant->id,
            'workflow_id' => $this->workflow->id,
        ]);
    }

    public function test_member_cannot_create_webhook_endpoint(): void
    {
        $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v1/webhook-endpoints', [
                'workflow_id' => $this->workflow->id,
            ])
            ->assertForbidden();
    }

    public function test_owner_can_list_webhook_endpoints(): void
    {
        WebhookEndpoint::create([
            'tenant_id'   => $this->tenant->id,
            'workflow_id' => $this->workflow->id,
            'slug'        => 'test-slug-123',
            'secret'      => 'secret123',
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/webhook-endpoints')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_deactivate_endpoint(): void
    {
        $endpoint = WebhookEndpoint::create([
            'tenant_id'   => $this->tenant->id,
            'workflow_id' => $this->workflow->id,
            'slug'        => 'test-slug-456',
            'secret'      => 'secret456',
            'is_active'   => true,
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/webhook-endpoints/{$endpoint->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_owner_can_regenerate_secret(): void
    {
        $endpoint = WebhookEndpoint::create([
            'tenant_id'   => $this->tenant->id,
            'workflow_id' => $this->workflow->id,
            'slug'        => 'test-slug-789',
            'secret'      => 'old-secret',
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/webhook-endpoints/{$endpoint->id}/regenerate-secret")
            ->assertOk()
            ->assertJsonStructure(['secret']);

        $this->assertNotEquals('old-secret', $response->json('secret'));
    }

    public function test_owner_can_delete_endpoint(): void
    {
        $endpoint = WebhookEndpoint::create([
            'tenant_id'   => $this->tenant->id,
            'workflow_id' => $this->workflow->id,
            'slug'        => 'test-slug-del',
            'secret'      => 'secret-del',
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/v1/webhook-endpoints/{$endpoint->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('webhook_endpoints', ['id' => $endpoint->id]);
    }

    public function test_endpoint_url_contains_slug(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/webhook-endpoints', [
                'workflow_id' => $this->workflow->id,
            ]);

        $url = $response->json('data.url');
        $this->assertStringContainsString('/api/v1/webhooks/', $url);
    }
}
