<?php

return [
    // HTTP Status Codes
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

    // Threat Detection
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

    // Bot Detection
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

    // Security Monitoring
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

    // Rate Limiting
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

    // Captcha
    'captcha' => [
        'failed_attempts_threshold' => 3,
        'min_score' => 0.5,
    ],

    // File Security
    'file_security' => [
        'max_size' => 10485760, // 10MB
        'max_image_dimension' => 4096,
    ],

    // Password Security
    'password_security' => [
        'history_limit' => 5,
        'min_age_hours' => 1,
        'max_age_days' => 90,
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

    // Cache TTL
    'cache' => [
        'last_seen' => 60,
        'bot_requests' => 600, // 10 minutes
        'bot_behavior' => 3600, // 1 hour
    ],
];
