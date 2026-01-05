<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class WebApplicationFirewall
{
    private const THREAT_SCORE_THRESHOLD = 60; // Adjusted for user agent detection
    private const IP_BLOCK_DURATION = 3600; // 1 hour
    
    private array $sqlPatterns = [
        // Advanced SQL injection patterns
        '/(\bUNION\b.*\bSELECT\b)/i' => 30,
        '/(\bDROP\b.*\bTABLE\b)/i' => 50,
        '/(\bINSERT\b.*\bINTO\b)/i' => 25,
        '/(\bDELETE\b.*\bFROM\b)/i' => 40,
        '/(\bUPDATE\b.*\bSET\b)/i' => 25,
        '/(\bSELECT\b.*\bFROM\b)/i' => 20,
        '/(\'.*OR.*\'.*=.*\')/i' => 35,
        '/(\".*OR.*\".*=.*\")/i' => 35,
        '/(\bOR\b.*1.*=.*1)/i' => 40,
        '/(\bAND\b.*1.*=.*1)/i' => 40,
        '/\b(exec|execute)\s*\(/i' => 45,
        '/\b(sp_|xp_)\w+/i' => 35,
        '/\b(waitfor|delay)\s+/i' => 30,
        '/\b(cast|convert)\s*\(/i' => 20,
        '/\b(char|ascii|substring)\s*\(/i' => 25,
        '/\b(information_schema|sysobjects|syscolumns)/i' => 40,
        '/(\-\-|\/\*|\*\/)/i' => 15,
        '/\b(load_file|into\s+outfile)/i' => 45,
        '/\b(benchmark|sleep)\s*\(/i' => 35,
    ];
    
    private array $xssPatterns = [
        // Advanced XSS patterns
        '/<script[^>]*>/i' => 40,
        '/<\/script>/i' => 40,
        '/<iframe[^>]*>/i' => 35,
        '/<object[^>]*>/i' => 30,
        '/<embed[^>]*>/i' => 30,
        '/<applet[^>]*>/i' => 35,
        '/javascript:/i' => 30,
        '/vbscript:/i' => 30,
        '/data:text\/html/i' => 25,
        '/on\w+\s*=/i' => 35,
        '/alert\s*\(/i' => 25,
        '/confirm\s*\(/i' => 25,
        '/prompt\s*\(/i' => 25,
        '/document\.(cookie|domain|write)/i' => 30,
        '/window\.(location|open)/i' => 25,
        '/eval\s*\(/i' => 40,
        '/expression\s*\(/i' => 35,
        '/\binnerHTML\b/i' => 20,
        '/\bouterHTML\b/i' => 20,
        '/<\w+[^>]*\s+style\s*=.*expression/i' => 35,
    ];
    
    private array $lfiPatterns = [
        // Local File Inclusion patterns
        '/\.\.[\/\\\\]/i' => 40,
        '/etc\/passwd/i' => 50,
        '/proc\/self\/environ/i' => 45,
        '/\/windows\/system32/i' => 45,
        '/boot\.ini/i' => 40,
        '/\/etc\/shadow/i' => 50,
        '/\/var\/log/i' => 30,
        '/php:\/\/filter/i' => 35,
        '/php:\/\/input/i' => 35,
        '/data:\/\/text/i' => 30,
    ];
    
    private array $rfiPatterns = [
        // Remote File Inclusion patterns
        '/https?:\/\/[^\s]+\.(txt|php|asp|jsp)/i' => 40,
        '/ftp:\/\/[^\s]+/i' => 35,
        '/\b(include|require)(_once)?\s*\([^)]*https?:/i' => 45,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip WAF in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $clientIp = $this->getClientIp($request);
        
        // Check if IP is blocked
        if ($this->isIpBlocked($clientIp)) {
            $this->logThreat($clientIp, 'IP_BLOCKED', 'Blocked IP attempted access');
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        // Calculate threat score
        $threatScore = $this->calculateThreatScore($request);
        
        if ($threatScore >= self::THREAT_SCORE_THRESHOLD) {
            $this->handleThreat($clientIp, $threatScore, $request);
            return response()->json(['error' => 'Security threat detected'], 403);
        }
        
        // Log suspicious activity (score > 20 but < threshold)
        if ($threatScore > 20) {
            $this->logSuspiciousActivity($clientIp, $threatScore, $request);
        }
        
        return $next($request);
    }
    
    private function calculateThreatScore(Request $request): int
    {
        $score = 0;
        $input = $this->getAllInput($request);
        
        // Check SQL injection patterns
        foreach ($this->sqlPatterns as $pattern => $points) {
            if (preg_match($pattern, $input)) {
                $score += $points;
            }
        }
        
        // Check XSS patterns
        foreach ($this->xssPatterns as $pattern => $points) {
            if (preg_match($pattern, $input)) {
                $score += $points;
            }
        }
        
        // Check LFI patterns
        foreach ($this->lfiPatterns as $pattern => $points) {
            if (preg_match($pattern, $input)) {
                $score += $points;
            }
        }
        
        // Check RFI patterns
        foreach ($this->rfiPatterns as $pattern => $points) {
            if (preg_match($pattern, $input)) {
                $score += $points;
            }
        }
        
        // Additional checks
        $score += $this->checkSuspiciousHeaders($request);
        $score += $this->checkFileUploadThreats($request);
        $score += $this->checkRequestFrequency($request);
        
        return $score;
    }
    
    private function getAllInput(Request $request): string
    {
        $data = [];
        
        // Get all request data
        $data[] = json_encode($request->all());
        $data[] = $request->getContent();
        $data[] = $request->getQueryString();
        
        // Get headers
        foreach ($request->headers->all() as $key => $values) {
            $data[] = $key . ': ' . implode(', ', $values);
        }
        
        return implode(' ', $data);
    }
    
    private function checkSuspiciousHeaders(Request $request): int
    {
        $score = 0;
        
        // Check User-Agent
        $userAgent = $request->header('User-Agent', '');
        if (empty($userAgent) || strlen($userAgent) < 10) {
            $score += 15;
        }
        
        // Check for suspicious tools
        $suspiciousAgents = [
            'sqlmap', 'nikto', 'nmap', 'burp', 'zap', 'w3af',
            'havij', 'pangolin', 'acunetix', 'netsparker'
        ];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $score += 60; // Increased to exceed threshold
            }
        }
        
        // Check Referer
        $referer = $request->header('Referer', '');
        if (!empty($referer) && !$this->isValidReferer($referer)) {
            $score += 20;
        }
        
        return $score;
    }
    
    private function checkFileUploadThreats(Request $request): int
    {
        $score = 0;
        
        if ($request->hasFile('file') || $request->hasFile('upload')) {
            $files = $request->allFiles();
            
            foreach ($files as $file) {
                if (is_array($file)) {
                    foreach ($file as $f) {
                        $score += $this->analyzeFile($f);
                    }
                } else {
                    $score += $this->analyzeFile($file);
                }
            }
        }
        
        return $score;
    }
    
    private function analyzeFile($file): int
    {
        $score = 0;
        
        if (!$file || !$file->isValid()) {
            return 0;
        }
        
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = [
            'php', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd',
            'sh', 'py', 'pl', 'rb', 'js', 'vbs', 'jar'
        ];
        
        if (in_array($extension, $dangerousExtensions)) {
            $score += 45;
        }
        
        // Check file content
        $content = file_get_contents($file->getPathname());
        if (strpos($content, '<?php') !== false || strpos($content, '<%') !== false) {
            $score += 50;
        }
        
        return $score;
    }
    
    private function checkRequestFrequency(Request $request): int
    {
        $clientIp = $this->getClientIp($request);
        $key = "waf_freq:{$clientIp}";
        
        $requests = Redis::incr($key);
        Redis::expire($key, 60); // 1 minute window
        
        if ($requests > 100) {
            return 30; // High frequency
        } elseif ($requests > 50) {
            return 15; // Medium frequency
        }
        
        return 0;
    }
    
    private function getClientIp(Request $request): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            $ip = $request->server($header);
            if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
        
        return $request->ip();
    }
    
    private function isIpBlocked(string $ip): bool
    {
        // Check both WAF blocks and rate limiter blocks
        return Redis::exists("waf_blocked:{$ip}") || Redis::exists("blocked_ip:{$ip}");
    }
    
    private function handleThreat(string $ip, int $score, Request $request): void
    {
        // Block IP temporarily
        Redis::setex("waf_blocked:{$ip}", self::IP_BLOCK_DURATION, $score);
        
        // Log the threat
        $this->logThreat($ip, 'HIGH_THREAT', "Threat score: {$score}", $request);
        
        // Increment threat counter
        $threatKey = "waf_threats:{$ip}:" . date('Y-m-d');
        Redis::incr($threatKey);
        Redis::expire($threatKey, 86400); // 24 hours
    }
    
    private function logThreat(string $ip, string $type, string $message, Request $request = null): void
    {
        $logData = [
            'ip' => $ip,
            'type' => $type,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'user_agent' => $request?->header('User-Agent'),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
        ];
        
        Log::warning('WAF Threat Detected', $logData);
        
        // Store in Redis for real-time monitoring
        Redis::lpush('waf_threats', json_encode($logData));
        Redis::ltrim('waf_threats', 0, 999); // Keep last 1000 threats
    }
    
    private function logSuspiciousActivity(string $ip, int $score, Request $request): void
    {
        $logData = [
            'ip' => $ip,
            'score' => $score,
            'timestamp' => now()->toISOString(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ];
        
        Log::info('WAF Suspicious Activity', $logData);
    }
    
    private function isValidReferer(string $referer): bool
    {
        $allowedDomains = [
            config('app.url'),
            'https://microblogging.com',
            'https://www.microblogging.com'
        ];
        
        foreach ($allowedDomains as $domain) {
            if (strpos($referer, $domain) === 0) {
                return true;
            }
        }
        
        return false;
    }
}
