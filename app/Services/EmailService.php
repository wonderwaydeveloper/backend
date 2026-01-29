<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendVerificationEmail($user, $code)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\VerificationEmail($user, $code));
            Log::info('Verification email sent successfully', [
                'user_email' => $user->email, 
                'code' => $code,
                'mailer' => config('mail.default')
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Verification email failed', [
                'error' => $e->getMessage(), 
                'user_email' => $user->email,
                'mailer' => config('mail.default')
            ]);

            return false;
        }
    }

    public function sendPasswordResetEmail($user, $token)
    {
        try {
            Mail::to($user->email)->queue(new \App\Mail\PasswordResetEmail($user, $token));
            Log::info('Password reset email queued', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Password reset email failed', ['error' => $e->getMessage()]);

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
        try {
            Mail::to($user->email)->send(new \App\Mail\DeviceVerificationEmail($user, $code, $deviceInfo));
            Log::info('Device verification email sent', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Device verification email failed', ['error' => $e->getMessage()]);

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
