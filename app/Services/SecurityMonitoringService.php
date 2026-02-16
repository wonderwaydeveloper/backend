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
            $score += config('security.threat_detection.scores.sql_injection');
            $reasons[] = 'sql_injection_detected';
        }
        
        // XSS detection
        if (preg_match('/<script|javascript:|on\w+=/i', $input)) {
            $score += config('security.threat_detection.scores.xss');
            $reasons[] = 'xss_detected';
        }
        
        // Bot detection
        $userAgent = $request->userAgent();
        if (!$userAgent || preg_match('/bot|crawler|spider|sqlmap|nikto/i', $userAgent)) {
            $score += config('security.threat_detection.scores.bot');
            $reasons[] = 'bot_detected';
        }
        
        $thresholds = config('security.threat_detection.thresholds');
        return [
            'score' => $score,
            'reasons' => $reasons,
            'action' => $score >= $thresholds['block'] ? 'block' : ($score >= $thresholds['challenge'] ? 'challenge' : ($score >= $thresholds['monitor'] ? 'monitor' : 'allow'))
        ];
    }

    // IP BLOCKING
    public function blockIP(string $ip, ?int $duration = null, string $reason = 'security_violation'): void
    {
        $duration = $duration ?? config('security.threat_detection.ip_block_duration');
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
                    $riskScore += config('security.monitoring.risk_scores.new_ip');
                    $reasons[] = 'Login from new IP address';
                    break;
                case 'high_activity_volume':
                    $riskScore += config('security.monitoring.risk_scores.high_activity');
                    $reasons[] = 'Unusual activity volume';
                    break;
            }
        }
        
        // Additional checks
        $failedLogins = Cache::get("failed_logins:{$userId}", 0);
        $threshold = config('security.monitoring.failed_login_threshold');
        if ($failedLogins > $threshold) {
            $riskScore += config('security.monitoring.risk_scores.failed_logins');
            $reasons[] = 'Multiple failed login attempts';
        }
        
        $currentHour = now()->hour;
        $unusualHours = config('security.monitoring.unusual_hours');
        if ($currentHour < $unusualHours['end'] || $currentHour > $unusualHours['start']) {
            $riskScore += config('security.monitoring.risk_scores.unusual_hours');
            $reasons[] = 'Login at unusual hours';
        }
        
        $levels = config('security.monitoring.risk_levels');
        $riskLevel = $riskScore >= $levels['high'] ? 'high' : ($riskScore >= $levels['medium'] ? 'medium' : 'low');
        
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
