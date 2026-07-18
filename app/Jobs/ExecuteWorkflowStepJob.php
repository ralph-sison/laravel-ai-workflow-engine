<?php

namespace App\Jobs;

use App\AI\AiProviderFactory;
use App\Models\Execution;
use App\Models\ExecutionLog;
use App\Models\WorkflowStep;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflowStepJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly string $executionId,
        public readonly string $stepId,
    ) {
        $this->onQueue('workflows');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $execution = Execution::findOrFail($this->executionId);
        $step = WorkflowStep::findOrFail($this->stepId);

        // Override queue for AI steps
        if ($step->type === 'ai') {
            $this->onQueue('ai');
        }

        $startedAt = microtime(true);

        $log = ExecutionLog::create([
            'execution_id' => $execution->id,
            'step_id'      => $step->id,
            'status'       => 'running',
            'input'        => $execution->context ?? [],
            'attempt'      => $this->attempts(),
        ]);

        try {
            $output = $this->runStep($step, $execution->context ?? []);

            $execution->update([
                'context' => array_merge($execution->context ?? [], $output),
            ]);

            $log->update([
                'status'      => 'success',
                'output'      => $output,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

        } catch (\Throwable $e) {
            $log->update([
                'status'      => 'failed',
                'error'       => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            if ($step->on_error === 'stop') {
                $this->batch()?->cancel();
                $execution->update(['status' => 'failed', 'finished_at' => now()]);
                throw $e;
            }
            // on_error === 'continue' — log failure but don't fail the job
        }
    }

    public function failed(\Throwable $exception): void
    {
        $execution = Execution::find($this->executionId);
        $execution?->update(['status' => 'failed', 'finished_at' => now()]);
    }

    private function runStep(WorkflowStep $step, array $context): array
    {
        return match ($step->type) {
            'ai'        => $this->runAiStep($step, $context),
            'transform' => ['step_result' => "Transform step [{$step->name}] executed"],
            'http'      => ['step_result' => "HTTP step [{$step->name}] executed"],
            default     => ['step_result' => "Step [{$step->name}] executed (type: {$step->type})"],
        };
    }

    private function runAiStep(WorkflowStep $step, array $context): array
    {
        $config   = $step->config ?? [];
        $factory  = app(AiProviderFactory::class);
        $provider = $factory->make($config);

        $messages = $config['messages'] ?? [
            ['role' => 'user', 'content' => $config['prompt'] ?? json_encode($context)],
        ];

        $options = array_filter([
            'model'       => $config['model'] ?? null,
            'system'      => $config['system'] ?? null,
            'temperature' => $config['temperature'] ?? null,
            'max_tokens'  => $config['max_tokens'] ?? null,
        ]);

        $response = $provider->complete($messages, $options);

        return $response->toArray();
    }
}
