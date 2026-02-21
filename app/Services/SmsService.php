<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsService
{
    private $client;
    private $fromNumber;

    public function __construct()
    {
        // Check if Twilio credentials are available
        if (config('services.twilio.account_sid') && config('services.twilio.auth_token')) {
            $this->client = new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );
            $this->fromNumber = config('services.twilio.phone_number');
        } else {
            // Fallback mode - no actual SMS sending
            $this->client = null;
            $this->fromNumber = null;
        }
    }

    public function sendOtp($phoneNumber, $otp)
    {
        if ($this->client) {
            try {
                $this->client->messages->create(
                    $phoneNumber,
                    [
                        'from' => $this->fromNumber,
                        'body' => "Your OTP is: {$otp}"
                    ]
                );
                Log::info('SMS OTP sent', ['phone' => $phoneNumber]);
                return true;
            } catch (\Exception $e) {
                Log::error('SMS OTP failed', ['error' => $e->getMessage()]);
                return false;
            }
        }
        
        // Development fallback
        Log::info('📱 SMS OTP (DEVELOPMENT MODE)', [
            'phone' => $phoneNumber, 
            'otp' => '******',
            'note' => 'SMS service not configured - check logs for development'
        ]);
        return true;
    }

    public function sendVerificationCode($phoneNumber, $code)
    {
        if ($this->client) {
            try {
                $this->client->messages->create(
                    $phoneNumber,
                    [
                        'from' => $this->fromNumber,
                        'body' => "Your verification code is: {$code}. Valid for " . config('security.email.verification_expire_minutes', 15) . " minutes."
                    ]
                );
                Log::info('SMS verification code sent', ['phone' => $phoneNumber]);
                return true;
            } catch (\Exception $e) {
                Log::error('SMS verification failed', ['error' => $e->getMessage()]);
                return false;
            }
        }
        
        // Development fallback
        Log::info('📱 SMS VERIFICATION CODE (DEVELOPMENT MODE)', [
            'type' => 'SMS_VERIFICATION',
            'phone' => $phoneNumber,
            'code' => '******',
            'expires_in' => config('security.email.verification_expire_minutes', 15) . ' minutes',
            'timestamp' => now()->toDateTimeString(),
            'note' => 'SMS service not configured - code logged for development'
        ]);
        
        return true;
    }

    public function sendLoginCode($phoneNumber, $code)
    {
        if ($this->client) {
            try {
                $this->client->messages->create(
                    $phoneNumber,
                    [
                        'from' => $this->fromNumber,
                        'body' => "Your login code is: {$code}. Valid for " . (config('security.tokens.auto_refresh_threshold', 300) / 60) . " minutes."
                    ]
                );
                Log::info('SMS login code sent', ['phone' => $phoneNumber]);
                return true;
            } catch (\Exception $e) {
                Log::error('SMS login failed', ['error' => $e->getMessage()]);
                return false;
            }
        }
        
        // Development fallback
        Log::info('🔑 SMS LOGIN CODE (DEVELOPMENT MODE)', [
            'type' => 'SMS_LOGIN',
            'phone' => $phoneNumber,
            'code' => '******',
            'expires_in' => (config('security.tokens.auto_refresh_threshold', 300) / 60) . ' minutes',
            'timestamp' => now()->toDateTimeString(),
            'note' => 'SMS service not configured - code logged for development'
        ]);
        
        return true;
    }
    public function sendNotification($phoneNumber, $message)
    {
        // Fallback mode when no Twilio client
        if (!$this->client) {
            Log::info('SMS Notification Fallback', ['phone' => $phoneNumber, 'message' => $message]);
            return true;
        }
        
        try {
            $this->client->messages->create(
                $phoneNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('SMS notification failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
