<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookEndpoint;

class WebhookEndpointPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function view(User $user, WebhookEndpoint $endpoint): bool
    {
        return $user->tenant_id === $endpoint->tenant_id
            && $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function update(User $user, WebhookEndpoint $endpoint): bool
    {
        return $user->tenant_id === $endpoint->tenant_id
            && $user->hasAnyRole(['owner', 'admin']);
    }

    public function delete(User $user, WebhookEndpoint $endpoint): bool
    {
        return $user->tenant_id === $endpoint->tenant_id
            && $user->hasAnyRole(['owner', 'admin']);
    }
}
