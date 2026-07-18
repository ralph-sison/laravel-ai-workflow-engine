<?php

use App\Console\Commands\TriggerScheduledWorkflowsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fire scheduled workflow triggers every minute
Schedule::command(TriggerScheduledWorkflowsCommand::class)->everyMinute()->withoutOverlapping();
