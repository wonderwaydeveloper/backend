<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SecurityEventLogger
{
    const EVENT_LOGIN_SUCCESS = 'login_success';
    const EVENT_LOGIN_FAILED = 'login_failed';
    const EVENT_SUSPICIOUS_REQUEST = 'suspicious_request';
    const EVENT_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    const EVENT_SPAM_DETECTED = 'spam_detected';
    const EVENT_XSS_ATTEMPT = 'xss_attempt';
    const EVENT_SQL_INJECTION_ATTEMPT = 'sql_injection_attempt';
    const EVENT_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    
    public function logSecurityEvent(string $event, array $data = [], ?Request $request = null): void
    {
        $logData = [
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ];
        
        if ($request) {
            $logData['request'] = [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $this->sanitizeHeaders($request->headers->all()),
            ];
        }
        
        if (auth()->check()) {
            $logData['user'] = [
                'id' => auth()->id(),
                'email' => auth()->user()->email,
            ];
        }
        
        Log::channel('security')->warning($event, $logData);
        
        // For critical events, also log to database
        if ($this->isCriticalEvent($event)) {
            $this->logToDatabase($event, $logData);
        }
    }
    
    public function logLoginAttempt(string $email, bool $success, ?Request $request = null): void
    {
        $event = $success ? self::EVENT_LOGIN_SUCCESS : self::EVENT_LOGIN_FAILED;
        
        $this->logSecurityEvent($event, [
            'email' => $this->maskEmail($email),
            'success' => $success,
        ], $request);
    }
    
    public function logSuspiciousActivity(string $description, array $details = [], ?Request $request = null): void
    {
        $this->logSecurityEvent(self::EVENT_SUSPICIOUS_REQUEST, [
            'description' => $description,
            'details' => $details,
        ], $request);
    }
    
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }
        
        return $headers;
    }
    
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***INVALID_EMAIL***';
        }
        
        $username = $parts[0];
        $domain = $parts[1];\n        
        $maskedUsername = substr($username, 0, 2) . str_repeat('*', max(0, strlen($username) - 2));
        
        return $maskedUsername . '@' . $domain;
    }
    
    private function isCriticalEvent(string $event): bool
    {
        $criticalEvents = [
            self::EVENT_SQL_INJECTION_ATTEMPT,
            self::EVENT_XSS_ATTEMPT,
            self::EVENT_UNAUTHORIZED_ACCESS,
        ];
        
        return in_array($event, $criticalEvents);
    }
    
    private function logToDatabase(string $event, array $data): void
    {
        try {
            \DB::table('security_logs')->insert([
                'event' => $event,
                'data' => json_encode($data),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log security event to database', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}