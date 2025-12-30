<?php

return [
    'admin_allowed_ips' => [
        '127.0.0.1',
        '::1',
        // Add production IPs here
    ],
    
    'session_timeout' => 3600, // 1 hour
    
    'max_login_attempts' => 3,
    
    'lockout_duration' => 900, // 15 minutes
    
    'require_2fa' => env('ADMIN_REQUIRE_2FA', true),
];