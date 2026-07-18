<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WebhookEndpoint extends TenantAwareModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'workflow_id',
        'slug',
        'secret',
        'method',
        'is_active',
        'last_triggered_at',
        'trigger_count',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'last_triggered_at'  => 'datetime',
        'trigger_count'      => 'integer',
    ];

    public static function generateSlug(): string
    {
        return Str::random(32);
    }

    public static function generateSecret(): string
    {
        return Str::random(64);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function recordTrigger(): void
    {
        $this->increment('trigger_count');
        $this->update(['last_triggered_at' => now()]);
    }
}
