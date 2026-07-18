<?php

namespace App\Providers;

use App\Models\Connector;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use App\Policies\ConnectorPolicy;
use App\Policies\WebhookEndpointPolicy;
use App\Policies\WorkflowPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(Connector::class, ConnectorPolicy::class);
        Gate::policy(WebhookEndpoint::class, WebhookEndpointPolicy::class);
    }
}
