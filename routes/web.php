<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\WorkflowController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Redirect root to dashboard or login
Route::get('/', fn () => redirect('/dashboard'));

// Authenticated web routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Workflows
    Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
    Route::get('/workflows/create', [WorkflowController::class, 'create'])->name('workflows.create');
    Route::post('/workflows', [WorkflowController::class, 'store'])->name('workflows.store');
    Route::get('/workflows/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show');
    Route::post('/workflows/{workflow}/activate', [WorkflowController::class, 'activate'])->name('workflows.activate');
    Route::post('/workflows/{workflow}/pause', [WorkflowController::class, 'pause'])->name('workflows.pause');
    Route::post('/workflows/{workflow}/execute', [WorkflowController::class, 'execute'])->name('workflows.execute');
});
