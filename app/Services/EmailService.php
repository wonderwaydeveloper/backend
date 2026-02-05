<?php

namespace App\Services;

use Illuminate\Support\Facades\{Log, Mail, Cache};
use App\Services\AuditTrailService;

class EmailService
{
    public function __construct(
        private AuditTrailService $auditService,
        private RateLimitingService $rateLimiter
    ) {}

    public function sendVerificationEmail($user, $code)
    {
        if (!$this->canSendEmail($user->email, 'verification')) {
            Log::warning('Email rate limit exceeded', ['email' => $user->email, 'type' => 'verification']);
            return false;
        }

        if (!$this->validateEmailSecurity($user->email)) {
            Log::warning('Email security validation failed', ['email' => $user->email]);
            return false;
        }


        
        Log::info('📧 VERIFICATION CODE SENT', [
            'type' => 'EMAIL_VERIFICATION',
            'email' => $this->maskEmail($user->email),
            'name' => $user->name ?? 'Unknown',
            'code' => '******',
            'expires_in' => config('authentication.email.verification_expire_minutes', 15) . ' minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            Mail::to($user->email)->send(new \App\Mail\VerificationEmail($user, $code));
            
            $this->auditService->logSecurityEvent('email_sent', [
                'type' => 'verification',
                'recipient' => $this->maskEmail($user->email)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Verification email failed', [
                'error' => $e->getMessage(), 
                'user_email' => $this->maskEmail($user->email)
            ]);
            return false;
        }
    }

    public function sendPasswordResetEmail($user, $code)
    {
        if (!$this->canSendEmail($user->email, 'password_reset')) {
            Log::warning('Email rate limit exceeded', ['email' => $user->email, 'type' => 'password_reset']);
            return false;
        }

        if (!$this->validateEmailSecurity($user->email)) {
            return false;
        }


        
        Log::info('🔑 PASSWORD RESET CODE SENT', [
            'type' => 'PASSWORD_RESET',
            'email' => $this->maskEmail($user->email),
            'name' => $user->name ?? 'Unknown',
            'code' => '******',
            'expires_in' => config('authentication.password.reset.expire_minutes', 15) . ' minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            Mail::to($user->email)->queue(new \App\Mail\PasswordResetEmail($user, $code));
            
            $this->auditService->logSecurityEvent('email_sent', [
                'type' => 'password_reset',
                'recipient' => $this->maskEmail($user->email)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Password reset email failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendDeviceVerificationEmail($user, $code, $deviceInfo)
    {
        if (!$this->canSendEmail($user->email, 'device_verification')) {
            return false;
        }


        
        Log::info('📱 DEVICE VERIFICATION CODE SENT', [
            'type' => 'DEVICE_VERIFICATION',
            'email' => $this->maskEmail($user->email),
            'name' => $user->name ?? 'Unknown',
            'code' => '******',
            'device' => 'Unknown Device',
            'ip' => $this->maskIp($deviceInfo['ip'] ?? 'Unknown IP'),
            'location' => $deviceInfo['location'] ?? 'Unknown Location',
            'expires_in' => config('authentication.email.verification_expire_minutes', 15) . ' minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            Mail::to($user->email)->send(new \App\Mail\DeviceVerificationEmail($user, $code, $deviceInfo));
            
            $this->auditService->logSecurityEvent('email_sent', [
                'type' => 'device_verification',
                'recipient' => $this->maskEmail($user->email),
                'device_ip' => $this->maskIp($deviceInfo['ip'] ?? '')
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Device verification email failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendNotificationEmail($user, $notification)
    {
        try {
            Mail::to($user->email)->queue(new \App\Mail\NotificationEmail($user, $notification));
            Log::info('Notification email queued', ['user_id' => $user->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Notification email failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendBulkEmail($users, $subject, $view, $data = [])
    {
        try {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(new \App\Mail\BulkEmail($user, $subject, $view, $data));
            }
            Log::info('Bulk emails queued', ['count' => count($users)]);
            return true;
        } catch (\Exception $e) {
            Log::error('Bulk email failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function sendSecurityAlert($user, $alertData)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\SecurityAlertEmail($user, $alertData));
            Log::info('Security alert email sent', ['user_id' => $user->id, 'event' => $alertData['event']]);
            return true;
        } catch (\Exception $e) {
            Log::error('Security alert email failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function canSendEmail(string $email, string $type): bool
    {
        $result = $this->rateLimiter->checkLimit("email.{$type}", $email);
        return $result['allowed'];
    }



    private function validateEmailSecurity(string $email): bool
    {
        // Check against blacklisted domains
        $blacklist = config('authentication.email.blacklist_domains', []);
        $domain = substr(strrchr($email, '@'), 1);
        
        if (in_array($domain, $blacklist)) {
            Log::warning('Blacklisted email domain', ['email' => $this->maskEmail($email), 'domain' => $domain]);
            return false;
        }

        return true;
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***INVALID***';
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        $maskedUsername = substr($username, 0, 2) . str_repeat('*', max(0, strlen($username) - 2));
        
        return $maskedUsername . '@' . $domain;
    }

    private function maskIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return implode('.', array_slice($parts, 0, 3)) . '.***';
        }
        
        return 'Unknown IP';
    }
}
