<?php

namespace App\Providers;

use App\Models\Connector;
use App\Models\ScheduledTrigger;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use App\Policies\ConnectorPolicy;
use App\Policies\ScheduledTriggerPolicy;
use App\Policies\WebhookEndpointPolicy;
use App\Policies\WorkflowPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Billing is per-tenant, not per-user
        Cashier::useCustomerModel(\App\Models\Tenant::class);

        // 60 req/min per authenticated tenant; 10/min for unauthenticated (guests, public webhooks)
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->tenant_id)
                : Limit::perMinute(10)->by($request->ip());
        });

        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(Connector::class, ConnectorPolicy::class);
        Gate::policy(WebhookEndpoint::class, WebhookEndpointPolicy::class);
        Gate::policy(ScheduledTrigger::class, ScheduledTriggerPolicy::class);
    }
}
