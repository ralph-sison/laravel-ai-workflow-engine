<?php

namespace App\Policies;

use App\Models\Connector;
use App\Models\User;

class ConnectorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function view(User $user, Connector $connector): bool
    {
        return $user->tenant_id === $connector->tenant_id
            && $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function update(User $user, Connector $connector): bool
    {
        return $user->tenant_id === $connector->tenant_id
            && $user->hasAnyRole(['owner', 'admin']);
    }

    public function delete(User $user, Connector $connector): bool
    {
        return $user->tenant_id === $connector->tenant_id
            && $user->hasAnyRole(['owner', 'admin']);
    }

    public function test(User $user, Connector $connector): bool
    {
        return $user->tenant_id === $connector->tenant_id
            && $user->hasAnyRole(['owner', 'admin', 'member']);
    }
}
