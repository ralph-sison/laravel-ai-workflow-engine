<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookEndpointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'workflow_id'        => $this->workflow_id,
            'workflow'           => $this->whenLoaded('workflow', fn () => [
                'id'     => $this->workflow->id,
                'name'   => $this->workflow->name,
                'status' => $this->workflow->status,
            ]),
            'url'                => url("/api/v1/webhooks/{$this->slug}"),
            'method'             => $this->method,
            'is_active'          => $this->is_active,
            'trigger_count'      => $this->trigger_count,
            'last_triggered_at'  => $this->last_triggered_at?->toISOString(),
            'created_at'         => $this->created_at->toISOString(),
            // secret intentionally excluded — shown only on create/regenerate
        ];
    }
}
