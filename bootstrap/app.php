<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        then: function () {
            // Security routes removed - using Filament admin panel instead
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global Security Headers for ALL requests
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // $middleware->append(\App\Http\Middleware\SessionSecurity::class);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\UnifiedSecurityMiddleware::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\PerformanceMonitoring::class,
        ]);

        $middleware->alias([
            'security' => \App\Http\Middleware\UnifiedSecurityMiddleware::class,
            'check.reply.permission' => \App\Http\Middleware\CheckReplyPermission::class,
            'csrf.protection' => \App\Http\Middleware\CSRFProtection::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\PostNotFoundException $e) {
            return $e->render();
        });

        $exceptions->render(function (\App\Exceptions\UserNotFoundException $e) {
            return $e->render();
        });

        $exceptions->render(function (\App\Exceptions\UnauthorizedActionException $e) {
            return $e->render();
        });

        $exceptions->render(function (\App\Exceptions\ValidationException $e) {
            return $e->render();
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Resource not found',
                'message' => 'Resource not found',
            ], 404);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Please login',
                ], 401);
            }
            
            return redirect()->guest(route('filament.admin.auth.login'));
        });
    })->create();
