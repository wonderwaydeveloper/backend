<?php

namespace App\Http\Middleware;

use App\Services\RedisService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomThrottleRequests
{
    public function __construct(private RedisService $redisService) {}

    public function handle(Request $request, Closure $next, string $key, int $maxAttempts, int $decayMinutes = 1): Response
    {
        $identifier = $this->resolveRequestSignature($request);

        if (!$this->redisService->checkRateLimit($key . ':' . $identifier, $maxAttempts, $decayMinutes * 60)) {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.',
                'retry_after' => $decayMinutes * 60,
            ], 429);
        }

        return $next($request);
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->method() .
            '|' . $request->server('SERVER_NAME') .
            '|' . $request->path() .
            '|' . $request->ip()
        );
    }
}