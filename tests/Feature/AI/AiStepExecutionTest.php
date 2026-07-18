<?php

namespace Tests\Feature\AI;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderContract;
use App\Jobs\ExecuteWorkflowStepJob;
use App\Models\Connector;
use App\Models\Execution;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeAiProvider;
use Tests\TestCase;

class AiStepExecutionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Tenant $tenant;
    private Workflow $workflow;
    private Connector $connector;

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
            'name'       => 'AI Workflow',
            'status'     => 'active',
        ]);

        $this->connector = Connector::create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'Test Claude',
            'type'        => 'claude',
            'credentials' => ['api_key' => 'sk-ant-test'],
        ]);
    }

    public function test_ai_step_calls_claude_and_stores_output(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'id'      => 'msg_test',
                'type'    => 'message',
                'role'    => 'assistant',
                'model'   => 'claude-haiku-4-5-20251001',
                'content' => [['type' => 'text', 'text' => 'Hello from Claude']],
                'usage'   => ['input_tokens' => 10, 'output_tokens' => 5],
            ], 200),
        ]);

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Summarise',
            'type'        => 'ai',
            'order'       => 1,
            'config'      => [
                'connector_id' => $this->connector->id,
                'prompt'       => 'Summarise this: {{input}}',
                'max_tokens'   => 100,
            ],
        ]);

        $execution = Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'context'      => ['input' => 'Some text to summarise'],
            'started_at'   => now(),
        ]);

        $job = new ExecuteWorkflowStepJob($execution->id, $step->id);
        $job->handle();

        $this->assertDatabaseHas('execution_logs', [
            'execution_id' => $execution->id,
            'step_id'      => $step->id,
            'status'       => 'success',
        ]);

        $log = \App\Models\ExecutionLog::where('execution_id', $execution->id)->first();
        $this->assertEquals('claude', $log->output['provider']);
        $this->assertEquals('Hello from Claude', $log->output['content']);
    }

    public function test_ai_step_calls_openai_and_stores_output(): void
    {
        $openaiConnector = Connector::create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'Test OpenAI',
            'type'        => 'openai',
            'credentials' => ['api_key' => 'sk-test-openai'],
        ]);

        // openai-php/client uses Guzzle directly (not Laravel Http), so we bind a
        // fake factory that returns a known AiProviderContract without any HTTP call.
        $fake = new FakeAiProvider('Hello from OpenAI', 'openai', 'gpt-4o-mini');
        $this->app->bind(AiProviderFactory::class, fn () => new class($fake) extends AiProviderFactory {
            public function __construct(private readonly AiProviderContract $fake) {}
            public function make(array $stepConfig): AiProviderContract { return $this->fake; }
        });

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'OpenAI Step',
            'type'        => 'ai',
            'order'       => 1,
            'config'      => [
                'connector_id' => $openaiConnector->id,
                'prompt'       => 'Say hello',
            ],
        ]);

        $execution = Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'context'      => [],
            'started_at'   => now(),
        ]);

        $job = new ExecuteWorkflowStepJob($execution->id, $step->id);
        $job->handle();

        $log = \App\Models\ExecutionLog::where('execution_id', $execution->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('openai', $log->output['provider']);
    }

    public function test_ai_step_calls_ollama_and_stores_output(): void
    {
        $ollamaConnector = Connector::create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'Local Ollama',
            'type'        => 'ollama',
            'credentials' => [],
            'meta'        => ['base_url' => 'http://ollama:11434'],
        ]);

        Http::fake([
            'ollama:11434/*' => Http::response([
                'model'             => 'llama3.2',
                'message'           => ['role' => 'assistant', 'content' => 'Hello from Ollama'],
                'done'              => true,
                'prompt_eval_count' => 8,
                'eval_count'        => 6,
            ], 200),
        ]);

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Ollama Step',
            'type'        => 'ai',
            'order'       => 1,
            'config'      => [
                'connector_id' => $ollamaConnector->id,
                'prompt'       => 'Say hello',
                'model'        => 'llama3.2',
            ],
        ]);

        $execution = Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'context'      => [],
            'started_at'   => now(),
        ]);

        $job = new ExecuteWorkflowStepJob($execution->id, $step->id);
        $job->handle();

        $log = \App\Models\ExecutionLog::where('execution_id', $execution->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('ollama', $log->output['provider']);
        $this->assertEquals('Hello from Ollama', $log->output['content']);
    }

    public function test_ai_step_failure_with_on_error_stop_marks_execution_failed(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Failing AI Step',
            'type'        => 'ai',
            'order'       => 1,
            'on_error'    => 'stop',
            'config'      => [
                'connector_id' => $this->connector->id,
                'prompt'       => 'Summarise',
            ],
        ]);

        $execution = Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'context'      => [],
            'started_at'   => now(),
        ]);

        $this->expectException(\Throwable::class);

        $job = new ExecuteWorkflowStepJob($execution->id, $step->id);
        $job->handle();

        $this->assertDatabaseHas('execution_logs', [
            'execution_id' => $execution->id,
            'status'       => 'failed',
        ]);
    }
}
