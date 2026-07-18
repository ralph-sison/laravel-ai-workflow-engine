<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class TenantAwareModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model) {
            if (empty($model->tenant_id) && app()->has('current.tenant')) {
                $model->tenant_id = app('current.tenant')->id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
