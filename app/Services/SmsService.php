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
        // Fallback mode when no Twilio client
        if (!$this->client) {
            Log::info('📱 SMS OTP (FALLBACK MODE)', [
                'phone' => $phoneNumber, 
                'otp' => $otp,
                'note' => 'Twilio not configured - using fallback mode'
            ]);
            return true; // Return success to prevent app breaking
        }
        
        try {
            $message = $this->client->messages->create(
                $phoneNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => "Verification code for " . config('app.name') . ": $otp",
                ]
            );

            Log::info('✅ SMS sent successfully', ['phone' => $phoneNumber, 'sid' => $message->sid]);
            return true;
        } catch (\Exception $e) {
            Log::error('❌ SMS failed', ['phone' => $phoneNumber, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendVerificationCode($phoneNumber, $code)
    {
        Log::info('📱 SMS VERIFICATION CODE SENT', [
            'type' => 'SMS_VERIFICATION',
            'phone' => $phoneNumber,
            'code' => $code,
            'expires_in' => '15 minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        return $this->sendOtp($phoneNumber, $code);
    }

    public function sendLoginCode($phoneNumber, $code)
    {
        Log::info('🔑 SMS LOGIN CODE SENT', [
            'type' => 'SMS_LOGIN',
            'phone' => $phoneNumber,
            'code' => $code,
            'expires_in' => '5 minutes',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // Fallback mode when no Twilio client
        if (!$this->client) {
            return true;
        }
        
        try {
            $message = $this->client->messages->create(
                $phoneNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => "Login code for " . config('app.name') . ": $code. This code expires in 5 minutes.",
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('❌ SMS login code failed', ['phone' => $phoneNumber, 'error' => $e->getMessage()]);
            return false;
        }
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
