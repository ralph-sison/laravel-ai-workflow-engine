<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ConnectorController;
use App\Http\Controllers\Api\V1\ExecutionController;
use App\Http\Controllers\Api\V1\ScheduledTriggerController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
use App\Http\Controllers\Api\V1\WorkflowController;
use App\Http\Controllers\Api\V1\WorkflowStepController;
use App\Http\Middleware\EnforcePlanLimits;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public auth routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Workflows
        Route::get('workflows', [WorkflowController::class, 'index']);
        Route::post('workflows', [WorkflowController::class, 'store']);
        Route::get('workflows/{workflow}', [WorkflowController::class, 'show']);
        Route::put('workflows/{workflow}', [WorkflowController::class, 'update']);
        Route::delete('workflows/{workflow}', [WorkflowController::class, 'destroy']);
        Route::post('workflows/{workflow}/activate', [WorkflowController::class, 'activate']);
        Route::post('workflows/{workflow}/pause', [WorkflowController::class, 'pause']);

        // Workflow steps
        Route::get('workflows/{workflow}/steps', [WorkflowStepController::class, 'index']);
        Route::post('workflows/{workflow}/steps', [WorkflowStepController::class, 'store']);
        Route::put('workflows/{workflow}/steps/{step}', [WorkflowStepController::class, 'update']);
        Route::delete('workflows/{workflow}/steps/{step}', [WorkflowStepController::class, 'destroy']);
        Route::post('workflows/{workflow}/steps/reorder', [WorkflowStepController::class, 'reorder']);

        // Executions
        Route::get('workflows/{workflow}/executions', [ExecutionController::class, 'index']);
        Route::get('workflows/{workflow}/executions/{execution}', [ExecutionController::class, 'show']);
        Route::post('workflows/{workflow}/executions/{execution}/retry', [ExecutionController::class, 'retry']);

        // Connectors
        Route::get('connectors', [ConnectorController::class, 'index']);
        Route::post('connectors', [ConnectorController::class, 'store']);
        Route::get('connectors/{connector}', [ConnectorController::class, 'show']);
        Route::put('connectors/{connector}', [ConnectorController::class, 'update']);
        Route::delete('connectors/{connector}', [ConnectorController::class, 'destroy']);
        Route::post('connectors/{connector}/test', [ConnectorController::class, 'test']);

        // Webhook endpoint management (authenticated)
        Route::get('webhook-endpoints', [WebhookEndpointController::class, 'index']);
        Route::post('webhook-endpoints', [WebhookEndpointController::class, 'store']);
        Route::get('webhook-endpoints/{webhookEndpoint}', [WebhookEndpointController::class, 'show']);
        Route::put('webhook-endpoints/{webhookEndpoint}', [WebhookEndpointController::class, 'update']);
        Route::delete('webhook-endpoints/{webhookEndpoint}', [WebhookEndpointController::class, 'destroy']);
        Route::post('webhook-endpoints/{webhookEndpoint}/regenerate-secret', [WebhookEndpointController::class, 'regenerateSecret']);

        // Scheduled triggers
        Route::get('scheduled-triggers', [ScheduledTriggerController::class, 'index']);
        Route::post('scheduled-triggers', [ScheduledTriggerController::class, 'store']);
        Route::get('scheduled-triggers/{scheduledTrigger}', [ScheduledTriggerController::class, 'show']);
        Route::put('scheduled-triggers/{scheduledTrigger}', [ScheduledTriggerController::class, 'update']);
        Route::delete('scheduled-triggers/{scheduledTrigger}', [ScheduledTriggerController::class, 'destroy']);

        // Billing & subscription
        Route::get('billing', [SubscriptionController::class, 'index']);
        Route::post('billing/checkout', [SubscriptionController::class, 'checkout']);
        Route::post('billing/portal', [SubscriptionController::class, 'portal']);
        Route::post('billing/cancel', [SubscriptionController::class, 'cancel']);

        // Plan-gated: workflow execution
        Route::post('workflows/{workflow}/execute', [WorkflowController::class, 'execute'])
            ->middleware(EnforcePlanLimits::class);
    });

    // Public webhook receiver — no auth, HMAC verified
    Route::post('webhooks/{slug}', [WebhookController::class, 'receive']);
    Route::get('webhooks/{slug}', [WebhookController::class, 'receive']);

    // Stripe webhook — verified by Cashier using STRIPE_WEBHOOK_SECRET
    Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

});
