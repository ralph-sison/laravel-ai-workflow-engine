import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Button from '@/Components/Button';
import { PageProps } from '@/types';

export default function WorkflowCreate(_props: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        trigger_type: 'manual' as 'manual' | 'webhook' | 'schedule',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/workflows');
    }

    return (
        <AppLayout>
            <Head title="New Workflow" />

            <div className="max-w-2xl">
                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-gray-900">New Workflow</h1>
                    <p className="mt-1 text-sm text-gray-500">Define the basics — you can add steps after creating.</p>
                </div>

                <form onSubmit={submit} className="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Name <span className="text-red-500">*</span></label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="Email → AI Summary → Slack"
                            required
                            autoFocus
                        />
                        {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            value={data.description}
                            onChange={e => setData('description', e.target.value)}
                            rows={3}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="What does this workflow do?"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Trigger type</label>
                        <div className="grid grid-cols-3 gap-3">
                            {(['manual', 'webhook', 'schedule'] as const).map(type => (
                                <label
                                    key={type}
                                    className={`flex items-center justify-center px-4 py-3 rounded-lg border cursor-pointer text-sm font-medium transition-colors ${
                                        data.trigger_type === type
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="trigger_type"
                                        value={type}
                                        checked={data.trigger_type === type}
                                        onChange={() => setData('trigger_type', type)}
                                        className="sr-only"
                                    />
                                    {type.charAt(0).toUpperCase() + type.slice(1)}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="flex items-center gap-3 pt-2">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating…' : 'Create workflow'}
                        </Button>
                        <Button type="button" variant="secondary" onClick={() => history.back()}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
