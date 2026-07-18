<?php

namespace Tests\Feature\Notification;

use App\Jobs\ExecuteWorkflowStepJob;
use App\Models\Execution;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationStepTest extends TestCase
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
            'tenant_id'  => $this->tenant->id,
            'created_by' => $this->owner->id,
            'name'       => 'Notification Workflow',
            'status'     => 'active',
        ]);
    }

    private function makeExecution(array $context = []): Execution
    {
        return Execution::create([
            'workflow_id'  => $this->workflow->id,
            'triggered_by' => $this->owner->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'context'      => $context,
            'started_at'   => now(),
        ]);
    }

    public function test_email_notification_step_sends_mail(): void
    {
        Mail::fake();

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Send Email',
            'type'        => 'notification',
            'order'       => 1,
            'config'      => [
                'channel' => 'email',
                'to'      => 'customer@example.com',
                'subject' => 'Order Confirmed',
                'message' => 'Hello {{customer_name}}, your order is confirmed.',
            ],
        ]);

        $execution = $this->makeExecution(['customer_name' => 'Ralph']);

        (new ExecuteWorkflowStepJob($execution->id, $step->id))->handle();

        Mail::assertSent(\App\Mail\WorkflowNotificationMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        $log = \App\Models\ExecutionLog::where('execution_id', $execution->id)->first();
        $this->assertEquals('success', $log->status);
        $this->assertEquals('email', $log->output['channel']);
    }

    public function test_slack_notification_step_posts_to_webhook(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Slack Alert',
            'type'        => 'notification',
            'order'       => 1,
            'config'      => [
                'channel'           => 'slack',
                'slack_webhook_url' => 'https://hooks.slack.com/services/test',
                'message'           => 'New order from {{customer_name}}',
            ],
        ]);

        $execution = $this->makeExecution(['customer_name' => 'Ralph']);

        (new ExecuteWorkflowStepJob($execution->id, $step->id))->handle();

        Http::assertSent(fn ($req) => str_contains($req->url(), 'hooks.slack.com'));

        $log = \App\Models\ExecutionLog::where('execution_id', $execution->id)->first();
        $this->assertEquals('success', $log->status);
        $this->assertEquals('slack', $log->output['channel']);
    }

    public function test_log_notification_step_succeeds_without_external_call(): void
    {
        Http::fake(); // ensure nothing external is called

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Log Notification',
            'type'        => 'notification',
            'order'       => 1,
            'config'      => [
                'channel' => 'log',
                'message' => 'Workflow ran for {{customer_name}}',
            ],
        ]);

        $execution = $this->makeExecution(['customer_name' => 'Ralph']);

        (new ExecuteWorkflowStepJob($execution->id, $step->id))->handle();

        Http::assertNothingSent();

        $log = \App\Models\ExecutionLog::where('execution_id', $execution->id)->first();
        $this->assertEquals('success', $log->status);
        $this->assertEquals('log', $log->output['channel']);
    }

    public function test_message_interpolates_context_variables(): void
    {
        Mail::fake();

        $step = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name'        => 'Interpolation Test',
            'type'        => 'notification',
            'order'       => 1,
            'config'      => [
                'channel' => 'email',
                'to'      => 'test@example.com',
                'subject' => 'Hello {{name}}',
                'message' => 'Dear {{name}}, your order #{{order_id}} is ready.',
            ],
        ]);

        $execution = $this->makeExecution(['name' => 'Ralph', 'order_id' => '1234']);

        (new ExecuteWorkflowStepJob($execution->id, $step->id))->handle();

        Mail::assertSent(\App\Mail\WorkflowNotificationMail::class, fn ($mail) => $mail->hasTo('test@example.com'));

        $log = \App\Models\ExecutionLog::where('execution_id', $execution->id)->first();
        $this->assertEquals('success', $log->status);
    }
}
