<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SecureJWTService
{
    private const ACCESS_TOKEN_TTL = 3600; // 1 hour
    private const REFRESH_TOKEN_TTL = 604800; // 7 days
    private const MAX_DEVICES_PER_USER = 5;
    
    private string $secretKey;
    private string $algorithm;
    
    public function __construct()
    {
        $this->secretKey = config('app.jwt_secret');
        $this->algorithm = 'HS256';
    }
    
    public function generateTokenPair(int $userId, array $permissions = [], string $deviceInfo = null): array
    {
        $deviceFingerprint = $this->generateDeviceFingerprint($deviceInfo);
        $sessionId = Str::uuid()->toString();
        
        // Generate access token
        $accessToken = $this->generateAccessToken($userId, $permissions, $sessionId, $deviceFingerprint);
        
        // Generate refresh token
        $refreshToken = $this->generateRefreshToken($userId, $sessionId, $deviceFingerprint);
        
        // Store session info
        $this->storeSession($userId, $sessionId, $deviceFingerprint, $deviceInfo);
        
        // Cleanup old sessions if needed
        $this->cleanupOldSessions($userId);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'session_id' => $sessionId
        ];
    }
    
    private function generateAccessToken(int $userId, array $permissions, string $sessionId, string $deviceFingerprint): string
    {
        $now = time();
        $jti = Str::uuid()->toString();
        
        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'exp' => $now + self::ACCESS_TOKEN_TTL,
            'nbf' => $now,
            'sub' => $userId,
            'jti' => $jti,
            'session_id' => $sessionId,
            'device_fp' => $deviceFingerprint,
            'permissions' => $permissions,
            'type' => 'access'
        ];
        
        // Store JTI for blacklisting capability
        Redis::setex("jwt_jti:{$jti}", self::ACCESS_TOKEN_TTL, json_encode([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'created_at' => $now
        ]));
        
        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }
    
    private function generateRefreshToken(int $userId, string $sessionId, string $deviceFingerprint): string
    {
        $now = time();
        $jti = Str::uuid()->toString();
        
        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'exp' => $now + self::REFRESH_TOKEN_TTL,
            'nbf' => $now,
            'sub' => $userId,
            'jti' => $jti,
            'session_id' => $sessionId,
            'device_fp' => $deviceFingerprint,
            'type' => 'refresh'
        ];
        
        // Store refresh token
        Redis::setex("refresh_token:{$jti}", self::REFRESH_TOKEN_TTL, json_encode([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'device_fp' => $deviceFingerprint,
            'created_at' => $now
        ]));
        
        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }
    
    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($decoded->jti)) {
                throw new \Exception('Token is blacklisted');
            }
            
            // Validate session
            if (!$this->isSessionValid($decoded->sub, $decoded->session_id)) {
                throw new \Exception('Session is invalid');
            }
            
            // Validate device fingerprint if present
            if (isset($decoded->device_fp) && !$this->validateDeviceFingerprint($decoded->device_fp)) {
                $this->logSuspiciousActivity($decoded->sub, 'Device fingerprint mismatch');
                throw new \Exception('Device fingerprint mismatch');
            }
            
            return $decoded;
            
        } catch (\Exception $e) {
            Log::warning('JWT validation failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...'
            ]);
            return null;
        }
    }
    
    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->secretKey, $this->algorithm));
            
            // Validate refresh token type
            if ($decoded->type !== 'refresh') {
                throw new \Exception('Invalid token type');
            }
            
            // Check if refresh token exists in Redis
            $tokenData = Redis::get("refresh_token:{$decoded->jti}");
            if (!$tokenData) {
                throw new \Exception('Refresh token not found');
            }
            
            $tokenInfo = json_decode($tokenData, true);
            
            // Validate session
            if (!$this->isSessionValid($decoded->sub, $decoded->session_id)) {
                throw new \Exception('Session is invalid');
            }
            
            // Get user permissions (you might want to fetch from database)
            $permissions = $this->getUserPermissions($decoded->sub);
            
            // Generate new token pair
            $newTokens = $this->generateTokenPair($decoded->sub, $permissions, null);
            
            // Invalidate old refresh token
            Redis::del("refresh_token:{$decoded->jti}");
            
            return $newTokens;
            
        } catch (\Exception $e) {
            Log::warning('Token refresh failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    public function blacklistToken(string $jti): void
    {
        $tokenInfo = Redis::get("jwt_jti:{$jti}");
        if ($tokenInfo) {
            $info = json_decode($tokenInfo, true);
            Redis::setex("blacklisted_jwt:{$jti}", self::ACCESS_TOKEN_TTL, json_encode([
                'blacklisted_at' => time(),
                'user_id' => $info['user_id'] ?? null
            ]));
        }
    }
    
    public function blacklistAllUserTokens(int $userId): void
    {
        // Get all user sessions
        $sessions = Redis::smembers("user_sessions:{$userId}");
        
        foreach ($sessions as $sessionId) {
            $this->invalidateSession($userId, $sessionId);
        }
        
        // Clear user sessions set
        Redis::del("user_sessions:{$userId}");
        
        Log::info('All tokens blacklisted for user', ['user_id' => $userId]);
    }
    
    public function invalidateSession(int $userId, string $sessionId): void
    {
        // Remove session
        Redis::del("session:{$userId}:{$sessionId}");
        Redis::srem("user_sessions:{$userId}", $sessionId);
        
        // Find and blacklist related tokens
        $keys = Redis::keys("jwt_jti:*");
        foreach ($keys as $key) {
            $tokenInfo = Redis::get($key);
            if ($tokenInfo) {
                $info = json_decode($tokenInfo, true);
                if ($info['session_id'] === $sessionId && $info['user_id'] === $userId) {
                    $jti = str_replace('jwt_jti:', '', $key);
                    $this->blacklistToken($jti);
                }
            }
        }
    }
    
    private function storeSession(int $userId, string $sessionId, string $deviceFingerprint, ?string $deviceInfo): void
    {
        $sessionData = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'device_fingerprint' => $deviceFingerprint,
            'device_info' => $deviceInfo,
            'created_at' => time(),
            'last_activity' => time(),
            'ip_address' => request()->ip()
        ];
        
        // Store session
        Redis::setex("session:{$userId}:{$sessionId}", self::REFRESH_TOKEN_TTL, json_encode($sessionData));
        
        // Add to user sessions set
        Redis::sadd("user_sessions:{$userId}", $sessionId);
    }
    
    private function isSessionValid(int $userId, string $sessionId): bool
    {
        $sessionData = Redis::get("session:{$userId}:{$sessionId}");
        return $sessionData !== null;
    }
    
    private function cleanupOldSessions(int $userId): void
    {
        $sessions = Redis::smembers("user_sessions:{$userId}");
        
        if (count($sessions) > self::MAX_DEVICES_PER_USER) {
            // Get session details to find oldest
            $sessionDetails = [];
            foreach ($sessions as $sessionId) {
                $data = Redis::get("session:{$userId}:{$sessionId}");
                if ($data) {
                    $sessionInfo = json_decode($data, true);
                    $sessionDetails[] = [
                        'session_id' => $sessionId,
                        'created_at' => $sessionInfo['created_at']
                    ];
                }
            }
            
            // Sort by creation time and remove oldest
            usort($sessionDetails, fn($a, $b) => $a['created_at'] <=> $b['created_at']);
            
            $sessionsToRemove = array_slice($sessionDetails, 0, count($sessionDetails) - self::MAX_DEVICES_PER_USER);
            
            foreach ($sessionsToRemove as $session) {\n                $this->invalidateSession($userId, $session['session_id']);\n            }\n        }\n    }\n    \n    private function generateDeviceFingerprint(?string $deviceInfo): string\n    {\n        $request = request();\n        \n        $components = [\n            $request->header('User-Agent', ''),\n            $request->header('Accept-Language', ''),\n            $request->header('Accept-Encoding', ''),\n            $deviceInfo ?? ''\n        ];\n        \n        return hash('sha256', implode('|', $components));\n    }\n    \n    private function validateDeviceFingerprint(string $expectedFingerprint): bool\n    {\n        $currentFingerprint = $this->generateDeviceFingerprint(null);\n        return hash_equals($expectedFingerprint, $currentFingerprint);\n    }\n    \n    private function isTokenBlacklisted(string $jti): bool\n    {\n        return Redis::exists(\"blacklisted_jwt:{$jti}\");\n    }\n    \n    private function getUserPermissions(int $userId): array\n    {\n        // This should fetch from your user permissions system\n        // For now, return empty array\n        return [];\n    }\n    \n    private function logSuspiciousActivity(int $userId, string $reason): void\n    {\n        Log::warning('Suspicious JWT activity', [\n            'user_id' => $userId,\n            'reason' => $reason,\n            'ip' => request()->ip(),\n            'user_agent' => request()->header('User-Agent'),\n            'timestamp' => now()->toISOString()\n        ]);\n    }\n    \n    public function getActiveSessions(int $userId): array\n    {\n        $sessions = Redis::smembers(\"user_sessions:{$userId}\");\n        $activeSessions = [];\n        \n        foreach ($sessions as $sessionId) {\n            $sessionData = Redis::get(\"session:{$userId}:{$sessionId}\");\n            if ($sessionData) {\n                $session = json_decode($sessionData, true);\n                $activeSessions[] = [\n                    'session_id' => $sessionId,\n                    'device_info' => $session['device_info'] ?? 'Unknown',\n                    'ip_address' => $session['ip_address'] ?? 'Unknown',\n                    'created_at' => date('Y-m-d H:i:s', $session['created_at']),\n                    'last_activity' => date('Y-m-d H:i:s', $session['last_activity'])\n                ];\n            }\n        }\n        \n        return $activeSessions;\n    }\n    \n    public function getTokenStatistics(): array\n    {\n        $activeTokens = count(Redis::keys('jwt_jti:*'));\n        $blacklistedTokens = count(Redis::keys('blacklisted_jwt:*'));\n        $activeSessions = count(Redis::keys('session:*'));\n        \n        return [\n            'active_tokens' => $activeTokens,\n            'blacklisted_tokens' => $blacklistedTokens,\n            'active_sessions' => $activeSessions\n        ];\n    }\n}