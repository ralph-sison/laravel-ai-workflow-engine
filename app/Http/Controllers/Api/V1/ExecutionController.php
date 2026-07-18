<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExecutionResource;
use App\Models\Execution;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
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
}
