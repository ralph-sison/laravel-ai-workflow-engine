<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWorkflowExecutionJob;
use App\Models\WebhookEndpoint;
use App\Services\WebhookSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(private readonly WebhookSignatureVerifier $verifier) {}

    /**
     * Public endpoint — no auth middleware.
     * Receives inbound webhook payload, verifies HMAC signature, fires workflow.
     */
    public function receive(Request $request, string $slug): JsonResponse
    {
        $endpoint = WebhookEndpoint::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        if (! $endpoint) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (! $endpoint->is_active) {
            return response()->json(['message' => 'Webhook endpoint is inactive.'], 422);
        }

        if (! $this->verifier->verify($request, $endpoint->secret)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $workflow = $endpoint->workflow;

        if (! $workflow->isActive()) {
            return response()->json(['message' => 'Workflow is not active.'], 422);
        }

        $execution = $workflow->executions()->create([
            'trigger_type' => 'webhook',
            'triggered_by' => null, // system-triggered
            'status'       => 'running',
            'payload'      => $request->all(),
            'context'      => $request->all(),
            'started_at'   => now(),
        ]);

        ProcessWorkflowExecutionJob::dispatch($execution->id);

        $endpoint->recordTrigger();

        return response()->json([
            'message'      => 'Workflow triggered.',
            'execution_id' => $execution->id,
        ], 202);
    }
}
