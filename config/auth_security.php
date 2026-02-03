<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Security Settings
    |--------------------------------------------------------------------------
    */
    
    'password' => [
        'min_length' => 8,
        'require_letters' => true,
        'require_numbers' => true,
        'require_special_chars' => false,
        'check_common_passwords' => true,
        'history_limit' => 5,
        'min_age_hours' => 1,
        'max_age_days' => 90,
    ],
    
    'rate_limiting' => [
        'enabled' => env('SECURITY_RATE_LIMIT_ENABLED', true),
        'login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'per_minute' => 60,
        'per_hour' => 1000,
    ],
    
    'device_verification' => [
        'enabled' => true,
        'max_devices' => 5,
        'fingerprint_components' => [
            'user_agent',
            'accept_language',
            'accept_encoding',
            'sec_ch_ua',
            'sec_ch_ua_platform',
            'ip_address'
        ],
    ],
    
    'session' => [
        'timeout' => 7200, // 2 hours
        'concurrent_limit' => 3,
        'fingerprint_validation' => true,
    ],
    
    'waf' => [
        'enabled' => env('SECURITY_WAF_ENABLED', true),
        'threat_threshold' => env('SECURITY_THREAT_THRESHOLD', 60),
        'ip_block_duration' => env('SECURITY_IP_BLOCK_DURATION', 3600),
        'log_threats' => true,
    ],
    
    'headers' => [
        'enabled' => true,
        'hsts' => [
            'enabled' => true,
            'max_age' => 31536000,
            'include_subdomains' => true,
            'preload' => true,
        ],
        'csp' => [
            'enabled' => true,
            'policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'",
        ],
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
    ],
];