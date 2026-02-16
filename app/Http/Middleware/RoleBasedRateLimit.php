<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionLimitService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedRateLimit
{
    public function __construct(
        private SubscriptionLimitService $limitService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $limit = $this->limitService->getRateLimit($user);
        $key = 'rate-limit:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'message' => 'Too many requests',
                'retry_after' => RateLimiter::availableIn($key),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, config('security.rate_limiting.default_window'));
        return $next($request);
    }
}
