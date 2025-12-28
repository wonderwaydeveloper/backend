<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Application Firewall (WAF)
    |--------------------------------------------------------------------------
    |
    | Configuration for the Web Application Firewall
    |
    */
    'waf' => [
        'enabled' => env('SECURITY_WAF_ENABLED', true),
        'threat_threshold' => env('SECURITY_THREAT_THRESHOLD', 50),
        'ip_block_duration' => env('SECURITY_IP_BLOCK_DURATION', 3600), // 1 hour
        'log_threats' => env('SECURITY_LOG_THREATS', true),
        'custom_rules_enabled' => env('SECURITY_CUSTOM_RULES_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting
    |
    */
    'rate_limiting' => [
        'enabled' => env('SECURITY_RATE_LIMIT_ENABLED', true),
        'per_minute' => env('SECURITY_RATE_LIMIT_PER_MINUTE', 60),
        'per_hour' => env('SECURITY_RATE_LIMIT_PER_HOUR', 1000),
        'burst_threshold' => env('SECURITY_BURST_THRESHOLD', 10),
        'burst_window' => env('SECURITY_BURST_WINDOW', 10), // seconds
        'login_attempts' => env('SECURITY_LOGIN_ATTEMPTS', 5),
        'login_lockout' => env('SECURITY_LOGIN_LOCKOUT', 900), // 15 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation
    |--------------------------------------------------------------------------
    |
    | Configuration for input validation and sanitization
    |
    */
    'input_validation' => [
        'enabled' => env('SECURITY_INPUT_VALIDATION_ENABLED', true),
        'strict_mode' => env('SECURITY_STRICT_MODE', true),
        'max_input_length' => env('SECURITY_MAX_INPUT_LENGTH', 10000),
        'allowed_html_tags' => ['b', 'i', 'u', 'strong', 'em', 'br', 'p'],
        'blocked_extensions' => [
            'php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp',
            'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar',
            'sh', 'py', 'pl', 'rb', 'cgi', 'htaccess'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configuration for security headers
    |
    */
    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('SECURITY_HSTS_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', false),
        ],
        'csp' => [
            'enabled' => env('SECURITY_CSP_ENABLED', true),
            'policy' => env('SECURITY_CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self' https:; media-src 'self' https:; object-src 'none'; frame-src 'none';"),
        ],
        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('SECURITY_X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Configuration for secure file uploads
    |
    */
    'file_upload' => [
        'max_size' => env('SECURITY_MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_video_types' => ['mp4', 'webm', 'ogg'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'txt'],
        'scan_for_malware' => env('SECURITY_SCAN_MALWARE', false),
        'quarantine_suspicious' => env('SECURITY_QUARANTINE_SUSPICIOUS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security
    |--------------------------------------------------------------------------
    |
    | Configuration for password security requirements
    |
    */
    'password' => [
        'min_length' => env('SECURITY_PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('SECURITY_PASSWORD_UPPERCASE', true),
        'require_lowercase' => env('SECURITY_PASSWORD_LOWERCASE', true),
        'require_numbers' => env('SECURITY_PASSWORD_NUMBERS', true),
        'require_special_chars' => env('SECURITY_PASSWORD_SPECIAL', true),
        'check_common_passwords' => env('SECURITY_PASSWORD_CHECK_COMMON', true),
        'history_limit' => env('SECURITY_PASSWORD_HISTORY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configuration for session security
    |
    */
    'session' => [
        'secure_cookies' => env('SECURITY_SECURE_COOKIES', true),
        'http_only' => env('SECURITY_HTTP_ONLY', true),
        'same_site' => env('SECURITY_SAME_SITE', 'strict'),
        'regenerate_on_login' => env('SECURITY_REGENERATE_ON_LOGIN', true),
        'timeout' => env('SECURITY_SESSION_TIMEOUT', 7200), // 2 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for security monitoring
    |
    */
    'monitoring' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        'alert_threshold' => env('SECURITY_ALERT_THRESHOLD', 100),
        'alert_email' => env('SECURITY_ALERT_EMAIL', 'security@wonderway.com'),
        'log_retention_days' => env('SECURITY_LOG_RETENTION', 90),
        'real_time_alerts' => env('SECURITY_REAL_TIME_ALERTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist/Blacklist
    |--------------------------------------------------------------------------
    |
    | Configuration for IP-based access control
    |
    */
    'ip_control' => [
        'whitelist_enabled' => env('SECURITY_IP_WHITELIST_ENABLED', false),
        'whitelist' => explode(',', env('SECURITY_IP_WHITELIST', '')),
        'blacklist_enabled' => env('SECURITY_IP_BLACKLIST_ENABLED', true),
        'blacklist' => explode(',', env('SECURITY_IP_BLACKLIST', '')),
        'auto_block_enabled' => env('SECURITY_AUTO_BLOCK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    |
    | Configuration for API-specific security
    |
    */
    'api' => [
        'require_https' => env('SECURITY_API_REQUIRE_HTTPS', true),
        'cors_enabled' => env('SECURITY_API_CORS_ENABLED', true),
        'allowed_origins' => explode(',', env('SECURITY_API_ALLOWED_ORIGINS', 'http://localhost:3000')),
        'api_key_required' => env('SECURITY_API_KEY_REQUIRED', false),
        'request_signing' => env('SECURITY_API_REQUEST_SIGNING', false),
    ],
];