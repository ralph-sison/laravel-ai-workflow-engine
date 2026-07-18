<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ScheduledTriggerResource;
use App\Models\ScheduledTrigger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ScheduledTriggerController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ScheduledTrigger::class);

        $triggers = ScheduledTrigger::query()
            ->with('workflow:id,name,status')
            ->orderBy('next_run_at')
            ->get();

        return ScheduledTriggerResource::collection($triggers);
    }

    public function store(Request $request): ScheduledTriggerResource
    {
        $this->authorize('create', ScheduledTrigger::class);

        $data = $request->validate([
            'workflow_id'     => ['required', 'uuid', Rule::exists('workflows', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'cron_expression' => ['required', 'string', 'max:100'],
            'timezone'        => ['sometimes', 'string', 'timezone'],
            'is_active'       => ['boolean'],
        ]);

        $trigger = ScheduledTrigger::make([
            'tenant_id'       => $request->user()->tenant_id,
            'workflow_id'     => $data['workflow_id'],
            'cron_expression' => $data['cron_expression'],
            'timezone'        => $data['timezone'] ?? 'UTC',
            'is_active'       => $data['is_active'] ?? true,
        ]);

        $trigger->next_run_at = $trigger->calculateNextRunAt();
        $trigger->save();

        return new ScheduledTriggerResource($trigger);
    }

    public function show(ScheduledTrigger $scheduledTrigger): ScheduledTriggerResource
    {
        $this->authorize('view', $scheduledTrigger);

        return new ScheduledTriggerResource($scheduledTrigger->load('workflow:id,name,status'));
    }

    public function update(Request $request, ScheduledTrigger $scheduledTrigger): ScheduledTriggerResource
    {
        $this->authorize('update', $scheduledTrigger);

        $data = $request->validate([
            'cron_expression' => ['sometimes', 'string', 'max:100'],
            'timezone'        => ['sometimes', 'string', 'timezone'],
            'is_active'       => ['boolean'],
        ]);

        $scheduledTrigger->fill($data);

        if (isset($data['cron_expression']) || isset($data['timezone'])) {
            $scheduledTrigger->next_run_at = $scheduledTrigger->calculateNextRunAt();
        }

        $scheduledTrigger->save();

        return new ScheduledTriggerResource($scheduledTrigger);
    }

    public function destroy(ScheduledTrigger $scheduledTrigger): JsonResponse
    {
        $this->authorize('delete', $scheduledTrigger);

        $scheduledTrigger->delete();

        return response()->json(null, 204);
    }
}
