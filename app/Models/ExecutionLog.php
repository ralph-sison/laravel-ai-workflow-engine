<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'execution_id',
        'step_id',
        'status',
        'input',
        'output',
        'error',
        'attempt',
        'duration_ms',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class);
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }
}
