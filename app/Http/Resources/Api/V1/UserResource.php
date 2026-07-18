<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'tenant_id' => $this->tenant_id,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
                'plan' => $this->tenant->plan,
            ]),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at' => $this->created_at,
        ];
    }
}
