<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'order'           => $this->order,
            'type'            => $this->type,
            'config'          => $this->config,
            'on_error'        => $this->on_error,
            'retry_limit'     => $this->retry_limit,
            'timeout_seconds' => $this->timeout_seconds,
            'created_at'      => $this->created_at,
        ];
    }
}
