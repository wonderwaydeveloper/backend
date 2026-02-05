<?php

namespace App\Services;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class TokenManagementService
{
    public function __construct(
        private SessionTimeoutService $timeoutService
    ) {}
    /**
     * Clean up expired tokens and sessions
     */
    public function cleanupExpiredTokens(): array
    {
        $stats = [
            'access_tokens_deleted' => 0,
            'device_tokens_deleted' => 0,
            'password_reset_tokens_deleted' => 0,
            'verification_tokens_deleted' => 0
        ];

        // Clean expired access tokens
        $stats['access_tokens_deleted'] = PersonalAccessToken::where('expires_at', '<', now())->delete();

        // Clean inactive device tokens
        $inactivityDays = $this->timeoutService->getDeviceTokenInactivityLimit();
        $stats['device_tokens_deleted'] = DeviceToken::where('last_used_at', '<', now()->subDays($inactivityDays))
            ->orWhere('active', false)
            ->delete();

        // Clean expired password reset tokens
        $resetExpiry = $this->timeoutService->getPasswordResetExpiry();
        $stats['password_reset_tokens_deleted'] = DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subMinutes($resetExpiry))
            ->delete();

        // Clean expired email verification tokens
        $verificationExpiry = $this->timeoutService->getEmailVerificationExpiry();
        $stats['verification_tokens_deleted'] = User::whereNotNull('email_verification_token')
            ->whereNull('email_verified_at')
            ->where('updated_at', '<', now()->subHours($verificationExpiry))
            ->update([
                'email_verification_token' => null
            ]);

        return $stats;
    }

    /**
     * Enforce concurrent session limits
     */
    public function enforceConcurrentSessionLimits(User $user): void
    {
        $maxSessions = $this->timeoutService->getConcurrentSessionLimit();
        
        $activeSessions = $user->tokens()
            ->where('expires_at', '>', now())
            ->orderBy('last_used_at', 'desc')
            ->get();

        if ($activeSessions->count() > $maxSessions) {
            // Keep the most recent sessions, delete the rest
            $sessionsToDelete = $activeSessions->skip($maxSessions);
            
            foreach ($sessionsToDelete as $session) {
                $session->delete();
            }
        }
    }

    /**
     * Revoke all user sessions except current
     */
    public function revokeAllUserSessions(User $user, ?string $exceptTokenId = null): int
    {
        $query = $user->tokens();
        
        if ($exceptTokenId) {
            $query->where('id', '!=', $exceptTokenId);
        }
        
        return $query->delete();
    }

    /**
     * Update device token activity
     */
    public function updateDeviceActivity(User $user, string $fingerprint, array $deviceInfo): void
    {
        DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'fingerprint' => $fingerprint
            ],
            [
                'last_used_at' => now(),
                'active' => true,
                'device_type' => $deviceInfo['device_type'] ?? 'unknown',
                'device_name' => $deviceInfo['device_name'] ?? 'Unknown Device',
                'browser' => $deviceInfo['browser'] ?? null,
                'os' => $deviceInfo['os'] ?? null,
                'ip_address' => $deviceInfo['ip_address'] ?? null,
                'user_agent' => $deviceInfo['user_agent'] ?? null
            ]
        );
    }

    /**
     * Get user's active sessions
     */
    public function getUserActiveSessions(User $user): array
    {
        // Get active sessions using centralized timeout
        $sessionTimeout = $this->timeoutService->getSessionTimeout();
        $deviceInactivity = $this->timeoutService->getDeviceTokenInactivityLimit();
        
        $tokens = $user->tokens()
            ->where('expires_at', '>', now())
            ->orderBy('last_used_at', 'desc')
            ->get();

        $devices = $user->devices()
            ->where('active', true)
            ->where('last_used_at', '>', now()->subDays($deviceInactivity))
            ->get();

        return [
            'active_tokens' => $tokens->count(),
            'active_devices' => $devices->count(),
            'sessions' => $tokens->map(function ($token) use ($devices) {
                $device = $devices->firstWhere('fingerprint', $token->name);
                
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'device_info' => $device ? [
                        'device_name' => $device->device_name,
                        'browser' => $device->browser,
                        'os' => $device->os,
                        'ip_address' => $device->ip_address
                    ] : null
                ];
            })
        ];
    }

    /**
     * Revoke specific session
     */
    public function revokeSession(User $user, string $tokenId): bool
    {
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }

    /**
     * Check if token needs refresh
     */
    public function shouldRefreshToken(PersonalAccessToken $token): bool
    {
        return $this->timeoutService->shouldRefreshToken($token);
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(User $user, PersonalAccessToken $currentToken): string
    {
        // Delete current token
        $currentToken->delete();
        
        // Create new token with centralized expiry
        $newToken = $this->timeoutService->createTokenWithExpiry(
            $user,
            $currentToken->name,
            $currentToken->abilities
        );
        
        return $newToken->plainTextToken;
    }
}