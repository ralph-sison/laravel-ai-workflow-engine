<?php

namespace App\Actions\Workflow;

use App\Models\Execution;
use App\Models\ExecutionLog;
use App\Models\User;
use App\Models\Workflow;

class ExecuteWorkflowAction
{
    public function execute(Workflow $workflow, ?User $triggeredBy = null, array $payload = []): Execution
    {
        $startedAt = microtime(true);

        $execution = Execution::create([
            'workflow_id'  => $workflow->id,
            'triggered_by' => $triggeredBy?->id,
            'trigger_type' => 'manual',
            'status'       => 'running',
            'payload'      => $payload,
            'context'      => [],
            'started_at'   => now(),
        ]);

        try {
            $context = $payload;
            $steps = $workflow->steps;

            foreach ($steps as $step) {
                $stepStartedAt = microtime(true);

                $log = ExecutionLog::create([
                    'execution_id' => $execution->id,
                    'step_id'      => $step->id,
                    'status'       => 'running',
                    'input'        => $context,
                    'attempt'      => 1,
                ]);

                try {
                    // Step execution placeholder — real handlers wired in v0.4.0+
                    $output = $this->runStep($step, $context);

                    $context = array_merge($context, $output);

                    $log->update([
                        'status'      => 'success',
                        'output'      => $output,
                        'duration_ms' => (int) ((microtime(true) - $stepStartedAt) * 1000),
                    ]);
                } catch (\Throwable $e) {
                    $log->update([
                        'status'      => 'failed',
                        'error'       => $e->getMessage(),
                        'duration_ms' => (int) ((microtime(true) - $stepStartedAt) * 1000),
                    ]);

                    if ($step->on_error === 'stop') {
                        throw $e;
                    }
                }
            }

            $execution->update([
                'status'      => 'success',
                'context'     => $context,
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            $workflow->update(['last_run_at' => now()]);

        } catch (\Throwable $e) {
            $execution->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        }

        return $execution->fresh(['logs']);
    }

    private function runStep(\App\Models\WorkflowStep $step, array $context): array
    {
        // Stub — real handlers wired in v0.4.0 (AI), v0.5.0 (HTTP/webhooks)
        return ['step_result' => "Step [{$step->name}] executed (type: {$step->type})", 'previous_context' => $context];
    }
}
