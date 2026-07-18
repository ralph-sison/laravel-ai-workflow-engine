<?php

namespace App\Models;

use Cron\CronExpression;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledTrigger extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'workflow_id',
        'cron_expression',
        'timezone',
        'is_active',
        'next_run_at',
        'last_run_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function calculateNextRunAt(): \DateTime
    {
        $cron = new CronExpression($this->cron_expression);
        return $cron->getNextRunDate('now', 0, false, $this->timezone);
    }

    public function updateAfterRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRunAt(),
        ]);
    }
}
