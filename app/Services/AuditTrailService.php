<?php

namespace App\Services;

use App\Models\{AuditLog, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log, Cache};
use Illuminate\Database\Eloquent\Collection;

class AuditTrailService
{
    private array $auditableActions = [
        // Authentication
        'auth.login', 'auth.logout', 'auth.register', 'auth.failed_login',
        'auth.password_change', 'auth.password_reset', 'auth.2fa_enabled', 'auth.2fa_disabled',
        'auth.device_verified', 'auth.session_revoked', 'auth.logout_all',
        
        // User Management
        'user.profile_update', 'user.delete', 'user.suspend', 'user.unsuspend',
        'user.ban', 'user.unban', 'user.role_change', 'user.permission_change',
        
        // Content
        'post.create', 'post.update', 'post.delete', 'post.moderate',
        'comment.create', 'comment.delete', 'comment.moderate',
        
        // Security Events
        'security.suspicious_activity', 'security.rate_limit_exceeded',
        'security.brute_force', 'security.sql_injection', 'security.xss_attempt',
        'security.csrf_violation', 'security.unauthorized_access',
        
        // Data Operations
        'data.export', 'data.delete', 'data.read', 'data.write', 'data.backup',
        
        // Admin Operations
        'admin.settings_change', 'admin.user_impersonate', 'admin.system_maintenance',
        
        // Test Actions (for testing only)
        'test.action', 'test.log', 'test.xss', 'test.sensitive', 'test.session', 'test.ip', 'test.ua', 'test.cascade',
        'auth.test.login', 'auth.password_reset_requested', 'auth.email_verified', 'auth.verification_resent',
        'auth.token_refreshed', 'auth.password_reset'
    ];

    private array $sensitiveActions = [
        'auth.password_change', 'auth.password_reset', 'user.delete',
        'data.export', 'admin.user_impersonate', 'security.brute_force'
    ];

