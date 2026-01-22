<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public auth endpoints
Route::post('/auth/login', [AuthController::class, 'login'])->middleware([\App\Http\Middleware\NotifyOnThrottle::class, 'throttle:api.login']);

Route::middleware('auth:sanctum')->group(function () {

    // Auth logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Single Email Verification
    Route::post('/verify/single', [VerificationController::class, 'verifySingle']);

    // Bulk Verification (JSON array)
    Route::post('/verify/bulk', [VerificationController::class, 'verifyBulk']);

    // File Upload Verification
    Route::post('/verify/file', [VerificationController::class, 'verifyFile']);

    // Check Job Status
    Route::get('/verify/status/{jobId}', [VerificationController::class, 'status']);

    // Get Results
    Route::get('/verify/results/{jobId}', [VerificationController::class, 'results']);

    // Export Results
    Route::post('/verify/export/{jobId}', [VerificationController::class, 'export']);

    // Credits & Balance
    Route::get('/credits/balance', [CreditController::class, 'balance']);
    Route::get('/credits/history', [CreditController::class, 'history']);

    // Blacklist Management
    Route::apiResource('blacklist', BlacklistController::class);

    // Integrations
    Route::post('/integrations/google-sheets/sync', [IntegrationController::class, 'syncGoogleSheets']);
    Route::post('/integrations/hubspot/sync', [IntegrationController::class, 'syncHubspot']);
    Route::post('/webhooks/register', [WebhookController::class, 'register']);
});

