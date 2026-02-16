<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health', function () {
    $checks = [
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ];

    try {
        // Database check
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Exception $e) {
        $checks['database'] = 'error';
        $checks['status'] = 'error';
    }

    try {
        // Cache check
        Cache::put('health_check', true, 10);
        Cache::get('health_check');
        $checks['cache'] = 'ok';
    } catch (\Exception $e) {
        $checks['cache'] = 'error';
        $checks['status'] = 'error';
    }

    $statusCode = $checks['status'] === 'ok' ? 200 : 503;

    return response()->json($checks, $statusCode);
});
