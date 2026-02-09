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
    
    'content' => [
        'post' => [
            'max_length' => 280,
            'max_links' => 2,
            'max_mentions' => 5,
        ],
        'comment' => [
            'max_length' => 280,
        ],
        'message' => [
            'max_length' => 1000,
        ],
        'community' => [
            'name_max_length' => 100,
            'description_max_length' => 500,
        ],
    ],
    
    'file_upload' => [
        'image' => [
            'max_size_kb' => 5120, // 5MB
            'allowed_mimes' => 'jpeg,png,jpg,gif,webp',
        ],
        'avatar' => [
            'max_size_kb' => 2048, // 2MB
            'allowed_mimes' => 'jpeg,png,jpg,gif',
        ],
        'video' => [
            'max_size_kb' => 102400, // 100MB
            'allowed_mimes' => 'mp4,mov,avi,mkv,webm',
        ],
        'media_general' => [
            'max_size_kb' => 10240, // 10MB
            'allowed_mimes' => 'jpeg,png,gif,webp,mp4,mov',
        ],
    ],
];