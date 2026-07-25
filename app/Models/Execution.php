<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Execution extends Model
{
    use HasUuids;

    protected $fillable = [
        'workflow_id',
        'triggered_by',
        'trigger_type',
        'status',
        'payload',
        'context',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    protected $casts = [
        'payload' => 'array',
        'context' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isRunning(): bool  { return $this->status === 'running'; }
    public function isSuccess(): bool  { return $this->status === 'success'; }
    public function isFailed(): bool   { return $this->status === 'failed'; }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExecutionLog::class)->orderBy('created_at');
    }
}
