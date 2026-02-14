<?php

return [
    'max_file_size' => [
        'image' => 5 * 1024 * 1024, // 5MB
        'video' => 512 * 1024 * 1024, // 512MB
        'document' => 10 * 1024 * 1024, // 10MB
    ],

    'allowed_mime_types' => [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ],

    'image_dimensions' => [
        'avatar' => ['width' => 400, 'height' => 400],
        'cover' => ['width' => 1200, 'height' => 400],
        'story' => ['width' => 1080, 'height' => 1920],
        'post' => ['max_width' => 1200],
    ],

    'thumbnail_sizes' => [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 600, 'height' => 600],
    ],

    'quality' => [
        'image' => 85,
        'thumbnail' => 80,
    ],

    'storage_disk' => env('MEDIA_STORAGE_DISK', 'public'),
];
