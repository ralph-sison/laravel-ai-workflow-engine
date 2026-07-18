<?php

namespace Tests\Feature\Webhook;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookReceiverTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;
    private Workflow $workflow;
    private WebhookEndpoint $endpoint;
    private string $secret;

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
            'name'         => 'Webhook Workflow',
            'status'       => 'active',
            'trigger_type' => 'webhook',
        ]);

        $this->secret = 'test-secret-value';

        $this->endpoint = WebhookEndpoint::create([
            'tenant_id'   => $this->tenant->id,
            'workflow_id' => $this->workflow->id,
            'slug'        => 'test-public-slug',
            'secret'      => $this->secret,
            'is_active'   => true,
        ]);
    }

    private function validSignature(string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', $body, $this->secret);
    }

    public function test_valid_webhook_triggers_workflow_execution(): void
    {
        Queue::fake();

        $body = json_encode(['event' => 'order.created', 'order_id' => 42]);

        $this->postJson('/api/v1/webhooks/test-public-slug', json_decode($body, true), [
            'X-FlowForge-Signature' => $this->validSignature($body),
            'Content-Type'          => 'application/json',
        ])
            ->assertStatus(202)
            ->assertJsonStructure(['message', 'execution_id']);

        Queue::assertPushed(\App\Jobs\ProcessWorkflowExecutionJob::class);

        $this->assertDatabaseHas('executions', [
            'workflow_id'  => $this->workflow->id,
            'trigger_type' => 'webhook',
            'status'       => 'running',
        ]);
    }

    public function test_invalid_signature_returns_401(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/webhooks/test-public-slug', ['event' => 'test'], [
            'X-FlowForge-Signature' => 'sha256=invalidsignature',
        ])
            ->assertStatus(401);

        Queue::assertNothingPushed();
    }

    public function test_missing_signature_returns_401(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/webhooks/test-public-slug', ['event' => 'test'])
            ->assertStatus(401);

        Queue::assertNothingPushed();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->postJson('/api/v1/webhooks/nonexistent-slug', ['event' => 'test'], [
            'X-FlowForge-Signature' => $this->validSignature('{}'),
        ])
            ->assertStatus(404);
    }

    public function test_inactive_endpoint_returns_422(): void
    {
        Queue::fake();

        $this->endpoint->update(['is_active' => false]);

        $body = json_encode(['event' => 'test']);

        $this->postJson('/api/v1/webhooks/test-public-slug', json_decode($body, true), [
            'X-FlowForge-Signature' => $this->validSignature($body),
        ])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_paused_workflow_returns_422(): void
    {
        Queue::fake();

        $this->workflow->update(['status' => 'paused']);

        $body = json_encode(['event' => 'test']);

        $this->postJson('/api/v1/webhooks/test-public-slug', json_decode($body, true), [
            'X-FlowForge-Signature' => $this->validSignature($body),
        ])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_trigger_count_increments_on_valid_request(): void
    {
        Queue::fake();

        $body = json_encode(['event' => 'ping']);

        $this->postJson('/api/v1/webhooks/test-public-slug', json_decode($body, true), [
            'X-FlowForge-Signature' => $this->validSignature($body),
        ])->assertStatus(202);

        $this->assertDatabaseHas('webhook_endpoints', [
            'id'            => $this->endpoint->id,
            'trigger_count' => 1,
        ]);
    }

    public function test_webhook_payload_stored_in_execution_context(): void
    {
        Queue::fake();

        $payload = ['event' => 'order.created', 'customer' => 'Ralph'];
        $body    = json_encode($payload);

        $this->postJson('/api/v1/webhooks/test-public-slug', $payload, [
            'X-FlowForge-Signature' => $this->validSignature($body),
        ])->assertStatus(202);

        $execution = \App\Models\Execution::where('workflow_id', $this->workflow->id)->first();
        $this->assertEquals('Ralph', $execution->context['customer']);
    }
}
