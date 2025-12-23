<?php

namespace App\Services;

class FallbackSmsService
{
    public function sendCode($phoneNumber, $code)
    {
        // Fallback: Log SMS instead of sending
        \Log::info("SMS Code for {$phoneNumber}: {$code}");
        
        // Return success to prevent app breaking
        return [
            'success' => true,
            'message' => 'Code sent (development mode)',
            'sid' => 'dev_' . uniqid()
        ];
    }
    
    public function verifyCode($phoneNumber, $code)
    {
        // Development: Accept any 6-digit code
        if (strlen($code) === 6 && is_numeric($code)) {
            return ['valid' => true];
        }
        
        return ['valid' => false];
    }
}