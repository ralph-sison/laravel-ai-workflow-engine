import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import { PageProps, Workflow } from '@/types';

interface Props extends PageProps {
    workflows: Workflow[];
}

export default function WorkflowsIndex({ workflows }: Props) {
    function handleExecute(workflow: Workflow) {
        router.post(`/workflows/${workflow.id}/execute`);
    }

    return (
        <AppLayout>
            <Head title="Workflows" />

            <div className="flex items-center justify-between mb-8">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Workflows</h1>
                    <p className="mt-1 text-sm text-gray-500">{workflows.length} workflow{workflows.length !== 1 ? 's' : ''}</p>
                </div>
                <Link href="/workflows/create">
                    <Button>New Workflow</Button>
                </Link>
            </div>

            {workflows.length === 0 ? (
                <div className="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
                    <p className="text-gray-500 text-sm">No workflows yet.</p>
                    <Link href="/workflows/create">
                        <Button className="mt-4">Create your first workflow</Button>
                    </Link>
                </div>
            ) : (
                <div className="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                    {workflows.map(workflow => (
                        <div key={workflow.id} className="px-6 py-4 flex items-center justify-between">
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-3">
                                    <Link
                                        href={`/workflows/${workflow.id}`}
                                        className="text-sm font-semibold text-gray-900 hover:text-indigo-600 truncate"
                                    >
                                        {workflow.name}
                                    </Link>
                                    <Badge label={workflow.status} />
                                    <Badge label={workflow.trigger_type} />
                                </div>
                                {workflow.description && (
                                    <p className="mt-0.5 text-xs text-gray-400 truncate">{workflow.description}</p>
                                )}
                                {workflow.last_run_at && (
                                    <p className="mt-0.5 text-xs text-gray-400">
                                        Last run {new Date(workflow.last_run_at).toLocaleString()}
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center gap-2 ml-4">
                                {workflow.status === 'active' && (
                                    <Button
                                        variant="secondary"
                                        onClick={() => handleExecute(workflow)}
                                    >
                                        Run
                                    </Button>
                                )}
                                <Link href={`/workflows/${workflow.id}`}>
                                    <Button variant="secondary">View</Button>
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
