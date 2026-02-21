<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | This file consolidates all performance-related configurations including
    | cache TTL values and performance monitoring settings.
    |
    */

    // ========================================================================
    // CACHE TTL
    // ========================================================================

    'cache' => [
        'ab_test' => 300,
        'cpu_usage' => 60,
        'memory_usage' => 60,
        'active_connections' => 30,
        'queue_size' => 30,
        'critical_assets' => 3600,
        'conversion_funnel' => 3600,
        'conversion_rate' => 7200,
        'server_stats' => 60,
        'localization' => 3600,
        'post' => 300,
        'user_posts' => 300,
        'following' => 600,
        'timeline' => 300,
        'popular_content' => 1800,
        'search' => 600,
        'trending' => 3600,
        'engagement' => 300,
        'user' => 600,  // From authentication.cache.user_ttl
    ],

    // ========================================================================
    // MONITORING
    // ========================================================================

    'monitoring' => [
        'simulation_delay_seconds' => 0.05,
    ],

    'email' => [
        'rate_limit_delay_seconds' => 5,
    ],
];
