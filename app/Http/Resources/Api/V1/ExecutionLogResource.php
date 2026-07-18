<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExecutionLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'step_id'     => $this->step_id,
            'status'      => $this->status,
            'input'       => $this->input,
            'output'      => $this->output,
            'error'       => $this->error,
            'attempt'     => $this->attempt,
            'duration_ms' => $this->duration_ms,
            'created_at'  => $this->created_at,
        ];
    }
}
