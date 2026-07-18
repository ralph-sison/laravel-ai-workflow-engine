<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id
            && $user->hasAnyRole(['owner', 'admin', 'member']);
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id
            && $user->hasAnyRole(['owner', 'admin']);
    }

    public function execute(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id
            && $user->hasAnyRole(['owner', 'admin', 'member'])
            && $workflow->isActive();
    }
}
