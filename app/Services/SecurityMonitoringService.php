<?php

namespace App\Services;

use App\Notifications\SecurityAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;

class SecurityMonitoringService
{
    public function __construct(
        private AuditTrailService $auditService,
        private RateLimitingService $rateLimiter
    ) {}

    private array $alertThresholds = [
        'failed_logins' => 10,
        'blocked_requests' => 50,
        'suspicious_activities' => 5,
        'data_breaches' => 1,
        'privilege_escalations' => 1,
    ];



    public function isAccountLocked(string $identifier): bool
    {
        return Cache::has("account_locked:{$identifier}");
    }

    // THREAT DETECTION
    public function calculateThreatScore($request): array
    {
        $score = 0;
        $reasons = [];
        $input = $this->getAllInput($request);
        
        // SQL injection detection
        if (preg_match('/(union.*select|drop.*table|\'.*or.*\')/i', $input)) {
            $score += 50;
            $reasons[] = 'sql_injection_detected';
        }
        
        // XSS detection
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
        
        return [
            'score' => $score,
            'reasons' => $reasons,
            'action' => $score >= 80 ? 'block' : ($score >= 60 ? 'challenge' : ($score >= 40 ? 'monitor' : 'allow'))
        ];
    }

    // IP BLOCKING
    public function blockIP(string $ip, int $duration = 3600, string $reason = 'security_violation'): void
    {
        Cache::put("blocked_ip:{$ip}", [
            'blocked_at' => time(),
            'reason' => $reason,
            'expires_at' => time() + $duration
        ], now()->addSeconds($duration));
        
        // Audit logging handled by caller
    }
    
    public function isIPBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }


    
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '***INVALID_EMAIL***';
        
        $username = $parts[0];
        $domain = $parts[1];
        $maskedUsername = substr($username, 0, 2) . str_repeat('*', max(0, strlen($username) - 2));
        
        return $maskedUsername . '@' . $domain;
    }
    
    private function getAllInput($request): string
    {
        $data = [];
        $data[] = json_encode($request->all());
        $data[] = $request->getContent();
        $data[] = $request->getQueryString();
        
        return implode(' ', $data);
    }


    
    public function checkSuspiciousActivity(int $userId): array
    {
        // Use AuditTrailService for anomaly detection
        $anomalies = $this->auditService->detectAnomalousActivity($userId);
        
        $riskScore = 0;
        $reasons = [];
        
        // Convert anomalies to risk assessment
        foreach ($anomalies as $anomaly) {
            switch ($anomaly['type']) {
                case 'new_ip_addresses':
                    $riskScore += 20;
                    $reasons[] = 'Login from new IP address';
                    break;
                case 'high_activity_volume':
                    $riskScore += 30;
                    $reasons[] = 'Unusual activity volume';
                    break;
            }
        }
        
        // Additional checks
        $failedLogins = Cache::get("failed_logins:{$userId}", 0);
        if ($failedLogins > 3) {
            $riskScore += 30;
            $reasons[] = 'Multiple failed login attempts';
        }
        
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 23) {
            $riskScore += 15;
            $reasons[] = 'Login at unusual hours';
        }
        
        $riskLevel = $riskScore >= 50 ? 'high' : ($riskScore >= 30 ? 'medium' : 'low');
        
        $recommendations = [];
        if ($riskScore >= 30) {
            $recommendations[] = 'Enable two-factor authentication';
            $recommendations[] = 'Review recent login activity';
        }
        if ($riskScore >= 50) {
            $recommendations[] = 'Change your password immediately';
            $recommendations[] = 'Check for unauthorized access';
        }
        
        return [
            'detected' => $riskScore > 0,
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'reasons' => $reasons,
            'recommendations' => $recommendations,
            'anomalies' => $anomalies
        ];
    }
}
