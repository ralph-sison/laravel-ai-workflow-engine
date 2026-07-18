<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WorkflowStepResource;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkflowStepController extends Controller
{
    public function index(Workflow $workflow): AnonymousResourceCollection
    {
        $this->authorize('view', $workflow);

        return WorkflowStepResource::collection($workflow->steps);
    }

    public function store(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'type'            => ['required', 'string', 'in:ai,http,transform,notification,condition,delay'],
            'config'          => ['nullable', 'array'],
            'on_error'        => ['sometimes', 'string', 'in:stop,continue,retry'],
            'retry_limit'     => ['sometimes', 'integer', 'min:0', 'max:10'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:300'],
        ]);

        $order = $workflow->steps()->max('order') + 1;

        $step = $workflow->steps()->create([...$validated, 'order' => $order]);

        return response()->json(['data' => new WorkflowStepResource($step)], 201);
    }

    public function update(Request $request, Workflow $workflow, WorkflowStep $step): JsonResponse
    {
        $this->authorize('update', $workflow);

        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:255'],
            'type'            => ['sometimes', 'string', 'in:ai,http,transform,notification,condition,delay'],
            'config'          => ['nullable', 'array'],
            'on_error'        => ['sometimes', 'string', 'in:stop,continue,retry'],
            'retry_limit'     => ['sometimes', 'integer', 'min:0', 'max:10'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:300'],
        ]);

        $step->update($validated);

        return response()->json(['data' => new WorkflowStepResource($step)]);
    }

    public function destroy(Workflow $workflow, WorkflowStep $step): JsonResponse
    {
        $this->authorize('update', $workflow);

        $step->delete();

        return response()->json(['message' => 'Step deleted.']);
    }

    public function reorder(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $validated = $request->validate([
            'steps'          => ['required', 'array'],
            'steps.*.id'     => ['required', 'uuid'],
            'steps.*.order'  => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['steps'] as $item) {
            $workflow->steps()->where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['data' => WorkflowStepResource::collection($workflow->steps()->get())]);
    }
}
