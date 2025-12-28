<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecureJWTService
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    
    const ACCESS_TOKEN_TTL = 900; // 15 minutes
    const REFRESH_TOKEN_TTL = 604800; // 7 days
    const MAX_DEVICES_PER_USER = 5;
    
    public function __construct()
    {
        $this->secretKey = config('jwt.secret');
    }
    
    public function generateTokenPair(int $userId, array $permissions = [], ?string $deviceInfo = null): array
    {
        $sessionId = Str::uuid()->toString();
        $deviceFingerprint = $this->generateDeviceFingerprint($deviceInfo);
        
        $this->storeSession($userId, $sessionId, $deviceFingerprint, $deviceInfo);
        $this->cleanupOldSessions($userId);
        
        $accessJti = Str::uuid()->toString();
        $refreshJti = Str::uuid()->toString();
        $now = time();
        
        $accessPayload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'exp' => $now + self::ACCESS_TOKEN_TTL,
            'sub' => $userId,
            'jti' => $accessJti,
            'type' => 'access',
            'permissions' => $permissions,
            'session_id' => $sessionId,
            'device_fp' => $deviceFingerprint
        ];
        
        $refreshPayload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'exp' => $now + self::REFRESH_TOKEN_TTL,
            'sub' => $userId,
            'jti' => $refreshJti,
            'type' => 'refresh',
            'session_id' => $sessionId
        ];
        
        $accessToken = JWT::encode($accessPayload, $this->secretKey, $this->algorithm);
        $refreshToken = JWT::encode($refreshPayload, $this->secretKey, $this->algorithm);
        
        Redis::setex("jwt_jti:{$accessJti}", self::ACCESS_TOKEN_TTL, json_encode([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'type' => 'access',
            'created_at' => $now
        ]));
        
        Redis::setex("refresh_token:{$refreshJti}", self::REFRESH_TOKEN_TTL, json_encode([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'type' => 'refresh',
            'created_at' => $now
        ]));
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'token_type' => 'Bearer'
        ];
    }
    
    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            if ($this->isTokenBlacklisted($decoded->jti)) {
                throw new \Exception('Token is blacklisted');
            }
            
            if (!$this->isSessionValid($decoded->sub, $decoded->session_id)) {
                throw new \Exception('Session is invalid');
            }
            
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
            
            if ($decoded->type !== 'refresh') {
                throw new \Exception('Invalid token type');
            }
            
            $tokenData = Redis::get("refresh_token:{$decoded->jti}");
            if (!$tokenData) {
                throw new \Exception('Refresh token not found');
            }
            
            if (!$this->isSessionValid($decoded->sub, $decoded->session_id)) {
                throw new \Exception('Session is invalid');
            }
            
            $permissions = $this->getUserPermissions($decoded->sub);
            $newTokens = $this->generateTokenPair($decoded->sub, $permissions, null);
            
            Redis::del("refresh_token:{$decoded->jti}");
            
            return $newTokens;
            
        } catch (\Exception $e) {
            Log::warning('Token refresh failed', ['error' => $e->getMessage()]);
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
        $sessions = Redis::smembers("user_sessions:{$userId}");
        
        foreach ($sessions as $sessionId) {
            $this->invalidateSession($userId, $sessionId);
        }
        
        Redis::del("user_sessions:{$userId}");
        Log::info('All tokens blacklisted for user', ['user_id' => $userId]);
    }
    
    public function invalidateSession(int $userId, string $sessionId): void
    {
        Redis::del("session:{$userId}:{$sessionId}");
        Redis::srem("user_sessions:{$userId}", $sessionId);
        
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
        
        Redis::setex("session:{$userId}:{$sessionId}", self::REFRESH_TOKEN_TTL, json_encode($sessionData));
        Redis::sadd("user_sessions:{$userId}", $sessionId);
    }
    
    private function isSessionValid(int $userId, string $sessionId): bool
    {
        return Redis::get("session:{$userId}:{$sessionId}") !== null;
    }
    
    private function cleanupOldSessions(int $userId): void
    {
        $sessions = Redis::smembers("user_sessions:{$userId}");
        
        if (count($sessions) > self::MAX_DEVICES_PER_USER) {
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
            
            usort($sessionDetails, fn($a, $b) => $a['created_at'] <=> $b['created_at']);
            $sessionsToRemove = array_slice($sessionDetails, 0, count($sessionDetails) - self::MAX_DEVICES_PER_USER);
            
            foreach ($sessionsToRemove as $session) {
                $this->invalidateSession($userId, $session['session_id']);
            }
        }
    }
    
    private function generateDeviceFingerprint(?string $deviceInfo): string
    {
        $request = request();
        
        $components = [
            $request->header('User-Agent', ''),
            $request->header('Accept-Language', ''),
            $request->header('Accept-Encoding', ''),
            $deviceInfo ?? ''
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    private function validateDeviceFingerprint(string $expectedFingerprint): bool
    {
        $currentFingerprint = $this->generateDeviceFingerprint(null);
        return hash_equals($expectedFingerprint, $currentFingerprint);
    }
    
    private function isTokenBlacklisted(string $jti): bool
    {
        return Redis::exists("blacklisted_jwt:{$jti}");
    }
    
    private function getUserPermissions(int $userId): array
    {
        return [];
    }
    
    private function logSuspiciousActivity(int $userId, string $reason): void
    {
        Log::warning('Suspicious JWT activity', [
            'user_id' => $userId,
            'reason' => $reason,
            'ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'timestamp' => now()->toISOString()
        ]);
    }
    
    public function getActiveSessions(int $userId): array
    {
        $sessions = Redis::smembers("user_sessions:{$userId}");
        $activeSessions = [];
        
        foreach ($sessions as $sessionId) {
            $sessionData = Redis::get("session:{$userId}:{$sessionId}");
            if ($sessionData) {
                $session = json_decode($sessionData, true);
                $activeSessions[] = [
                    'session_id' => $sessionId,
                    'device_info' => $session['device_info'] ?? 'Unknown',
                    'ip_address' => $session['ip_address'] ?? 'Unknown',
                    'created_at' => date('Y-m-d H:i:s', $session['created_at']),
                    'last_activity' => date('Y-m-d H:i:s', $session['last_activity'])
                ];
            }
        }
        
        return $activeSessions;
    }
    
    public function getTokenStatistics(): array
    {
        $activeTokens = count(Redis::keys('jwt_jti:*'));
        $blacklistedTokens = count(Redis::keys('blacklisted_jwt:*'));
        $activeSessions = count(Redis::keys('session:*'));
        
        return [
            'active_tokens' => $activeTokens,
            'blacklisted_tokens' => $blacklistedTokens,
            'active_sessions' => $activeSessions
        ];
    }
}