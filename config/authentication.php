<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Unified Authentication Configuration
    |--------------------------------------------------------------------------
    */
    
    'password' => [
        'reset' => [
            'expire_minutes' => env('PASSWORD_RESET_EXPIRE', 15),
            'throttle_seconds' => env('PASSWORD_RESET_THROTTLE', 60),
        ],
        'security' => [
            'min_length' => 8,
            'require_letters' => true,
            'require_numbers' => true,
            'require_special_chars' => false,
            'history_limit' => 5,
            'min_age_hours' => 1,
            'max_age_days' => 90,
        ],
    ],
    
    'rate_limiting' => [
        // Authentication
        'login' => ['max_attempts' => 5, 'window_minutes' => 15],
        'register' => ['max_attempts' => 3, 'window_minutes' => 60],
        'password_reset' => ['max_attempts' => 2, 'window_minutes' => 60],
        'device_verify' => ['max_attempts' => 3, 'window_minutes' => 1],
        'email_verification' => ['max_attempts' => 3, 'window_minutes' => 60],
        'reset_verify' => ['max_attempts' => 5, 'window_minutes' => 15],
        'me' => ['max_attempts' => 30, 'window_minutes' => 1],
        'resend' => ['max_attempts' => 5, 'window_minutes' => 60],
        'reset_resend' => ['max_attempts' => 5, 'window_minutes' => 60],
        'phone_login' => ['max_attempts' => 5, 'window_minutes' => 60],
        'phone_resend' => ['max_attempts' => 5, 'window_minutes' => 60],
        'social' => ['max_attempts' => 10, 'window_minutes' => 5],
        'device_resend' => ['max_attempts' => 5, 'window_minutes' => 1],
        'email_password_reset' => ['max_attempts' => 2, 'window_minutes' => 60],
        'email_device_verification' => ['max_attempts' => 3, 'window_minutes' => 60],
        'email_resend' => ['max_attempts' => 3, 'window_minutes' => 60],
        'api_general' => ['max_attempts' => 300, 'window_minutes' => 15],
        'api_login' => ['max_attempts' => 5, 'window_minutes' => 15],
        'api_register' => ['max_attempts' => 3, 'window_minutes' => 60],
        
        // Content Actions (Twitter-like limits)
        'post_create' => ['max_attempts' => 300, 'window_minutes' => 180],
        'post_update' => ['max_attempts' => 100, 'window_minutes' => 60],
        'post_delete' => ['max_attempts' => 100, 'window_minutes' => 60],
        'comment_create' => ['max_attempts' => 300, 'window_minutes' => 180],
        'like' => ['max_attempts' => 1000, 'window_minutes' => 1440],
        'repost' => ['max_attempts' => 300, 'window_minutes' => 180],
        
        // Social Actions
        'follow' => ['max_attempts' => 400, 'window_minutes' => 1440],
        'unfollow' => ['max_attempts' => 400, 'window_minutes' => 1440],
        'block' => ['max_attempts' => 50, 'window_minutes' => 60],
        'mute' => ['max_attempts' => 50, 'window_minutes' => 60],
        
        // Search & Discovery
        'search' => ['max_attempts' => 180, 'window_minutes' => 15],
        'trending' => ['max_attempts' => 75, 'window_minutes' => 15],
        
        // Profile & Settings
        'profile_update' => ['max_attempts' => 5, 'window_minutes' => 60],
        'profile_view' => ['max_attempts' => 500, 'window_minutes' => 15],
        
        // Media
        'media_upload' => ['max_attempts' => 50, 'window_minutes' => 60],
        
        // Messages
        'message_send' => ['max_attempts' => 500, 'window_minutes' => 1440],
        'message_read' => ['max_attempts' => 1000, 'window_minutes' => 15],
    ],
    
    'tokens' => [
        'access_lifetime_seconds' => env('ACCESS_TOKEN_LIFETIME', 7200), // 2h like Twitter
        'refresh_lifetime_seconds' => env('REFRESH_TOKEN_LIFETIME', 604800),
        'remember_lifetime_seconds' => env('REMEMBER_TOKEN_LIFETIME', 1209600),
        'auto_refresh_threshold' => env('AUTO_REFRESH_THRESHOLD', 300),
    ],
    
    'session' => [
        'timeout_seconds' => env('SESSION_TIMEOUT', 7200),
        'concurrent_limit' => env('SESSION_CONCURRENT_LIMIT', 3),
        'fingerprint_validation' => true,
    ],
    
    'email' => [
        'verification_expire_minutes' => env('EMAIL_VERIFICATION_EXPIRE', 15),
        'code_length' => 6,
        'max_code_attempts' => 5,
        'blacklist_domains' => [
            '10minutemail.com',
            'tempmail.org',
            'guerrillamail.com',
        ],
        'templates' => [
            'brand_color' => env('EMAIL_BRAND_COLOR', '#1DA1F2'),
            'logo_url' => env('EMAIL_LOGO_URL', null),
            'support_email' => env('EMAIL_SUPPORT', 'support@' . parse_url(env('APP_URL', 'localhost'), PHP_URL_HOST)),
        ],
    ],
    
    'device' => [
        'verification_enabled' => true,
        'max_devices' => 5,
        'max_inactivity_days' => env('DEVICE_MAX_INACTIVITY_DAYS', 30),
        'fingerprint_components' => [
            'user_agent',
            'accept_language',
            'ip_address'
        ],
    ],
    
    'social' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],
        'apple' => [
            'client_id' => env('APPLE_CLIENT_ID'),
            'client_secret' => env('APPLE_CLIENT_SECRET'),
            'redirect' => env('APPLE_REDIRECT_URI'),
        ],
    ],
    
    'file_upload' => [
        'max_size' => env('FILE_UPLOAD_MAX_SIZE', 10485760), // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'scan_for_malware' => env('FILE_SCAN_MALWARE', true),
        'max_video_duration' => env('FILE_UPLOAD_MAX_VIDEO_DURATION', 300), // 5 minutes
    ],
    
    'waf' => [
        'enabled' => env('SECURITY_WAF_ENABLED', true),
        'threat_threshold' => env('SECURITY_THREAT_THRESHOLD', 60),
        'ip_block_duration' => env('SECURITY_IP_BLOCK_DURATION', 3600),
        'admin_allowed_ips' => [
            '127.0.0.1',
            '::1',
        ],
        'require_2fa' => env('ADMIN_REQUIRE_2FA', true),
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
    ],
    
    'age_restrictions' => [
        'minimum_age' => env('MINIMUM_AGE', 15),
        'child_age_threshold' => env('CHILD_AGE_THRESHOLD', 18),
    ],
    
    'cache' => [
        'trending_ttl' => env('CACHE_TRENDING_TTL', 900), // 15 minutes
        'timeline_ttl' => env('CACHE_TIMELINE_TTL', 300), // 5 minutes
        'user_ttl' => env('CACHE_USER_TTL', 600), // 10 minutes
        'post_ttl' => env('CACHE_POST_TTL', 1800), // 30 minutes
    ],
];