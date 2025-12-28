<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class AdvancedRateLimiter
{
    private const WINDOW_SIZE = 60; // seconds
    private const MAX_REQUESTS_PER_MINUTE = 60;
    private const MAX_REQUESTS_PER_HOUR = 1000;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const BURST_THRESHOLD = 10; // requests in 10 seconds
    
    public function attempt(string $key, int $maxAttempts, int $decayMinutes = 1): bool
    {
        $current = $this->getCurrentAttempts($key);
        
        if ($current >= $maxAttempts) {
            $this->logRateLimit($key, $current, $maxAttempts);
            return false;
        }
        
        $this->hit($key, $decayMinutes);
        return true;
    }
    
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->getCurrentAttempts($key) >= $maxAttempts;
    }
    
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $pipeline = Redis::pipeline();
        
        // Sliding window counter
        $now = time();
        $windowStart = $now - ($decayMinutes * 60);
        
        // Remove old entries
        $pipeline->zremrangebyscore($key, 0, $windowStart);
        
        // Add current request
        $pipeline->zadd($key, $now, $now . ':' . uniqid());
        
        // Set expiration
        $pipeline->expire($key, $decayMinutes * 60);
        
        $results = $pipeline->exec();
        
        // Return current count
        return $this->getCurrentAttempts($key);
    }
    
    public function getCurrentAttempts(string $key): int
    {
        $now = time();
        $windowStart = $now - self::WINDOW_SIZE;
        
        return Redis::zcount($key, $windowStart, $now);
    }
    
    public function getRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->getCurrentAttempts($key));
    }
    
    public function getRetryAfter(string $key): int
    {
        $oldest = Redis::zrange($key, 0, 0, 'WITHSCORES');
        
        if (empty($oldest)) {
            return 0;
        }
        
        $oldestTime = (int) array_values($oldest)[0];
        return max(0, ($oldestTime + self::WINDOW_SIZE) - time());
    }
    
    public function checkBurstLimit(string $ip): bool
    {
        $key = "burst:{$ip}";
        $burstCount = $this->getCurrentBurstCount($key);
        
        if ($burstCount >= self::BURST_THRESHOLD) {
            $this->logBurstDetection($ip, $burstCount);
            return false;
        }
        
        $this->hitBurst($key);
        return true;
    }
    
    private function getCurrentBurstCount(string $key): int
    {
        $now = time();
        $windowStart = $now - 10; // 10 seconds window
        
        return Redis::zcount($key, $windowStart, $now);
    }
    
    private function hitBurst(string $key): void
    {
        $now = time();
        $windowStart = $now - 10;
        
        $pipeline = Redis::pipeline();
        $pipeline->zremrangebyscore($key, 0, $windowStart);
        $pipeline->zadd($key, $now, $now . ':' . uniqid());
        $pipeline->expire($key, 10);
        $pipeline->exec();
    }
    
    public function checkHourlyLimit(string $ip): bool
    {
        $key = "hourly:{$ip}";
        $hourlyCount = $this->getHourlyCount($key);
        
        if ($hourlyCount >= self::MAX_REQUESTS_PER_HOUR) {
            $this->logHourlyLimitExceeded($ip, $hourlyCount);
            return false;
        }
        
        $this->hitHourly($key);
        return true;
    }
    
    private function getHourlyCount(string $key): int
    {
        $now = time();
        $hourStart = $now - 3600; // 1 hour
        
        return Redis::zcount($key, $hourStart, $now);
    }
    
    private function hitHourly(string $key): void
    {
        $now = time();
        $hourStart = $now - 3600;
        
        $pipeline = Redis::pipeline();
        $pipeline->zremrangebyscore($key, 0, $hourStart);
        $pipeline->zadd($key, $now, $now . ':' . uniqid());
        $pipeline->expire($key, 3600);
        $pipeline->exec();
    }
    
    public function checkLoginAttempts(string $identifier): bool
    {
        $key = "login_attempts:{$identifier}";
        return $this->attempt($key, self::MAX_LOGIN_ATTEMPTS, 15); // 15 minutes lockout
    }
    
    public function resetLoginAttempts(string $identifier): void
    {
        $key = "login_attempts:{$identifier}";
        Redis::del($key);
    }
    
    public function blockIpTemporarily(string $ip, int $duration = 3600): void
    {
        $key = "blocked_ip:{$ip}";
        Redis::setex($key, $duration, time());
        
        $this->logIpBlocked($ip, $duration);
    }
    
    public function isIpBlocked(string $ip): bool
    {
        $key = "blocked_ip:{$ip}";
        return Redis::exists($key);
    }
    
    public function getBlockedIps(): array
    {
        $keys = Redis::keys('blocked_ip:*');
        $ips = [];
        
        foreach ($keys as $key) {
            $ip = str_replace('blocked_ip:', '', $key);
            $blockedAt = Redis::get($key);
            $ttl = Redis::ttl($key);
            
            $ips[] = [
                'ip' => $ip,
                'blocked_at' => date('Y-m-d H:i:s', $blockedAt),
                'expires_in' => $ttl > 0 ? $ttl : 0
            ];
        }
        
        return $ips;
    }
    
    public function getStatistics(): array
    {
        $stats = [
            'total_blocked_ips' => count(Redis::keys('blocked_ip:*')),
            'active_rate_limits' => count(Redis::keys('rate_limit:*')),
            'burst_detections_today' => $this->getBurstDetectionsToday(),
            'top_offending_ips' => $this->getTopOffendingIps()
        ];
        
        return $stats;
    }
    
    private function getBurstDetectionsToday(): int
    {
        $key = 'burst_detections:' . date('Y-m-d');
        return Redis::get($key) ?: 0;
    }
    
    private function getTopOffendingIps(): array
    {
        $key = 'offending_ips:' . date('Y-m-d');
        return Redis::zrevrange($key, 0, 9, 'WITHSCORES') ?: [];
    }
    
    private function logRateLimit(string $key, int $current, int $max): void
    {
        Log::warning('Rate limit exceeded', [
            'key' => $key,
            'current_attempts' => $current,
            'max_attempts' => $max,
            'timestamp' => now()->toISOString()
        ]);
    }
    
    private function logBurstDetection(string $ip, int $count): void
    {
        Log::warning('Burst limit exceeded', [
            'ip' => $ip,
            'burst_count' => $count,
            'threshold' => self::BURST_THRESHOLD,
            'timestamp' => now()->toISOString()
        ]);
        
        // Increment daily counter
        $dailyKey = 'burst_detections:' . date('Y-m-d');
        Redis::incr($dailyKey);
        Redis::expire($dailyKey, 86400);
        
        // Track offending IP
        $offendingKey = 'offending_ips:' . date('Y-m-d');
        Redis::zincrby($offendingKey, 1, $ip);
        Redis::expire($offendingKey, 86400);
    }
    
    private function logHourlyLimitExceeded(string $ip, int $count): void
    {
        Log::warning('Hourly limit exceeded', [
            'ip' => $ip,
            'hourly_count' => $count,
            'limit' => self::MAX_REQUESTS_PER_HOUR,
            'timestamp' => now()->toISOString()
        ]);
    }
    
    private function logIpBlocked(string $ip, int $duration): void
    {
        Log::warning('IP blocked temporarily', [
            'ip' => $ip,
            'duration' => $duration,
            'timestamp' => now()->toISOString()
        ]);
    }
}