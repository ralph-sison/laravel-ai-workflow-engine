<?php

namespace App\Http\Middleware;

use App\Services\UsageService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    public function __construct(private readonly UsageService $usage) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant) {
            return $next($request);
        }

        if (! $this->usage->canExecute($tenant)) {
            return response()->json([
                'message' => 'Monthly execution limit reached. Please upgrade your plan.',
                'plan'    => $tenant->plan,
            ], 402);
        }

        return $next($request);
    }
}
