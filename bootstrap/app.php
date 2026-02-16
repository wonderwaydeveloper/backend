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
            Route::middleware('api')
                ->prefix('broadcasting')
                ->group(base_path('routes/broadcasting.php'));
            
            Route::middleware('api')
                ->group(base_path('routes/health.php'));
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global Security Headers for ALL requests
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            // UnifiedSecurityMiddleware removed from global - applied per route
            \App\Http\Middleware\CSRFProtection::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\PerformanceMonitoring::class,
            \App\Http\Middleware\UpdateLastSeen::class,
            \App\Http\Middleware\CheckUserModeration::class,
        ]);

        $middleware->alias([
            'security' => \App\Http\Middleware\UnifiedSecurityMiddleware::class,
            'check.reply.permission' => \App\Http\Middleware\CheckReplyPermission::class,
            'csrf.protection' => \App\Http\Middleware\CSRFProtection::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'captcha' => \App\Http\Middleware\CaptchaMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'check.moderation' => \App\Http\Middleware\CheckUserModeration::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
            'check.feature' => \App\Http\Middleware\CheckFeatureAccess::class,
            'role.ratelimit' => \App\Http\Middleware\RoleBasedRateLimit::class,
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
        });
    })->create();
