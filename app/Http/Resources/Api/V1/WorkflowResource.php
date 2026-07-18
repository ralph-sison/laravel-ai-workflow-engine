<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'status'         => $this->status,
            'trigger_type'   => $this->trigger_type,
            'trigger_config' => $this->trigger_config,
            'version'        => $this->version,
            'last_run_at'    => $this->last_run_at,
            'steps_count'    => $this->whenCounted('steps'),
            'steps'          => WorkflowStepResource::collection($this->whenLoaded('steps')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
