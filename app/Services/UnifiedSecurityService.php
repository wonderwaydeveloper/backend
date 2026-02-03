<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class UnifiedSecurityService
{
    private array $endpointLimits = [
        'api/login' => ['attempts' => 5, 'decay' => 900],
        'api/register' => ['attempts' => 3, 'decay' => 3600], 
        'api/moments' => ['attempts' => 100, 'decay' => 3600],
        'api/follow' => ['attempts' => 50, 'decay' => 3600],
        'api/upload' => ['attempts' => 20, 'decay' => 3600],
    ];
    
    private array $rateLimits = [
        'login' => ['attempts' => 5, 'window' => 900],
        'register' => ['attempts' => 3, 'window' => 3600],
        'api' => ['attempts' => 60, 'window' => 60],
        'upload' => ['attempts' => 20, 'window' => 3600],
        'follow' => ['attempts' => 50, 'window' => 3600],
        'general' => ['attempts' => 100, 'window' => 60]
    ];
    
    public function checkRateLimit(Request $request, string $type = 'general'): array
    {
        $endpoint = $request->route()?->uri() ?? $request->path();
        $userId = $request->user()?->id ?? $request->ip();
        
        if (isset($this->endpointLimits[$endpoint])) {
            $limits = $this->endpointLimits[$endpoint];
            $key = "api_limit:{$endpoint}:{$userId}";
            
            if (RateLimiter::tooManyAttempts($key, $limits['attempts'])) {
                $this->logSuspiciousActivity($request, $userId, $endpoint);
                
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'retry_after' => RateLimiter::availableIn($key)
                ];
            }
            
            RateLimiter::hit($key, $limits['decay']);
            
            return [
                'allowed' => true,
                'remaining' => $limits['attempts'] - RateLimiter::attempts($key),
                'retry_after' => 0
            ];
        }
        
        $identifier = $this->getIdentifier($request);
        $key = "rate_limit:{$type}:{$identifier}";
        $config = $this->rateLimits[$type] ?? $this->rateLimits['general'];
        
        if ($type === 'login' && $request->isMethod('POST')) {
            return $this->handleBruteForceProtection($request, $key, $config);
        }
        
        $current = $this->getCurrentAttempts($key, $config['window']);
        
        if ($current >= $config['attempts']) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $this->getRetryAfter($key, $config['window'])
            ];
        }
        
        $this->incrementAttempts($key, $config['window']);
        
        return [
            'allowed' => true,
            'remaining' => $config['attempts'] - $current - 1,
            'retry_after' => 0
        ];
    }
    
    public function calculateThreatScore(Request $request): array
    {
        $score = 0;
        $reasons = [];
        $input = $this->getAllInput($request);
        
        // SQL injection
        if (preg_match('/(union.*select|drop.*table|\'.*or.*\')/i', $input)) {
            $score += 50;
            $reasons[] = 'sql_injection_detected';
        }
        
        // XSS
        if (preg_match('/<script|javascript:|on\w+=/i', $input)) {
            $score += 40;
            $reasons[] = 'xss_detected';
        }
        
        // Bot detection
        $userAgent = $request->userAgent();
        if (!$userAgent || preg_match('/bot|crawler|spider|sqlmap|nikto/i', $userAgent)) {
            $score += 30;
            $reasons[] = 'bot_detected';
        }
        
        // Spam detection
        $user = $request->user();
        if (!($user && $user->hasRole('admin'))) {
            if ($request->has('content')) {
                $content = $request->input('content');
                if (preg_match('/spam|fake|scam|click here|free money/i', $content)) {
                    $score += 20;
                    $reasons[] = 'spam_keywords';
                }
                
                $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $content);
                if ($urlCount > 2) {
                    $score += 25;
                    $reasons[] = 'multiple_urls';
                }
            }
        }
        
        // File upload threats
        if ($request->hasFile('file') || $request->hasFile('upload')) {
            $files = $request->allFiles();
            foreach ($files as $file) {
                if (is_array($file)) {
                    foreach ($file as $f) {
                        if ($this->isDangerousFile($f)) {
                            $score += 45;
                            $reasons[] = 'dangerous_file_upload';
                        }
                    }
                } else {
                    if ($this->isDangerousFile($file)) {
                        $score += 45;
                        $reasons[] = 'dangerous_file_upload';
                    }
                }
            }
        }
        
        return [
            'score' => $score,
            'reasons' => $reasons,
            'action' => $score >= 80 ? 'block' : ($score >= 60 ? 'challenge' : ($score >= 40 ? 'monitor' : 'allow'))
        ];
    }
    
    public function blockIP(string $ip, int $duration = 3600, string $reason = 'security_violation'): void
    {
        Redis::setex("blocked_ip:{$ip}", $duration, json_encode([
            'blocked_at' => time(),
            'reason' => $reason,
            'expires_at' => time() + $duration
        ]));
        
        Log::warning('IP Blocked', ['ip' => $ip, 'duration' => $duration, 'reason' => $reason]);
    }
    
    public function isIPBlocked(string $ip): bool
    {
        return Redis::exists("blocked_ip:{$ip}") || Redis::exists("waf_blocked:{$ip}");
    }
    
    public function isUserSuspicious($user): bool
    {
        if (!$user) return false;
        
        $key = "suspicious_user:{$user->id}";
        $suspiciousScore = Redis::get($key) ?? 0;
        
        return $suspiciousScore > 50;
    }
    
    public function sanitizeInputs(Request $request): void
    {
        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
                $value = strip_tags($value, '<p><br><strong><em>');
            }
        });
        $request->merge($input);
    }
    
    public function detectAdvancedXss(Request $request): bool
    {
        $patterns = [
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i'
        ];
        
        foreach ($request->all() as $value) {
            if (is_string($value)) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public function detectAdvancedSqlInjection(Request $request): bool
    {
        $patterns = [
            '/(union|select|insert|delete|update|drop)/i',
            '/(or|and)\s+\d+\s*=\s*\d+/i',
            '/[\'\";].*(or|and)/i',
            '/\b(exec|execute|sp_|xp_)\b/i'
        ];
        
        foreach ($request->all() as $value) {
            if (is_string($value)) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public function handleLoginResponse($response, Request $request): void
    {
        if ($request->is('api/auth/login') && $request->isMethod('POST')) {
            $key = 'login_attempts:' . $request->ip();
            
            if ($response->status() === 401) {
                Redis::incr($key);
                Redis::expire($key, 900);
            } elseif ($response->status() === 200) {
                Redis::del($key);
            }
        }
    }
    
    private function logSuspiciousActivity(Request $request, $userId, string $endpoint): void
    {
        Redis::lpush('suspicious_activity', json_encode([
            'user_id' => $userId,
            'ip' => $request->ip(),
            'endpoint' => $endpoint,
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
            'type' => 'rate_limit_exceeded',
        ]));
    }
    
    private function getAllInput(Request $request): string
    {
        $data = [];
        $data[] = json_encode($request->all());
        $data[] = $request->getContent();
        $data[] = $request->getQueryString();
        
        foreach ($request->headers->all() as $key => $values) {
            $data[] = $key . ': ' . implode(', ', $values);
        }
        
        return implode(' ', $data);
    }
    
    private function isDangerousFile($file): bool
    {
        if (!$file || !$file->isValid()) return false;
        
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = [
            'php', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd',
            'sh', 'py', 'pl', 'rb', 'js', 'vbs', 'jar'
        ];
        
        if (in_array($extension, $dangerousExtensions)) {
            return true;
        }
        
        $content = file_get_contents($file->getPathname());
        if (strpos($content, '<?php') !== false || strpos($content, '<%') !== false) {
            return true;
        }
        
        return false;
    }
    
    private function getIdentifier(Request $request): string
    {
        $user = $request->user();
        return $user ? "user:{$user->id}" : "ip:{$request->ip()}";
    }
    
    private function getCurrentAttempts(string $key, int $window): int
    {
        $now = time();
        Redis::zremrangebyscore($key, 0, $now - $window);
        return Redis::zcard($key);
    }
    
    private function incrementAttempts(string $key, int $window): void
    {
        Redis::zadd($key, time(), uniqid());
        Redis::expire($key, $window);
    }
    
    private function getRetryAfter(string $key, int $window): int
    {
        $oldest = Redis::zrange($key, 0, 0, 'WITHSCORES');
        if (empty($oldest)) return 0;
        
        $oldestTime = (int) array_values($oldest)[0];
        return max(0, ($oldestTime + $window) - time());
    }
    
    private function handleBruteForceProtection(Request $request, string $key, array $config): array
    {
        $bruteKey = 'login_attempts:' . $request->ip();
        $attempts = Redis::get($bruteKey) ?? 0;
        
        if ($attempts >= 5) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => Redis::ttl($bruteKey) ?: 900,
                'error' => 'Account temporarily locked due to too many failed attempts'
            ];
        }
        
        $current = $this->getCurrentAttempts($key, $config['window']);
        
        if ($current >= $config['attempts']) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $this->getRetryAfter($key, $config['window'])
            ];
        }
        
        $this->incrementAttempts($key, $config['window']);
        
        return [
            'allowed' => true,
            'remaining' => $config['attempts'] - $current - 1,
            'retry_after' => 0
        ];
    }
}