<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security & Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | This file consolidates all security, authentication, and moderation
    | configurations into a single source of truth.
    |
    */

    // ========================================================================
    // AUTHENTICATION
    // ========================================================================

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
        'strength_scores' => [
            'length_multiplier' => 2,
            'max_length_bonus' => 25,
            'letter_bonus' => 10,
            'number_bonus' => 10,
            'special_char_bonus' => 10,
            'mixed_case_bonus' => 5,
            'repeated_penalty' => 10,
            'sequential_penalty' => 10,
            'common_password_penalty' => 25,
            'max_score' => 100,
        ],
    ],

    'tokens' => [
        'access_lifetime_seconds' => env('ACCESS_TOKEN_LIFETIME', 7200), // 2h
        'refresh_lifetime_seconds' => env('REFRESH_TOKEN_LIFETIME', 604800),
        'refresh_token_length' => 60,
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
        'code_min' => 100000,
        'code_max' => 999999,
        'verification_token_length' => 60,
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
        'token_length' => 40,
        'fingerprint_components' => [
            'user_agent',
            'accept_language',
            'ip_address'
        ],
    ],

    'social' => [
        'password_length' => 32,
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

    'age_restrictions' => [
        'minimum_age' => env('MINIMUM_AGE', 15),
        'child_age_threshold' => env('CHILD_AGE_THRESHOLD', 18),
    ],

    // ========================================================================
    // SECURITY
    // ========================================================================

    'http_status' => [
        'unauthorized' => 401,
        'forbidden' => 403,
        'not_found' => 404,
        'csrf_token_mismatch' => 419,
        'unprocessable_entity' => 422,
        'too_many_requests' => 429,
        'internal_server_error' => 500,
        'service_unavailable' => 503,
    ],

    'threat_detection' => [
        'scores' => [
            'sql_injection' => 50,
            'xss' => 40,
            'bot' => 30,
        ],
        'thresholds' => [
            'block' => 80,
            'challenge' => 60,
            'monitor' => 40,
        ],
        'ip_block_duration' => 3600, // 1 hour
    ],

    'bot_detection' => [
        'scores' => [
            'bot_user_agent' => 50,
            'rapid_requests' => 30,
            'suspicious_headers' => 20,
            'suspicious_behavior' => 25,
            'no_javascript' => 15,
            'known_bot_fingerprint' => 40,
        ],
        'thresholds' => [
            'block' => 90,
            'challenge' => 70,
            'monitor' => 50,
        ],
        'rapid_requests' => [
            'max_requests' => 10,
            'window_seconds' => 10,
        ],
        'behavior' => [
            'max_same_page_requests' => 20,
            'min_unique_pages' => 3,
            'min_time_per_page' => 2,
        ],
        'challenge_retry_after' => 30,
        'known_bot_cache_days' => 7,
    ],

    'monitoring' => [
        'alert_thresholds' => [
            'failed_logins' => 10,
            'blocked_requests' => 50,
            'suspicious_activities' => 5,
            'data_breaches' => 1,
            'privilege_escalations' => 1,
        ],
        'risk_levels' => [
            'high' => 50,
            'medium' => 30,
        ],
        'risk_scores' => [
            'new_ip' => 20,
            'high_activity' => 30,
            'failed_logins' => 30,
            'unusual_hours' => 15,
        ],
        'unusual_hours' => [
            'start' => 23,
            'end' => 6,
        ],
        'failed_login_threshold' => 3,
    ],

    'rate_limiting' => [
        'lock_timeout' => 5,
        'default_remaining' => 999,
        'default_retry_after' => 60,
        'default_window' => 60,

        // Authentication
        'auth' => [
            'login' => ['max_attempts' => 5, 'window_minutes' => 1],
            'register' => ['max_attempts' => 3, 'window_minutes' => 1],
            'password_reset' => ['max_attempts' => 3, 'window_minutes' => 1],
            'email_verify' => ['max_attempts' => 10, 'window_minutes' => 1],
            'device_verification' => ['max_attempts' => 5, 'window_minutes' => 1],
        ],

        // Social Actions
        'social' => [
            'follow' => ['max_attempts' => 400, 'window_minutes' => 1440],
            'block' => ['max_attempts' => 10, 'window_minutes' => 1],
            'mute' => ['max_attempts' => 20, 'window_minutes' => 1],
        ],

        // Search
        'search' => [
            'posts' => ['max_attempts' => 450, 'window_minutes' => 15],
            'users' => ['max_attempts' => 180, 'window_minutes' => 15],
            'hashtags' => ['max_attempts' => 180, 'window_minutes' => 15],
            'all' => ['max_attempts' => 180, 'window_minutes' => 15],
            'advanced' => ['max_attempts' => 180, 'window_minutes' => 15],
            'suggestions' => ['max_attempts' => 180, 'window_minutes' => 15],
        ],

        // Messaging
        'messaging' => [
            'send' => ['max_attempts' => 60, 'window_minutes' => 1],
        ],

        // Hashtags
        'hashtags' => [
            'trending' => ['max_attempts' => 75, 'window_minutes' => 15],
            'search' => ['max_attempts' => 180, 'window_minutes' => 15],
            'suggestions' => ['max_attempts' => 180, 'window_minutes' => 15],
            'show' => ['max_attempts' => 900, 'window_minutes' => 15],
        ],

        // Trending
        'trending' => [
            'hashtags' => ['max_attempts' => 75, 'window_minutes' => 15],
            'posts' => ['max_attempts' => 75, 'window_minutes' => 15],
            'users' => ['max_attempts' => 75, 'window_minutes' => 15],
            'personalized' => ['max_attempts' => 75, 'window_minutes' => 15],
            'velocity' => ['max_attempts' => 180, 'window_minutes' => 15],
            'all' => ['max_attempts' => 75, 'window_minutes' => 15],
            'stats' => ['max_attempts' => 180, 'window_minutes' => 15],
            'refresh' => ['max_attempts' => 15, 'window_minutes' => 15],
        ],

        // Polls
        'polls' => [
            'create' => ['max_attempts' => 10, 'window_minutes' => 1],
            'vote' => ['max_attempts' => 20, 'window_minutes' => 1],
            'results' => ['max_attempts' => 60, 'window_minutes' => 1],
            'delete' => ['max_attempts' => 10, 'window_minutes' => 1],
        ],

        // Moderation
        'moderation' => [
            'report' => ['max_attempts' => 5, 'window_minutes' => 1],
        ],

        // Mentions
        'mentions' => [
            'search' => ['max_attempts' => 60, 'window_minutes' => 1],
            'view' => ['max_attempts' => 60, 'window_minutes' => 1],
        ],

        // Realtime
        'realtime' => [
            'default' => ['max_attempts' => 60, 'window_minutes' => 1],
        ],
    ],

    'captcha' => [
        'failed_attempts_threshold' => 3,
        'min_score' => 0.5,
    ],

    'file_security' => [
        'max_size' => 10485760, // 10MB
        'max_image_dimension' => 4096,
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

    'cache' => [
        'last_seen' => 60,
        'bot_requests' => 600, // 10 minutes
        'bot_behavior' => 3600, // 1 hour
    ],

    // ========================================================================
    // MODERATION
    // ========================================================================

    'spam' => [
        'thresholds' => [
            'post' => 70,
            'comment' => 60,
            'user' => 50,
        ],

        'penalties' => [
            'spam_keyword' => 20,
            'multiple_links_high' => 50,
            'multiple_links_medium' => 25,
            'single_link' => 10,
            'suspicious_pattern' => 15,
            'short_content' => 10,
            'excessive_emoji' => 15,
            'new_account' => 20,
            'multiple_reports' => 25,
            'flagged_user' => 30,
            'suspicious_follower_ratio' => 15,
            'high_frequency' => 30,
            'medium_frequency' => 15,
            'duplicate_content' => 25,
        ],

        'limits' => [
            'url_count_high' => 3,
            'url_count_medium' => 2,
            'min_content_length' => 10,
            'max_emoji_count' => 10,
            'new_user_days' => 1,
            'report_threshold' => 5,
            'following_threshold' => 100,
            'follower_threshold' => 10,
            'posts_per_hour_high' => 10,
            'posts_per_hour_medium' => 5,
        ],
    ],
];
