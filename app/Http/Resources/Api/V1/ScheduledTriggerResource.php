<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledTriggerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'workflow_id'     => $this->workflow_id,
            'workflow'        => $this->whenLoaded('workflow', fn () => [
                'id'     => $this->workflow->id,
                'name'   => $this->workflow->name,
                'status' => $this->workflow->status,
            ]),
            'cron_expression' => $this->cron_expression,
            'timezone'        => $this->timezone,
            'is_active'       => $this->is_active,
            'next_run_at'     => $this->next_run_at?->toISOString(),
            'last_run_at'     => $this->last_run_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
