<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'workflow_id'  => $this->workflow_id,
            'trigger_type' => $this->trigger_type,
            'status'       => $this->status,
            'payload'      => $this->payload,
            'context'      => $this->context,
            'started_at'   => $this->started_at,
            'finished_at'  => $this->finished_at,
            'duration_ms'  => $this->duration_ms,
            'logs'         => ExecutionLogResource::collection($this->whenLoaded('logs')),
            'created_at'   => $this->created_at,
        ];
    }
}
