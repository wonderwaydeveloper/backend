<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendVerificationEmail($user, $code)
    {
        Log::info('📧 VERIFICATION CODE SENT', [
            'type' => 'EMAIL_VERIFICATION',
            'email' => $user->email,
            'name' => $user->name ?? 'Unknown',
            'code' => $code,
            'expires_in' => '15 minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            Mail::to($user->email)->send(new \App\Mail\VerificationEmail($user, $code));
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Verification email failed', [
                'error' => $e->getMessage(), 
                'user_email' => $user->email
            ]);
            return false;
        }
    }

    public function sendPasswordResetEmail($user, $code)
    {
        Log::info('🔑 PASSWORD RESET CODE SENT', [
            'type' => 'PASSWORD_RESET',
            'email' => $user->email,
            'name' => $user->name ?? 'Unknown',
            'code' => $code,
            'expires_in' => '15 minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            Mail::to($user->email)->queue(new \App\Mail\PasswordResetEmail($user, $code));
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Password reset email failed', ['error' => $e->getMessage()]);
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
    
    public function sendDeviceVerificationEmail($user, $code, $deviceInfo)
    {
        Log::info('📱 DEVICE VERIFICATION CODE SENT', [
            'type' => 'DEVICE_VERIFICATION',
            'email' => $user->email,
            'name' => $user->name ?? 'Unknown',
            'code' => $code,
            'device' => 'Unknown Device',
            'ip' => $deviceInfo['ip'] ?? 'Unknown IP',
            'location' => $deviceInfo['location'] ?? 'Unknown Location',
            'expires_in' => '15 minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            Mail::to($user->email)->send(new \App\Mail\DeviceVerificationEmail($user, $code, $deviceInfo));
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Device verification email failed', ['error' => $e->getMessage()]);
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
}
