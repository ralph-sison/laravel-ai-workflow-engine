<?php

namespace App\Services;

use App\Billing\Plans;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class UsageService
{
    /**
     * Count executions for the tenant in the current calendar month.
     */
    public function executionsThisMonth(Tenant $tenant): int
    {
        return DB::table('executions')
            ->join('workflows', 'executions.workflow_id', '=', 'workflows.id')
            ->where('workflows.tenant_id', $tenant->id)
            ->whereYear('executions.created_at', now()->year)
            ->whereMonth('executions.created_at', now()->month)
            ->count();
    }

    /**
     * Count workflows (non-deleted) for the tenant.
     */
    public function workflowCount(Tenant $tenant): int
    {
        return DB::table('workflows')
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Check whether the tenant can run another execution under their plan.
     */
    public function canExecute(Tenant $tenant): bool
    {
        $limits = Plans::limits($tenant->plan ?? Plans::FREE);

        if ($limits['executions_per_month'] === -1) {
            return true;
        }

        return $this->executionsThisMonth($tenant) < $limits['executions_per_month'];
    }

    /**
     * Check whether the tenant can create another workflow under their plan.
     */
    public function canCreateWorkflow(Tenant $tenant): bool
    {
        $limits = Plans::limits($tenant->plan ?? Plans::FREE);

        if ($limits['workflows'] === -1) {
            return true;
        }

        return $this->workflowCount($tenant) < $limits['workflows'];
    }

    /**
     * Return current usage summary for the tenant.
     */
    public function summary(Tenant $tenant): array
    {
        $plan   = $tenant->plan ?? Plans::FREE;
        $limits = Plans::limits($plan);

        return [
            'plan'                 => $plan,
            'workflows'            => [
                'used'  => $this->workflowCount($tenant),
                'limit' => $limits['workflows'] === -1 ? 'unlimited' : $limits['workflows'],
            ],
            'executions_per_month' => [
                'used'  => $this->executionsThisMonth($tenant),
                'limit' => $limits['executions_per_month'] === -1 ? 'unlimited' : $limits['executions_per_month'],
            ],
        ];
    }
}
