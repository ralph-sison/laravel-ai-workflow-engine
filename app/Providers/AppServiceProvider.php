<?php

namespace App\Providers;

use App\Models\Workflow;
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
    }
}
