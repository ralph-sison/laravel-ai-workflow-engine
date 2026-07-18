<?php

namespace App\Models;

use App\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'status',
        'trigger_type',
        'trigger_config',
        'version',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isActive(): bool   { return $this->status === 'active'; }
    public function isPaused(): bool   { return $this->status === 'paused'; }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class)->latest();
    }
}
