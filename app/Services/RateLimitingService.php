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
            return ['allowed' => true, 'remaining' => 999];
        }

        $key = "rate_limit:{$type}:{$identifier}";
        $lockKey = "{$key}:lock";
        
        $lock = Cache::lock($lockKey, 5);
        if (!$lock->get()) {
            return ['allowed' => false, 'error' => 'Too many concurrent requests', 'retry_after' => 5];
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

            Cache::increment($key, 1, now()->addMinutes($config['window_minutes']));
            
            return [
                'allowed' => true,
                'attempts' => $attempts + 1,
                'remaining' => max(0, $config['max_attempts'] - $attempts - 1)
            ];
        } finally {
            $lock->release();
        }
    }

    public function getConfig(string $type): ?array
    {
        $rateLimits = config('authentication.rate_limiting', []);
        
        return match($type) {
            'auth.login' => $rateLimits['login'] ?? ['max_attempts' => 5, 'window_minutes' => 15],
            'auth.register' => $rateLimits['register'] ?? ['max_attempts' => 3, 'window_minutes' => 60],
            'auth.password_reset' => $rateLimits['password_reset'] ?? ['max_attempts' => 2, 'window_minutes' => 60],
            'device.verify' => $rateLimits['device_verify'] ?? ['max_attempts' => 3, 'window_minutes' => 1],
            'email.verification' => $rateLimits['email_verification'] ?? ['max_attempts' => 3, 'window_minutes' => 60],
            'auth.reset_verify' => $rateLimits['reset_verify'] ?? ['max_attempts' => 5, 'window_minutes' => 15],
            'auth.me' => $rateLimits['me'] ?? ['max_attempts' => 30, 'window_minutes' => 1],
            'auth.resend' => $rateLimits['resend'] ?? ['max_attempts' => 5, 'window_minutes' => 60],
            'auth.reset_resend' => $rateLimits['reset_resend'] ?? ['max_attempts' => 5, 'window_minutes' => 60],
            'auth.phone_login' => $rateLimits['phone_login'] ?? ['max_attempts' => 5, 'window_minutes' => 60],
            'auth.phone_resend' => $rateLimits['phone_resend'] ?? ['max_attempts' => 5, 'window_minutes' => 60],
            'auth.social' => $rateLimits['social'] ?? ['max_attempts' => 10, 'window_minutes' => 5],
            'device.resend' => $rateLimits['device_resend'] ?? ['max_attempts' => 5, 'window_minutes' => 1],
            'email.password_reset' => $rateLimits['email_password_reset'] ?? ['max_attempts' => 2, 'window_minutes' => 60],
            'email.device_verification' => $rateLimits['email_device_verification'] ?? ['max_attempts' => 3, 'window_minutes' => 60],
            'email.resend' => $rateLimits['email_resend'] ?? ['max_attempts' => 3, 'window_minutes' => 60],
            'api.general' => $rateLimits['api_general'] ?? ['max_attempts' => 60, 'window_minutes' => 1],
            'api.login' => $rateLimits['api_login'] ?? ['max_attempts' => 5, 'window_minutes' => 15],
            'api.register' => $rateLimits['api_register'] ?? ['max_attempts' => 3, 'window_minutes' => 60],
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