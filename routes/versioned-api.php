<?php

use Illuminate\Support\Facades\Route;

// API v1 Routes (Current)
Route::prefix('v1')->middleware(['api.version:v1'])->group(function () {
    require __DIR__ . '/api.php';
});

// API v2 Routes (Enhanced)
Route::prefix('v2')->middleware(['api.version:v2'])->group(function () {
    
    // Enhanced Posts API
    Route::middleware(['auth:sanctum', 'spam.detection'])->group(function () {
        
        // Posts with enhanced features
        Route::get('/posts', [App\Http\Controllers\Api\V2\PostController::class, 'index']);
        Route::post('/posts', [App\Http\Controllers\Api\V2\PostController::class, 'store']);
        Route::get('/posts/{post}', [App\Http\Controllers\Api\V2\PostController::class, 'show']);
        
        // Enhanced timeline with algorithms
        Route::get('/timeline/algorithmic', [App\Http\Controllers\Api\V2\TimelineController::class, 'algorithmic']);
        Route::get('/timeline/chronological', [App\Http\Controllers\Api\V2\TimelineController::class, 'chronological']);
        
        // Batch operations
        Route::post('/posts/batch', [App\Http\Controllers\Api\V2\PostController::class, 'batchCreate']);
        Route::delete('/posts/batch', [App\Http\Controllers\Api\V2\PostController::class, 'batchDelete']);
        
    });
});

// Health check for all versions
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'version' => 'multi-version',
        'supported_versions' => ['v1', 'v2'],
        'timestamp' => now()->toISOString(),
    ]);
});