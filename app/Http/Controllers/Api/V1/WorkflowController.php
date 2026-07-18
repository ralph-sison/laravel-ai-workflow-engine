<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workflow\ExecuteWorkflowAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExecutionResource;
use App\Http\Resources\Api\V1\WorkflowResource;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkflowController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Workflow::class);

        $workflows = Workflow::withCount('steps')
            ->latest()
            ->paginate(20);

        return WorkflowResource::collection($workflows);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'trigger_type'   => ['sometimes', 'string', 'in:manual,webhook,schedule,email'],
            'trigger_config' => ['nullable', 'array'],
        ]);

        $workflow = Workflow::create([
            ...$validated,
            'tenant_id'  => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
            'status'     => 'draft',
        ]);

        return response()->json(['data' => new WorkflowResource($workflow)], 201);
    }

    public function show(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        return response()->json([
            'data' => new WorkflowResource($workflow->load('steps')),
        ]);
    }

    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $validated = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'trigger_type'   => ['sometimes', 'string', 'in:manual,webhook,schedule,email'],
            'trigger_config' => ['nullable', 'array'],
        ]);

        $workflow->update($validated);

        return response()->json(['data' => new WorkflowResource($workflow)]);
    }

    public function destroy(Workflow $workflow): JsonResponse
    {
        $this->authorize('delete', $workflow);

        $workflow->delete();

        return response()->json(['message' => 'Workflow deleted.']);
    }

    public function activate(Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $workflow->update(['status' => 'active']);

        return response()->json(['data' => new WorkflowResource($workflow)]);
    }

    public function pause(Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $workflow->update(['status' => 'paused']);

        return response()->json(['data' => new WorkflowResource($workflow)]);
    }

    public function execute(Request $request, Workflow $workflow, ExecuteWorkflowAction $action): JsonResponse
    {
        $this->authorize('execute', $workflow);

        $execution = $action->execute($workflow, $request->user(), $request->all());

        return response()->json(['data' => new ExecutionResource($execution->load('logs'))], 201);
    }
}
