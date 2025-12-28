<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SecurityDashboardController;

Route::prefix('admin/security')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [SecurityDashboardController::class, 'dashboard']);
    Route::get('/threats', [SecurityDashboardController::class, 'threats']);
    Route::get('/blocked-ips', [SecurityDashboardController::class, 'blockedIps']);
    Route::post('/unblock-ip', [SecurityDashboardController::class, 'unblockIp']);
});