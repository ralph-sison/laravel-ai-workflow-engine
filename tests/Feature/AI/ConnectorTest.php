<?php

namespace Tests\Feature\AI;

use App\Models\Connector;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectorTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $member;
    private Tenant $tenant;

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
    }

    public function test_owner_can_create_connector(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/connectors', [
                'name'        => 'My OpenAI Key',
                'type'        => 'openai',
                'credentials' => ['api_key' => 'sk-test-key'],
                'meta'        => ['default_model' => 'gpt-4o-mini'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'My OpenAI Key')
            ->assertJsonPath('data.type', 'openai')
            ->assertJsonMissing(['credentials']); // never exposed in response

        $this->assertDatabaseHas('connectors', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'My OpenAI Key',
            'type'      => 'openai',
        ]);
    }

    public function test_member_cannot_create_connector(): void
    {
        $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v1/connectors', [
                'name'        => 'Should Fail',
                'type'        => 'openai',
                'credentials' => ['api_key' => 'sk-test'],
            ])
            ->assertForbidden();
    }

    public function test_owner_can_list_connectors(): void
    {
        Connector::create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'OpenAI Connector',
            'type'        => 'openai',
            'credentials' => ['api_key' => 'sk-test'],
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/connectors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'OpenAI Connector');
    }

    public function test_connector_credentials_are_encrypted_in_database(): void
    {
        $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/connectors', [
                'name'        => 'Encrypted Test',
                'type'        => 'claude',
                'credentials' => ['api_key' => 'sk-ant-supersecret'],
            ]);

        // Raw DB value must not contain the plain-text key
        $raw = \Illuminate\Support\Facades\DB::table('connectors')
            ->where('name', 'Encrypted Test')
            ->value('credentials');

        $this->assertStringNotContainsString('sk-ant-supersecret', $raw);
    }

    public function test_owner_can_update_connector(): void
    {
        $connector = Connector::create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'Old Name',
            'type'        => 'openai',
            'credentials' => ['api_key' => 'sk-old'],
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/connectors/{$connector->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_owner_can_delete_connector(): void
    {
        $connector = Connector::create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'To Delete',
            'type'        => 'openai',
            'credentials' => ['api_key' => 'sk-test'],
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/v1/connectors/{$connector->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('connectors', ['id' => $connector->id]);
    }

    public function test_connector_from_other_tenant_is_not_accessible(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other Corp', 'slug' => 'other-corp']);
        $otherConnector = Connector::create([
            'tenant_id'   => $otherTenant->id,
            'name'        => 'Other Connector',
            'type'        => 'openai',
            'credentials' => ['api_key' => 'sk-other'],
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/connectors/{$otherConnector->id}")
            ->assertForbidden(); // Policy denies cross-tenant access
    }
}
