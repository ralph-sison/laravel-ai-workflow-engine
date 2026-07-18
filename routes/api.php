<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ExecutionController;
use App\Http\Controllers\Api\V1\WorkflowController;
use App\Http\Controllers\Api\V1\WorkflowStepController;
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
        Route::post('workflows/{workflow}/execute', [WorkflowController::class, 'execute']);

        // Workflow steps
        Route::get('workflows/{workflow}/steps', [WorkflowStepController::class, 'index']);
        Route::post('workflows/{workflow}/steps', [WorkflowStepController::class, 'store']);
        Route::put('workflows/{workflow}/steps/{step}', [WorkflowStepController::class, 'update']);
        Route::delete('workflows/{workflow}/steps/{step}', [WorkflowStepController::class, 'destroy']);
        Route::post('workflows/{workflow}/steps/reorder', [WorkflowStepController::class, 'reorder']);

        // Executions
        Route::get('workflows/{workflow}/executions', [ExecutionController::class, 'index']);
        Route::get('workflows/{workflow}/executions/{execution}', [ExecutionController::class, 'show']);
    });

});
