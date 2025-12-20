<?php

return [
    'post' => [
        'max_length' => env('POST_MAX_LENGTH', 280),
        'max_images' => env('POST_MAX_IMAGES', 4),
        'image_max_size' => env('POST_IMAGE_MAX_SIZE', 2048), // KB
    ],

    'message' => [
        'max_length' => env('MESSAGE_MAX_LENGTH', 1000),
        'media_max_size' => env('MESSAGE_MEDIA_MAX_SIZE', 10240), // KB
    ],

    'rate_limits' => [
        'login' => env('RATE_LIMIT_LOGIN', '5,5'),
        'register' => env('RATE_LIMIT_REGISTER', '3,60'),
        'post' => env('RATE_LIMIT_POST', '10,1'),
        'follow' => env('RATE_LIMIT_FOLLOW', '30,1'),
        'message' => env('RATE_LIMIT_MESSAGE', '60,1'),
    ],

    'cache' => [
        'trending_ttl' => env('CACHE_TRENDING_TTL', 3600),
        'user_suggestions_ttl' => env('CACHE_USER_SUGGESTIONS_TTL', 1800),
    ],
];
