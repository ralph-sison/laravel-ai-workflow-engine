<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\UsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly UsageService $usage) {}

    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        $totalWorkflows  = DB::table('workflows')->where('tenant_id', $tenant->id)->whereNull('deleted_at')->count();
        $activeWorkflows = DB::table('workflows')->where('tenant_id', $tenant->id)->where('status', 'active')->whereNull('deleted_at')->count();

        $executionsToday = DB::table('executions')
            ->join('workflows', 'executions.workflow_id', '=', 'workflows.id')
            ->where('workflows.tenant_id', $tenant->id)
            ->whereDate('executions.created_at', today())
            ->count();

        $failedToday = DB::table('executions')
            ->join('workflows', 'executions.workflow_id', '=', 'workflows.id')
            ->where('workflows.tenant_id', $tenant->id)
            ->where('executions.status', 'failed')
            ->whereDate('executions.created_at', today())
            ->count();

        $recentExecutions = DB::table('executions')
            ->join('workflows', 'executions.workflow_id', '=', 'workflows.id')
            ->where('workflows.tenant_id', $tenant->id)
            ->orderByDesc('executions.created_at')
            ->limit(10)
            ->select('executions.id', 'workflows.name as workflow_name', 'executions.status', 'executions.trigger_type', 'executions.started_at')
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_workflows'  => $totalWorkflows,
                'active_workflows' => $activeWorkflows,
                'executions_today' => $executionsToday,
                'failed_today'     => $failedToday,
            ],
            'usage'              => $this->usage->summary($tenant),
            'recent_executions'  => $recentExecutions,
        ]);
    }
}
