<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Limits Configuration
    |--------------------------------------------------------------------------
    |
    | This file consolidates all system limits including rate limits,
    | role-based limits, pagination, polls, and posts.
    |
    */

    // ========================================================================
    // RATE LIMITS
    // ========================================================================

    'rate_limits' => [
        'auth' => [
            'login' => '5,1',
            'register' => '10,1',
            'device_verification' => '5,1',
            'resend_code' => '3,1',
            'password_reset' => '5,1',
            'captcha_after_failed' => 3,
        ],
        'social' => [
            'follow' => '400,1440',
            'block' => '10,1',
            'mute' => '20,1',
            'report' => '5,1',
        ],
        'search' => [
            'posts' => '450,15',
            'users' => '180,15',
            'hashtags' => '180,15',
            'all' => '450,15',
            'advanced' => '180,15',
            'suggestions' => '180,15',
        ],
        'trending' => [
            'default' => '75,15',
            'hashtags' => '75,15',
            'posts' => '75,15',
            'users' => '75,15',
            'personalized' => '75,15',
            'velocity' => '180,15',
            'all' => '75,15',
            'stats' => '180,15',
            'refresh' => '15,15',
        ],
        'hashtags' => [
            'trending' => '75,15',
            'search' => '180,15',
            'show' => '900,15',
            'suggestions' => '180,15',
        ],
        'messaging' => [
            'send' => '60,1',
        ],
        'polls' => [
            'create' => '10,1',
            'vote' => '20,1',
            'results' => '60,1',
            'delete' => '10,1',
        ],
        'moderation' => [
            'report' => '10,1',
        ],
        'mentions' => [
            'search' => '180,15',
            'view' => '180,15',
        ],
        'realtime' => [
            'default' => '60,1',
        ],
    ],

    'trending' => [
        'thresholds' => [
            'hashtag_min_posts' => 5,
            'post_min_engagement' => 10,
            'user_min_followers' => 100,
        ],
    ],

    // ========================================================================
    // ROLE-BASED LIMITS
    // ========================================================================

    'roles' => [
        'user' => [
            'media_per_post' => 4,
            'max_file_size_kb' => 5120,
            'posts_per_day' => 100,
            'video_length_seconds' => 140,
            'scheduled_posts' => 0,
            'rate_limit_per_minute' => 60,
            'hd_upload' => false,
            'advertisements' => false,
        ],
        'verified' => [
            'media_per_post' => 4,
            'max_file_size_kb' => 10240,
            'posts_per_day' => 200,
            'video_length_seconds' => 140,
            'scheduled_posts' => 0,
            'rate_limit_per_minute' => 100,
            'hd_upload' => false,
            'advertisements' => false,
        ],
        'premium' => [
            'media_per_post' => 10,
            'max_file_size_kb' => 51200,
            'posts_per_day' => 500,
            'video_length_seconds' => 600,
            'scheduled_posts' => 100,
            'rate_limit_per_minute' => 200,
            'hd_upload' => true,
            'advertisements' => false,
        ],
        'organization' => [
            'media_per_post' => 10,
            'max_file_size_kb' => 102400,
            'posts_per_day' => 1000,
            'video_length_seconds' => 600,
            'scheduled_posts' => 500,
            'rate_limit_per_minute' => 300,
            'hd_upload' => true,
            'advertisements' => true,
        ],
        'moderator' => [
            'media_per_post' => 10,
            'max_file_size_kb' => 51200,
            'posts_per_day' => 500,
            'video_length_seconds' => 600,
            'scheduled_posts' => 100,
            'rate_limit_per_minute' => 200,
            'hd_upload' => true,
            'advertisements' => false,
        ],
        'admin' => [
            'media_per_post' => 20,
            'max_file_size_kb' => 204800,
            'posts_per_day' => 2000,
            'video_length_seconds' => 1200,
            'scheduled_posts' => 1000,
            'rate_limit_per_minute' => 500,
            'hd_upload' => true,
            'advertisements' => true,
        ],
    ],

    'creator_fund' => [
        'base_rate' => 0.001,
        'max_engagement_multiplier' => 0.1,
        'min_views' => 10000,
        'min_quality_score' => 70,
        'min_followers' => 1000,
    ],

    'advertisements' => [
        'default_cost_per_click' => 0.10,
        'default_cost_per_impression' => 0.01,
    ],

    // ========================================================================
    // PAGINATION
    // ========================================================================

    'pagination' => [
        'default' => 20,
        'posts' => 20,
        'messages' => 50,
        'notifications' => 20,
        'users' => 20,
        'comments' => 20,
        'bookmarks' => 20,
        'communities' => 20,
        'follows' => 20,
        'hashtags' => 20,
        'lists' => 20,
        'reports' => 20,
        'reposts' => 20,
        'likes' => 20,
        'trending' => 10,
        'suggestions' => 10,
        'search' => 20,
        'activities' => 50,
        'cache_warmup' => 100,
    ],

    // ========================================================================
    // POLLS
    // ========================================================================

    'polls' => [
        'max_question_length' => env('POLL_MAX_QUESTION_LENGTH', 200),
        'min_options' => env('POLL_MIN_OPTIONS', 2),
        'max_options' => env('POLL_MAX_OPTIONS', 4),
        'max_option_length' => env('POLL_MAX_OPTION_LENGTH', 100),
        'min_duration_hours' => env('POLL_MIN_DURATION_HOURS', 1),
        'max_duration_hours' => env('POLL_MAX_DURATION_HOURS', 168), // 7 days
    ],

    // ========================================================================
    // POSTS
    // ========================================================================

    'posts' => [
        'edit_timeout_minutes' => env('POST_EDIT_TIMEOUT_MINUTES', 60),
        'max_thread_posts' => 25,
    ],
];
