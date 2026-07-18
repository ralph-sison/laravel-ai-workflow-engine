<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowStep extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'workflow_id',
        'name',
        'order',
        'type',
        'config',
        'on_error',
        'retry_limit',
        'timeout_seconds',
    ];

    protected $casts = [
        'config' => 'array',
        'order' => 'integer',
        'retry_limit' => 'integer',
        'timeout_seconds' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExecutionLog::class, 'step_id');
    }
}
