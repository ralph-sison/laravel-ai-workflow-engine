<?php

namespace App\Http\Controllers\Web;

use App\Actions\Workflow\ExecuteWorkflowAction;
use App\Http\Controllers\Controller;
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function index(Request $request): Response
    {
        $workflows = Workflow::with('steps')
            ->latest()
            ->get();

        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Workflows/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'trigger_type' => ['required', 'in:manual,webhook,schedule'],
        ]);

        $workflow = Workflow::create([
            'tenant_id'    => $request->user()->tenant_id,
            'created_by'   => $request->user()->id,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'trigger_type' => $data['trigger_type'],
        ]);

        return redirect("/workflows/{$workflow->id}");
    }

    public function show(Workflow $workflow): Response
    {
        $workflow->load('steps');

        $executions = $workflow->executions()->limit(20)->get();

        return Inertia::render('Workflows/Show', [
            'workflow'   => $workflow,
            'executions' => $executions,
        ]);
    }

    public function activate(Workflow $workflow): RedirectResponse
    {
        $workflow->update(['status' => 'active']);
        return back();
    }

    public function pause(Workflow $workflow): RedirectResponse
    {
        $workflow->update(['status' => 'paused']);
        return back();
    }

    public function execute(Request $request, Workflow $workflow, ExecuteWorkflowAction $action): RedirectResponse
    {
        $action->execute($workflow, $request->user());
        return back()->with('success', 'Workflow triggered.');
    }
}
