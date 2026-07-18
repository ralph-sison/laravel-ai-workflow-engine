<?php

namespace App\Jobs;

use App\Models\Execution;
use App\Models\Workflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class ProcessWorkflowExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(
        public readonly string $executionId,
    ) {
        $this->onQueue('workflows');
    }

    public function handle(): void
    {
        $execution = Execution::with('workflow.steps')->findOrFail($this->executionId);
        $workflow = $execution->workflow;

        if (! $workflow || $execution->status !== 'running') {
            return;
        }

        $steps = $workflow->steps; // already ordered by 'order'

        if ($steps->isEmpty()) {
            $execution->update([
                'status'      => 'success',
                'finished_at' => now(),
                'duration_ms' => 0,
            ]);
            $workflow->update(['last_run_at' => now()]);
            return;
        }

        // Build a chain of step jobs
        $chain = $steps->map(
            fn ($step) => new ExecuteWorkflowStepJob($execution->id, $step->id)
        )->all();

        // Dispatch as a Bus batch so we can track completion and cancellation
        Bus::batch($chain)
            ->name("workflow:{$workflow->id}:execution:{$execution->id}")
            ->allowFailures() // individual step failure handled inside the job
            ->finally(function () use ($execution, $workflow) {
                $execution->refresh();

                // Only mark success if not already failed/cancelled
                if ($execution->status === 'running') {
                    $execution->update([
                        'status'      => 'success',
                        'finished_at' => now(),
                    ]);
                    $workflow->update(['last_run_at' => now()]);
                }
            })
            ->onQueue('workflows')
            ->dispatch();
    }

    public function failed(\Throwable $exception): void
    {
        $execution = Execution::find($this->executionId);
        $execution?->update(['status' => 'failed', 'finished_at' => now()]);
    }
}
