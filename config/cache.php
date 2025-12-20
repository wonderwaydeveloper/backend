<?php

return [
    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'memcached' => [
            'driver' => 'memcached',
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'database' => [
            'driver' => 'database',
            'connection' => null,
            'table' => 'cache',
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'wonderway_cache_'),

    'ttl' => [
        'user_profile' => 3600,
        'feed' => 1800,
        'search_results' => 300,
        'trending_hashtags' => 3600,
        'user_suggestions' => 21600,
        'post_stats' => 1800,
    ],
];
