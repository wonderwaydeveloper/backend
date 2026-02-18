<?php

return [
    'max_file_size' => [
        'image' => 5 * 1024 * 1024, // 5MB (Twitter standard)
        'video' => 2 * 1024 * 1024 * 1024, // 2GB (Twitter standard)
        'gif' => 15 * 1024 * 1024, // 15MB (Twitter standard)
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
        'post' => ['max_width' => 4096, 'max_height' => 4096], // Twitter standard
    ],

    'video_dimensions' => [
        'max_width' => 1920,
        'max_height' => 1200,
        'max_duration' => 140, // 2:20 minutes (Twitter standard)
    ],

    'image_variants' => [
        'small' => 340,
        'medium' => 680,
        'large' => 1200,
    ],

    'video_qualities' => [
        '240p' => ['width' => 426, 'height' => 240, 'bitrate' => 500],
        '360p' => ['width' => 640, 'height' => 360, 'bitrate' => 800],
        '480p' => ['width' => 854, 'height' => 480, 'bitrate' => 1000],
        '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => 2500],
        '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 5000],
    ],

    'quality' => [
        'image' => 85,
        'thumbnail' => 80,
    ],

    'storage_disk' => env('MEDIA_STORAGE_DISK', 'public'),
];
