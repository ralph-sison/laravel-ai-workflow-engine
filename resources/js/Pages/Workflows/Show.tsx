import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import { PageProps, Workflow, Execution } from '@/types';

interface Props extends PageProps {
    workflow: Workflow;
    executions: Execution[];
}

export default function WorkflowShow({ workflow, executions }: Props) {
    function handleActivate() {
        router.post(`/workflows/${workflow.id}/activate`);
    }

    function handlePause() {
        router.post(`/workflows/${workflow.id}/pause`);
    }

    function handleExecute() {
        router.post(`/workflows/${workflow.id}/execute`);
    }

    return (
        <AppLayout>
            <Head title={workflow.name} />

            {/* Header */}
            <div className="flex items-start justify-between mb-8">
                <div>
                    <div className="flex items-center gap-3 mb-1">
                        <h1 className="text-2xl font-bold text-gray-900">{workflow.name}</h1>
                        <Badge label={workflow.status} />
                        <Badge label={workflow.trigger_type} />
                    </div>
                    {workflow.description && (
                        <p className="text-sm text-gray-500">{workflow.description}</p>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {workflow.status === 'draft' && (
                        <Button onClick={handleActivate}>Activate</Button>
                    )}
                    {workflow.status === 'active' && (
                        <>
                            <Button variant="secondary" onClick={handlePause}>Pause</Button>
                            <Button onClick={handleExecute}>Run Now</Button>
                        </>
                    )}
                    {workflow.status === 'paused' && (
                        <Button onClick={handleActivate}>Resume</Button>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-3 gap-6">
                {/* Steps */}
                <div className="col-span-1">
                    <div className="bg-white rounded-xl border border-gray-200">
                        <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-gray-900">Steps</h2>
                            <span className="text-xs text-gray-400">{workflow.steps?.length ?? 0} steps</span>
                        </div>
                        {!workflow.steps?.length ? (
                            <div className="px-4 py-8 text-center text-xs text-gray-400">
                                No steps yet.
                            </div>
                        ) : (
                            <ol className="divide-y divide-gray-100">
                                {workflow.steps.map((step, i) => (
                                    <li key={step.id} className="px-4 py-3 flex items-center gap-3">
                                        <span className="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                            {i + 1}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium text-gray-900 truncate">{step.name}</p>
                                            <Badge label={step.type} variant="gray" />
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </div>
                </div>

                {/* Execution history */}
                <div className="col-span-2">
                    <div className="bg-white rounded-xl border border-gray-200">
                        <div className="px-4 py-3 border-b border-gray-100">
                            <h2 className="text-sm font-semibold text-gray-900">Execution History</h2>
                        </div>
                        {executions.length === 0 ? (
                            <div className="px-4 py-8 text-center text-xs text-gray-400">
                                No executions yet.
                            </div>
                        ) : (
                            <ul className="divide-y divide-gray-100">
                                {executions.map(exec => (
                                    <li key={exec.id} className="px-4 py-3 flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-mono text-gray-500">{exec.id.slice(0, 8)}…</p>
                                            <p className="text-xs text-gray-400 mt-0.5">
                                                {new Date(exec.started_at).toLocaleString()}
                                                {exec.finished_at && ` — ${durationLabel(exec.started_at, exec.finished_at)}`}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge label={exec.trigger_type} />
                                            <Badge label={exec.status} />
                                            {exec.status === 'failed' && (
                                                <Button
                                                    variant="secondary"
                                                    onClick={() => router.post(`/workflows/${workflow.id}/executions/${exec.id}/retry`)}
                                                >
                                                    Retry
                                                </Button>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function durationLabel(start: string, end: string): string {
    const ms = new Date(end).getTime() - new Date(start).getTime();
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(1)}s`;
}
