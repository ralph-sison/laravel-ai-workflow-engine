<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WebhookEndpointResource;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WebhookEndpointController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WebhookEndpoint::class);

        $endpoints = WebhookEndpoint::query()
            ->with('workflow:id,name,status')
            ->orderBy('created_at', 'desc')
            ->get();

        return WebhookEndpointResource::collection($endpoints);
    }

    public function store(Request $request): WebhookEndpointResource
    {
        $this->authorize('create', WebhookEndpoint::class);

        $data = $request->validate([
            'workflow_id' => ['required', 'uuid', Rule::exists('workflows', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'method'      => ['sometimes', 'string', 'in:POST,GET'],
            'is_active'   => ['boolean'],
        ]);

        $endpoint = WebhookEndpoint::create([
            'tenant_id'   => $request->user()->tenant_id,
            'workflow_id' => $data['workflow_id'],
            'slug'        => WebhookEndpoint::generateSlug(),
            'secret'      => WebhookEndpoint::generateSecret(),
            'method'      => $data['method'] ?? 'POST',
            'is_active'   => $data['is_active'] ?? true,
        ]);

        // Return the secret only on creation — never exposed again
        return (new WebhookEndpointResource($endpoint))
            ->additional(['meta' => ['secret' => $endpoint->secret]]);
    }

    public function show(WebhookEndpoint $webhookEndpoint): WebhookEndpointResource
    {
        $this->authorize('view', $webhookEndpoint);

        return new WebhookEndpointResource($webhookEndpoint->load('workflow:id,name,status'));
    }

    public function update(Request $request, WebhookEndpoint $webhookEndpoint): WebhookEndpointResource
    {
        $this->authorize('update', $webhookEndpoint);

        $data = $request->validate([
            'is_active' => ['boolean'],
            'method'    => ['string', 'in:POST,GET'],
        ]);

        $webhookEndpoint->update($data);

        return new WebhookEndpointResource($webhookEndpoint);
    }

    public function destroy(WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->authorize('delete', $webhookEndpoint);

        $webhookEndpoint->delete();

        return response()->json(null, 204);
    }

    public function regenerateSecret(WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->authorize('update', $webhookEndpoint);

        $newSecret = WebhookEndpoint::generateSecret();
        $webhookEndpoint->update(['secret' => $newSecret]);

        // Only time the new secret is visible
        return response()->json([
            'message' => 'Secret regenerated.',
            'secret'  => $newSecret,
        ]);
    }
}
