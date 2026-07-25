<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workflow\ExecuteWorkflowAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExecutionResource;
use App\Models\Execution;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExecutionController extends Controller
{
    public function index(Workflow $workflow): AnonymousResourceCollection
    {
        $this->authorize('view', $workflow);

        $executions = $workflow->executions()->paginate(20);

        return ExecutionResource::collection($executions);
    }

    public function show(Workflow $workflow, Execution $execution): JsonResponse
    {
        $this->authorize('view', $workflow);

        return response()->json([
            'data' => new ExecutionResource($execution->load('logs')),
        ]);
    }

    public function retry(Request $request, Workflow $workflow, Execution $execution, ExecuteWorkflowAction $action): JsonResponse
    {
        $this->authorize('execute', $workflow);

        if (! $execution->isFailed()) {
            return response()->json(['message' => 'Only failed executions can be retried.'], 422);
        }

        $newExecution = $action->execute($workflow, $request->user(), $execution->payload ?? []);

        return response()->json(['data' => new ExecutionResource($newExecution)], 201);
    }
}
