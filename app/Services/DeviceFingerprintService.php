<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceFingerprintService
{
    /**
     * Generate a consistent device fingerprint
     */
    public static function generate(Request $request): string
    {
        // More secure fingerprinting without relying solely on IP
        $components = [
            $request->userAgent() ?? '',
            $request->header('accept-language', ''),
            $request->header('accept-encoding', ''),
            $request->header('sec-ch-ua', ''),
            $request->header('sec-ch-ua-platform', ''),
            $request->header('sec-ch-ua-mobile', ''),
            // Use only first 3 octets of IP for better privacy
            self::maskIP($request->ip() ?? ''),
            // Add screen resolution if available
            $request->header('sec-ch-viewport-width', ''),
            $request->header('sec-ch-viewport-height', '')
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    private static function maskIP(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            // Mask last octet for IPv4
            return implode('.', array_slice($parts, 0, 3)) . '.0';
        }
        return $ip; // Return as-is for IPv6 or invalid IPs
    }
}