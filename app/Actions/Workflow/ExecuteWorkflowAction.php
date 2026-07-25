<?php

namespace App\Actions\Workflow;

use App\Jobs\ProcessWorkflowExecutionJob;
use App\Models\Execution;
use App\Models\ExecutionLog;
use App\Models\User;
use App\Models\Workflow;

class ExecuteWorkflowAction
{
    public function execute(Workflow $workflow, ?User $triggeredBy = null, array $payload = []): Execution
    {
        $execution = Execution::create([
            'workflow_id'  => $workflow->id,
            'triggered_by' => $triggeredBy?->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'payload'      => $payload,
            'context'      => $payload,
            'started_at'   => now(),
        ]);

        // When QUEUE_CONNECTION=sync (tests), the job runs inline and completes immediately.
        // In production with Redis queues, it dispatches to the 'workflows' queue picked up by Horizon.
        ProcessWorkflowExecutionJob::dispatch($execution->id);

        return $execution->fresh(['logs']);
    }
}
