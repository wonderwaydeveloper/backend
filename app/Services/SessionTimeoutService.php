<?php

namespace App\Services;

class SessionTimeoutService
{
    public function getAccessTokenLifetime(): int
    {
        return config('authentication.tokens.access_lifetime_seconds', 3600);
    }

    public function getRefreshTokenLifetime(): int
    {
        return config('authentication.tokens.refresh_lifetime_seconds', 604800);
    }

    public function getRememberTokenLifetime(): int
    {
        return config('authentication.tokens.remember_lifetime_seconds', 1209600);
    }

    public function getSessionTimeout(): int
    {
        return config('authentication.session.timeout_seconds', 7200);
    }

    public function getConcurrentSessionLimit(): int
    {
        return config('authentication.session.concurrent_limit', 3);
    }

    public function getAutoRefreshThreshold(): int
    {
        return config('authentication.tokens.auto_refresh_threshold', 300);
    }

    public function getPasswordResetExpiry(): int
    {
        return config('authentication.password.reset.expire_minutes', 15); // minutes
    }

    public function getEmailVerificationExpiry(): int
    {
        return config('authentication.email.verification_expire_minutes', 15) / 60; // convert minutes to hours
    }

    public function getDeviceTokenInactivityLimit(): int
    {
        return config('authentication.device.max_inactivity_days', 30); // days
    }

    public function getVerificationCodeExpiry(): int
    {
        return config('authentication.email.verification_expire_minutes', 15); // minutes
    }

    public function getTwoFactorCodeExpiry(): int
    {
        return config('authentication.tokens.auto_refresh_threshold', 300) / 60; // minutes
    }

    public function getDeviceVerificationExpiry(): int
    {
        return config('authentication.email.verification_expire_minutes', 15); // minutes
    }

    public function createTokenWithExpiry(\App\Models\User $user, string $name, array $abilities = ['*']): \Laravel\Sanctum\NewAccessToken
    {
        return $user->createToken(
            $name,
            $abilities,
            now()->addSeconds($this->getAccessTokenLifetime())
        );
    }

    public function shouldRefreshToken(\Laravel\Sanctum\PersonalAccessToken $token): bool
    {
        return $token->expires_at && 
               $token->expires_at->diffInSeconds(now()) <= $this->getAutoRefreshThreshold();
    }
}