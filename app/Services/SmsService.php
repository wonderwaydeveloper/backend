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
            Log::info('SMS Fallback Mode', ['phone' => $phoneNumber, 'otp' => $otp]);
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

            Log::info('SMS sent', ['phone' => $phoneNumber, 'sid' => $message->sid]);

            return true;
        } catch (\Exception $e) {
            Log::error('SMS failed', ['phone' => $phoneNumber, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function sendVerificationCode($phoneNumber, $code)
    {
        return $this->sendOtp($phoneNumber, $code);
    }

    public function sendLoginCode($phoneNumber, $code)
    {
        // Fallback mode when no Twilio client
        if (!$this->client) {
            Log::info('SMS Login Code Fallback', ['phone' => $phoneNumber, 'code' => $code]);
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

            Log::info('SMS login code sent', ['phone' => $phoneNumber, 'sid' => $message->sid]);
            return true;
        } catch (\Exception $e) {
            Log::error('SMS login code failed', ['phone' => $phoneNumber, 'error' => $e->getMessage()]);
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
