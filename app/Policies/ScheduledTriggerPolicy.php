<?php

namespace App\Policies;

use App\Models\ScheduledTrigger;
use App\Models\User;

class ScheduledTriggerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function view(User $user, ScheduledTrigger $trigger): bool
    {
        return $user->tenant_id === $trigger->tenant_id
            && $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function update(User $user, ScheduledTrigger $trigger): bool
    {
        return $user->tenant_id === $trigger->tenant_id
            && $user->hasAnyRole(['owner', 'admin']);
    }

    public function delete(User $user, ScheduledTrigger $trigger): bool
    {
        return $user->tenant_id === $trigger->tenant_id
            && $user->hasAnyRole(['owner', 'admin']);
    }
}
