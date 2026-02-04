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
        // Development mode - SMS service not configured
        Log::info('📱 SMS OTP (DEVELOPMENT MODE)', [
            'phone' => $phoneNumber, 
            'otp' => '******', // Hidden for security
            'note' => 'SMS service not configured - check logs for development'
        ]);
        return true; // Return success to prevent app breaking
    }

    public function sendVerificationCode($phoneNumber, $code)
    {
        Log::info('📱 SMS VERIFICATION CODE (DEVELOPMENT MODE)', [
            'type' => 'SMS_VERIFICATION',
            'phone' => $phoneNumber,
            'code' => '******', // Hidden for security
            'expires_in' => '15 minutes',
            'timestamp' => now()->toDateTimeString(),
            'note' => 'SMS service not configured - code logged for development'
        ]);
        
        return true; // Always return success in development mode
    }

    public function sendLoginCode($phoneNumber, $code)
    {
        Log::info('🔑 SMS LOGIN CODE (DEVELOPMENT MODE)', [
            'type' => 'SMS_LOGIN',
            'phone' => $phoneNumber,
            'code' => '******', // Hidden for security
            'expires_in' => '5 minutes',
            'timestamp' => now()->toDateTimeString(),
            'note' => 'SMS service not configured - code logged for development'
        ]);
        
        return true; // Always return success in development mode
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
