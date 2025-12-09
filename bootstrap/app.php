<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // فقط middlewareهای ضروری برای API
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // فقط middlewareهای سفارشی که واقعاً وجود دارند
        $middleware->alias([
            'underage.access' => \App\Http\Middleware\CheckUnderageAccess::class,
            'track.online' => \App\Http\Middleware\TrackOnlineUser::class,
            'custom.throttle' => \App\Http\Middleware\CustomThrottleRequests::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    
        // اضافه کردن middleware rate limiter برای API
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // هندل کردن exceptionهای API
        $exceptions->render(function (Illuminate\Auth\AuthenticationException $e, Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'authentication_required'
                ], 401);
            }
        });

        $exceptions->render(function (Illuminate\Validation\ValidationException $e, Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();