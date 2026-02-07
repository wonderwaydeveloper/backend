<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeviceFingerprintService
{
    private static array $trustedHeaders = [
        'sec-ch-ua',
        'sec-ch-ua-platform', 
        'sec-ch-ua-mobile',
        'accept-language',
        'accept-encoding'
    ];
    
    /**
     * Generate a secure device fingerprint
     */
    public static function generate(Request $request): string
    {
        $fingerprintComponents = config('authentication.device.fingerprint_components', [
            'user_agent', 'accept_language', 'ip_address'
        ]);
        
        $components = [];
        
        if (in_array('user_agent', $fingerprintComponents)) {
            $components[] = self::getBrowserFingerprint($request);
        }
        
        if (in_array('ip_address', $fingerprintComponents)) {
            $components[] = self::getNetworkFingerprint($request);
        }
        
        if (in_array('accept_language', $fingerprintComponents)) {
            $components[] = self::getSystemFingerprint($request);
        }
        
        // Temporal component (changes daily)
        $components[] = self::getTemporalComponent();
        
        // Add salt for additional security
        $salt = config('app.key');
        
        return hash('sha256', $salt . implode('|', array_filter($components)));
    }
    
    private static function getBrowserFingerprint(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';
        
        // Extract stable browser characteristics
        $browserData = [
            self::extractBrowserEngine($userAgent),
            self::extractBrowserVersion($userAgent),
            $request->header('sec-ch-ua', ''),
            $request->header('sec-ch-ua-platform', ''),
        ];
        
        return hash('md5', implode(':', $browserData));
    }
    
    private static function getNetworkFingerprint(Request $request): string
    {
        $ip = $request->ip() ?? '';
        
        // Use subnet instead of full IP for privacy
        $subnet = self::getSubnet($ip);
        
        // Add timezone as network indicator
        $timezone = $request->header('timezone', date_default_timezone_get());
        
        return hash('md5', $subnet . ':' . $timezone);
    }
    
    private static function getSystemFingerprint(Request $request): string
    {
        $systemData = [
            $request->header('accept-language', ''),
            $request->header('accept-encoding', ''),
            // Screen resolution (if available)
            $request->header('sec-ch-viewport-width', ''),
            $request->header('sec-ch-viewport-height', ''),
        ];
        
        return hash('md5', implode(':', $systemData));
    }
    
    private static function getTemporalComponent(): string
    {
        // Changes weekly to balance security and user experience
        return date('Y-W'); // Year-Week format
    }
    
    private static function getSubnet(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: Use /24 subnet (first 3 octets)
            $parts = explode('.', $ip);
            return implode('.', array_slice($parts, 0, 3)) . '.0/24';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: Use /64 subnet
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::/64';
        }
        
        return 'unknown';
    }
    
    private static function extractBrowserEngine(string $userAgent): string
    {
        if (str_contains($userAgent, 'WebKit')) return 'webkit';
        if (str_contains($userAgent, 'Gecko')) return 'gecko';
        if (str_contains($userAgent, 'Trident')) return 'trident';
        return 'unknown';
    }
    
    private static function extractBrowserVersion(string $userAgent): string
    {
        // Extract major version only for stability
        if (preg_match('/Chrome\/(\d+)/', $userAgent, $matches)) {
            return 'chrome-' . $matches[1];
        }
        if (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) {
            return 'firefox-' . $matches[1];
        }
        if (preg_match('/Safari\/(\d+)/', $userAgent, $matches)) {
            return 'safari-' . $matches[1];
        }
        return 'unknown';
    }
    
    /**
     * Validate fingerprint integrity
     */
    public static function validate(string $fingerprint, Request $request): bool
    {
        $expectedFingerprint = self::generate($request);
        return hash_equals($expectedFingerprint, $fingerprint);
    }
    
    /**
     * Generate a temporary fingerprint for verification
     */
    public static function generateTemporary(Request $request): string
    {
        $baseFingerprint = self::generate($request);
        $timestamp = time();
        
        // Valid for 15 minutes
        return hash('sha256', $baseFingerprint . ':' . floor($timestamp / 900));
    }
}