<?php

return [
    'user' => [
        'name' => [
            'max_length' => 50,
        ],
        'email' => [
            'max_length' => 255,
        ],
        'bio' => [
            'max_length' => 500,
        ],
        'location' => [
            'max_length' => 100,
        ],
        'website' => [
            'max_length' => 255,
        ],
    ],
    
    'password' => [
        'min_length' => 8,
    ],
    
    'date' => [
        'before_rule' => 'before:today',
    ],
    
    'search' => [
        'query' => [
            'min_length' => 1,
            'max_length' => 500, // Twitter standard
        ],
        'posts' => [
            'per_page' => 20,
            'max_per_page' => 100, // Twitter standard
        ],
        'users' => [
            'per_page' => 20,
            'max_per_page' => 100, // Twitter standard
        ],
        'hashtags' => [
            'per_page' => 20,
            'max_per_page' => 100, // Twitter standard
        ],
        'rate_limits' => [
            'posts' => 450, // 450 requests per 15 minutes (Twitter standard)
            'users' => 180, // 180 requests per 15 minutes (Twitter standard)
            'window' => 15, // 15 minutes
        ],
    ],
    
    'trending' => [
        'limit' => [
            'default' => 10,
            'max' => 100,
        ],
        'timeframe' => [
            'default' => 24,
            'max' => 720,
        ],
    ],
    
    'content' => [
        'post' => [
            'max_length' => 280,
            'min_length' => 1,
            'max_links' => 2,
            'max_mentions' => 5,
        ],
        'comment' => [
            'max_length' => 280,
            'min_length' => 1,
        ],
        'message' => [
            'max_length' => 1000,
            'min_length' => 1,
        ],
        'community' => [
            'name_max_length' => 100,
            'description_max_length' => 500,
        ],
    ],
    
    'file_upload' => [
        'image' => [
            'max_size_kb' => 5120, // 5MB
            'allowed_types' => ['jpeg', 'png', 'jpg', 'gif', 'webp'],
            'allowed_mimes' => 'jpeg,png,jpg,gif,webp',
        ],
        'avatar' => [
            'max_size_kb' => 2048, // 2MB
            'allowed_types' => ['jpeg', 'png', 'jpg', 'gif'],
            'allowed_mimes' => 'jpeg,png,jpg,gif',
        ],
        'video' => [
            'max_size_kb' => 102400, // 100MB
            'allowed_types' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
            'allowed_mimes' => 'mp4,mov,avi,mkv,webm',
        ],
        'media_general' => [
            'max_size_kb' => 10240, // 10MB
            'allowed_types' => ['jpeg', 'png', 'gif', 'webp', 'mp4', 'mov'],
            'allowed_mimes' => 'jpeg,png,gif,webp,mp4,mov',
        ],
    ],
];