<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class RateLimitingService
{
    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function checkLimit(string $type, string $identifier, ?array $customLimits = null): array
    {
        $config = $customLimits ?? $this->getConfig($type);
        if (!$config) {
            return ['allowed' => true, 'remaining' => config('security.rate_limiting.default_remaining')];
        }

        $key = "rate_limit:{$type}:{$identifier}";
        $lockKey = "{$key}:lock";
        
        $lock = Cache::lock($lockKey, config('security.rate_limiting.lock_timeout'));
        if (!$lock->get()) {
            return ['allowed' => false, 'error' => 'Too many concurrent requests', 'retry_after' => config('security.rate_limiting.lock_timeout')];
        }

        try {
            $attempts = Cache::get($key, 0);
            
            if ($attempts >= $config['max_attempts']) {
                $this->handleExceeded($type, $identifier, $attempts, $config);
                return [
                    'allowed' => false,
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $config['window_minutes'] * 60,
                    'attempts' => $attempts,
                    'max_attempts' => $config['max_attempts']
                ];
            }

            $newAttempts = $attempts + 1;
            Cache::put($key, $newAttempts, now()->addMinutes($config['window_minutes']));
            
            return [
                'allowed' => true,
                'attempts' => $newAttempts,
                'remaining' => max(0, $config['max_attempts'] - $newAttempts)
            ];
        } finally {
            $lock->release();
        }
    }

    public function getConfig(string $type): ?array
    {
        $rateLimits = config('security.rate_limiting', []);
        
        return match($type) {
            'auth.login' => $rateLimits['auth']['login'] ?? null,
            'auth.register' => $rateLimits['auth']['register'] ?? null,
            'auth.password_reset' => $rateLimits['auth']['password_reset'] ?? null,
            'auth.email_verify' => $rateLimits['auth']['email_verify'] ?? null,
            'device.verify' => $rateLimits['auth']['device_verification'] ?? null,
            'social.follow' => $rateLimits['social']['follow'] ?? null,
            'social.block' => $rateLimits['social']['block'] ?? null,
            'social.mute' => $rateLimits['social']['mute'] ?? null,
            'search.posts' => $rateLimits['search']['posts'] ?? null,
            'search.users' => $rateLimits['search']['users'] ?? null,
            'search.hashtags' => $rateLimits['search']['hashtags'] ?? null,
            'search.all' => $rateLimits['search']['all'] ?? null,
            'messaging.send' => $rateLimits['messaging']['send'] ?? null,
            'hashtags.trending' => $rateLimits['hashtags']['trending'] ?? null,
            'trending.hashtags' => $rateLimits['trending']['hashtags'] ?? null,
            'trending.posts' => $rateLimits['trending']['posts'] ?? null,
            'trending.users' => $rateLimits['trending']['users'] ?? null,
            'trending.refresh' => $rateLimits['trending']['refresh'] ?? null,
            'polls.create' => $rateLimits['polls']['create'] ?? null,
            'polls.vote' => $rateLimits['polls']['vote'] ?? null,
            'polls.results' => $rateLimits['polls']['results'] ?? null,
            'moderation.report' => $rateLimits['moderation']['report'] ?? null,
            'mentions.search' => $rateLimits['mentions']['search'] ?? null,
            'mentions.view' => $rateLimits['mentions']['view'] ?? null,
            'realtime.default' => $rateLimits['realtime']['default'] ?? null,
            default => null
        };
    }

    private function handleExceeded(string $type, string $identifier, int $attempts, array $config): void
    {
        $this->auditService->logSecurityEvent('rate_limit_exceeded', [
            'type' => $type,
            'identifier' => $identifier,
            'attempts' => $attempts,
            'max_attempts' => $config['max_attempts']
        ]);
    }
}