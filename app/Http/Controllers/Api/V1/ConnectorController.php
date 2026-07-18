<?php

namespace App\Http\Controllers\Api\V1;

use App\AI\AiProviderFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Connector\StoreConnectorRequest;
use App\Http\Requests\Api\V1\Connector\UpdateConnectorRequest;
use App\Http\Resources\Api\V1\ConnectorResource;
use App\Models\Connector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConnectorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Connector::class);

        $connectors = Connector::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ConnectorResource::collection($connectors);
    }

    public function store(StoreConnectorRequest $request): ConnectorResource
    {
        $this->authorize('create', Connector::class);

        $connector = Connector::create([
            'tenant_id'   => $request->user()->tenant_id,
            'name'        => $request->name,
            'type'        => $request->type,
            'credentials' => $request->credentials,
            'meta'        => $request->meta,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return new ConnectorResource($connector);
    }

    public function show(Connector $connector): ConnectorResource
    {
        $this->authorize('view', $connector);

        return new ConnectorResource($connector);
    }

    public function update(UpdateConnectorRequest $request, Connector $connector): ConnectorResource
    {
        $this->authorize('update', $connector);

        $connector->update($request->validated());

        return new ConnectorResource($connector);
    }

    public function destroy(Connector $connector): JsonResponse
    {
        $this->authorize('delete', $connector);

        $connector->delete();

        return response()->json(null, 204);
    }

    public function test(Connector $connector): JsonResponse
    {
        $this->authorize('test', $connector);

        try {
            $factory  = new AiProviderFactory();
            $provider = $factory->make(['connector_id' => $connector->id]);

            $response = $provider->complete(
                [['role' => 'user', 'content' => 'Reply with the single word: connected']],
                ['max_tokens' => 10]
            );

            return response()->json([
                'success'  => true,
                'provider' => $response->provider,
                'model'    => $response->model,
                'response' => $response->content,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }
}
