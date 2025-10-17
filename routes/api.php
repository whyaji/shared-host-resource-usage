<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResourceUsageController;

Route::get('/status', function (Request $request) {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now(),
        'version' => app()->version()
    ]);
})->middleware(['api.key', 'throttle:60,1']);

// Resource Usage Monitoring Routes
Route::prefix('resource-usage')->middleware(['api.key', 'throttle:30,1'])->group(function () {
    // Trigger resource usage check (async by default)
    Route::post('/check', [ResourceUsageController::class, 'check']);

    // Trigger resource usage check synchronously
    Route::post('/check-sync', [ResourceUsageController::class, 'check'])->defaults('sync', true);

    // Get latest resource usage data
    Route::get('/latest', [ResourceUsageController::class, 'latest']);

    // Get resource usage history
    Route::get('/history', [ResourceUsageController::class, 'history']);

    // Get resource usage statistics
    Route::get('/stats', [ResourceUsageController::class, 'stats']);
});
