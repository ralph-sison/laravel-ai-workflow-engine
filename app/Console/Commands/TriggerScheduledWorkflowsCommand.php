<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWorkflowExecutionJob;
use App\Models\ScheduledTrigger;
use Illuminate\Console\Command;

class TriggerScheduledWorkflowsCommand extends Command
{
    protected $signature = 'flowforge:trigger-scheduled';
    protected $description = 'Fire all scheduled workflow triggers that are due.';

    public function handle(): int
    {
        $due = ScheduledTrigger::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with('workflow')
            ->get();

        $fired = 0;

        foreach ($due as $trigger) {
            $workflow = $trigger->workflow;

            if (! $workflow || ! $workflow->isActive()) {
                continue;
            }

            $execution = $workflow->executions()->create([
                'trigger_type' => 'schedule',
                'triggered_by' => null,
                'status'       => 'running',
                'payload'      => ['scheduled_trigger_id' => $trigger->id],
                'context'      => ['scheduled_trigger_id' => $trigger->id],
                'started_at'   => now(),
            ]);

            ProcessWorkflowExecutionJob::dispatch($execution->id);

            $trigger->updateAfterRun();

            $fired++;
        }

        $this->info("Fired {$fired} scheduled workflow(s).");

        return self::SUCCESS;
    }
}