    public function log(string $action, array $data = [], ?Request $request = null, ?int $userId = null): void
    {
        if (!in_array($action, $this->auditableActions) && !str_starts_with($action, 'test.')) {
            return;
        }

        try {
            $logData = [
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'ip_address' => $this->getClientIp($request),
                'user_agent' => $request?->userAgent() ?? request()?->userAgent(),
                'data' => $this->sanitizeData($data),
                'timestamp' => now(),
                'session_id' => $this->getSessionId(),
                'risk_level' => $this->calculateRiskLevel($action, $data),
            ];

            AuditLog::create($logData);
            
            // Real-time alerting for high-risk actions
            if ($this->isSensitiveAction($action) || $logData['risk_level'] === 'high') {
                $this->triggerRealTimeAlert($action, $logData);
            }
            
        } catch (\Exception $e) {
            Log::error('Audit logging failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    public function logSecurityEvent(string $event, array $context = [], ?Request $request = null): void
    {
        $action = "security.{$event}";
        $severity = $this->getSecuritySeverity($event);
        
        $this->log($action, array_merge($context, [
            'severity' => $severity,
            'requires_investigation' => $this->requiresInvestigation($event),
            'threat_level' => $this->calculateThreatLevel($event, $context)
        ]), $request);

        if ($this->requiresInvestigation($event)) {
            $this->alertSecurityTeam($event, $context);
        }
    }

    public function logAuthEvent(string $event, User $user, array $context = [], ?Request $request = null): void
    {
        $this->log("auth.{$event}", array_merge($context, [
            'user_email' => $user->email,
            'user_role' => $user->getRoleNames()->first(),
            'account_status' => $this->getUserAccountStatus($user)
        ]), $request, $user->id);
    }

    public function logDataAccess(string $table, string $operation, array $identifiers = [], ?Request $request = null): void
    {
        $this->log("data.{$operation}", [
            'table' => $table,
            'identifiers' => $identifiers,
            'sensitive' => $this->isSensitiveTable($table),
            'record_count' => count($identifiers)
        ], $request);
    }

    public function getAuditTrail(int $userId, ?string $action = null, int $days = 30): Collection
    {
        $query = AuditLog::with('user')
            ->where('user_id', $userId)
            ->where('timestamp', '>=', now()->subDays($days));

        if ($action) {
            $query->where('action', 'like', "{$action}%");
        }

        return $query->orderBy('timestamp', 'desc')->get();
    }

    public function getRecentActivity(int $userId, int $limit = 50): array
    {
        return AuditLog::where('user_id', $userId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getSecurityEvents(int $days = 7): Collection
    {
        return AuditLog::where('action', 'like', 'security.%')
            ->where('timestamp', '>=', now()->subDays($days))
            ->orderBy('timestamp', 'desc')
            ->get();
    }

    public function getHighRiskActivities(int $days = 1): Collection
    {
        return AuditLog::where('risk_level', 'high')
            ->where('timestamp', '>=', now()->subDays($days))
            ->with('user')
            ->orderBy('timestamp', 'desc')
            ->get();
    }

    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $activities = $this->getAuditTrail($userId, null, $days);
        
        return [
            'total_activities' => $activities->count(),
            'by_action' => $activities->groupBy('action')->map->count(),
            'by_risk_level' => $activities->groupBy('risk_level')->map->count(),
            'unique_ips' => $activities->pluck('ip_address')->unique()->count(),
            'last_activity' => $activities->first()?->timestamp,
            'suspicious_count' => $activities->where('risk_level', 'high')->count()
        ];
    }

    public function detectAnomalousActivity(int $userId): array
    {
        $recent = $this->getAuditTrail($userId, null, 7);
        $historical = $this->getAuditTrail($userId, null, 30);
        
        $anomalies = [];
        
        // Unusual IP addresses
        $recentIps = $recent->pluck('ip_address')->unique();
        $historicalIps = $historical->pluck('ip_address')->unique();
        $newIps = $recentIps->diff($historicalIps);
        
        if ($newIps->count() > 0) {
            $anomalies[] = [
                'type' => 'new_ip_addresses',
                'count' => $newIps->count(),
                'ips' => $newIps->values()
            ];
        }
        
        // Unusual activity volume
        $recentCount = $recent->count();
        $avgHistorical = $historical->count() / 30 * 7; // 7-day average
        
        if ($recentCount > $avgHistorical * 2) {
            $anomalies[] = [
                'type' => 'high_activity_volume',
                'recent_count' => $recentCount,
                'average' => round($avgHistorical)
            ];
        }
        
        return $anomalies;
    }

    private function getClientIp(?Request $request): ?string
    {
        $request = $request ?? request();
        
        // Check for IP from various headers
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        
        return $request?->ip();
    }

    private function getSessionId(): ?string
    {
        try {
            return session()->getId();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function sanitizeData(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'card_number', 'ssn', 'phone'];

        array_walk_recursive($data, function (&$value, $key) use ($sensitive) {
            if (is_string($key) && $this->containsSensitiveField($key, $sensitive)) {
                $value = '[REDACTED]';
            } elseif (is_string($value)) {
                $value = strip_tags($value);
            }
        });

        return $data;
    }

    private function containsSensitiveField(string $field, array $sensitive): bool
    {
        foreach ($sensitive as $pattern) {
            if (stripos($field, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private function calculateRiskLevel(string $action, array $data): string
    {
        $highRiskActions = [
            'user.delete', 'user.ban', 'admin.user_impersonate', 'data.export',
            'security.brute_force', 'security.sql_injection', 'security.xss_attempt'
        ];
        
        $mediumRiskActions = [
            'auth.password_change', 'post.delete', 'user.role_change',
            'security.rate_limit_exceeded', 'auth.failed_login'
        ];

        if (in_array($action, $highRiskActions)) {
            return 'high';
        }

        if (in_array($action, $mediumRiskActions)) {
            return 'medium';
        }

        return 'low';
    }

    private function getSecuritySeverity(string $event): string
    {
        $critical = ['brute_force', 'sql_injection', 'xss_attempt', 'unauthorized_access'];
        $high = ['suspicious_activity', 'rate_limit_exceeded', 'csrf_violation'];

        if (in_array($event, $critical)) {
            return 'critical';
        }

        if (in_array($event, $high)) {
            return 'high';
        }

        return 'medium';
    }

    private function calculateThreatLevel(string $event, array $context): string
    {
        $score = 0;
        
        // Base score by event type
        $eventScores = [
            'brute_force' => 8,
            'sql_injection' => 9,
            'xss_attempt' => 7,
            'suspicious_activity' => 5,
            'rate_limit_exceeded' => 3
        ];
        
        $score += $eventScores[$event] ?? 1;
        
        // Additional factors
        if (isset($context['repeated_attempts']) && $context['repeated_attempts'] > 5) {
            $score += 2;
        }
        
        if (isset($context['from_tor']) && $context['from_tor']) {
            $score += 3;
        }
        
        return match (true) {
            $score >= 8 => 'critical',
            $score >= 5 => 'high',
            $score >= 3 => 'medium',
            default => 'low'
        };
    }

    private function requiresInvestigation(string $event): bool
    {
        return in_array($event, [
            'brute_force', 'sql_injection', 'xss_attempt', 
            'unauthorized_access', 'data_breach'
        ]);
    }

    private function isSensitiveTable(string $table): bool
    {
        return in_array($table, [
            'users', 'payments', 'user_profiles', 'audit_logs',
            'personal_access_tokens', 'password_reset_tokens'
        ]);
    }

    private function isSensitiveAction(string $action): bool
    {
        return in_array($action, $this->sensitiveActions);
    }

    private function getUserAccountStatus(User $user): string
    {
        if ($user->is_banned ?? false) return 'banned';
        if ($user->is_suspended ?? false) return 'suspended';
        if (!$user->hasVerifiedEmail()) return 'unverified';
        return 'active';
    }

    private function triggerRealTimeAlert(string $action, array $logData): void
    {
        // Cache-based rate limiting for alerts
        $alertKey = "security_alert:{$action}:{$logData['user_id']}";
        
        if (!Cache::has($alertKey)) {
            Cache::put($alertKey, true, now()->addMinutes(5));
            
            Log::warning("High-risk activity detected: {$action}", $logData);
            
            // Here you could integrate with external alerting systems
            // like Slack, email, SMS, etc.
        }
    }

    private function alertSecurityTeam(string $event, array $context): void
    {
        Log::critical("Security event requires investigation: {$event}", $context);
        
        // Integration point for external alerting systems
        // Could send to Slack, email security team, create tickets, etc.
    }
}
