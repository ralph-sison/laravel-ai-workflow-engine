import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import { PageProps, PlanUsage } from '@/types';

interface Props extends PageProps {
    stats: {
        total_workflows: number;
        active_workflows: number;
        executions_today: number;
        failed_today: number;
    };
    usage: PlanUsage;
    recent_executions: Array<{
        id: string;
        workflow_name: string;
        status: string;
        trigger_type: string;
        started_at: string;
    }>;
}

export default function Dashboard({ stats, usage, recent_executions }: Props) {
    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p className="mt-1 text-sm text-gray-500">Overview of your automation platform</p>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <StatCard label="Total Workflows" value={stats.total_workflows} />
                <StatCard label="Active Workflows" value={stats.active_workflows} highlight />
                <StatCard label="Executions Today" value={stats.executions_today} />
                <StatCard label="Failed Today" value={stats.failed_today} danger={stats.failed_today > 0} />
            </div>

            {/* Plan usage */}
            <div className="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-sm font-semibold text-gray-900">Plan Usage</h2>
                    <Badge label={usage.plan} variant="purple" />
                </div>
                <div className="space-y-3">
                    <UsageBar
                        label="Workflows"
                        used={usage.workflows.used}
                        limit={usage.workflows.limit}
                    />
                    <UsageBar
                        label="Executions this month"
                        used={usage.executions_per_month.used}
                        limit={usage.executions_per_month.limit}
                    />
                </div>
            </div>

            {/* Recent executions */}
            <div className="bg-white rounded-xl border border-gray-200">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-sm font-semibold text-gray-900">Recent Executions</h2>
                </div>
                {recent_executions.length === 0 ? (
                    <div className="px-6 py-8 text-center text-sm text-gray-400">
                        No executions yet. Run a workflow to see results here.
                    </div>
                ) : (
                    <ul className="divide-y divide-gray-100">
                        {recent_executions.map(exec => (
                            <li key={exec.id} className="px-6 py-3 flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">{exec.workflow_name}</p>
                                    <p className="text-xs text-gray-400">{new Date(exec.started_at).toLocaleString()}</p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge label={exec.trigger_type} />
                                    <Badge label={exec.status} />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}

function StatCard({ label, value, highlight = false, danger = false }: {
    label: string;
    value: number;
    highlight?: boolean;
    danger?: boolean;
}) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5">
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{label}</p>
            <p className={`mt-1 text-3xl font-bold ${danger ? 'text-red-600' : highlight ? 'text-indigo-600' : 'text-gray-900'}`}>
                {value}
            </p>
        </div>
    );
}

function UsageBar({ label, used, limit }: { label: string; used: number; limit: number | 'unlimited' }) {
    const pct = limit === 'unlimited' ? 0 : Math.min(100, Math.round((used / limit) * 100));
    return (
        <div>
            <div className="flex justify-between text-xs text-gray-500 mb-1">
                <span>{label}</span>
                <span>{used} / {limit === 'unlimited' ? '∞' : limit}</span>
            </div>
            {limit !== 'unlimited' && (
                <div className="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div
                        className={`h-full rounded-full ${pct >= 90 ? 'bg-red-500' : 'bg-indigo-500'}`}
                        style={{ width: `${pct}%` }}
                    />
                </div>
            )}
        </div>
    );
}
