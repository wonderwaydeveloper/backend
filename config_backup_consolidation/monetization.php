<?php

/**
 * Role-based Limits Configuration
 * 
 * این فایل تنها منبع محدودیتهای نقشها است.
 * 
 * نقشها (6 نقش):
 * - user: کاربر عادی (محدودترین دسترسی)
 * - verified: کاربر تایید شده
 * - premium: کاربر پرمیوم
 * - organization: سازمان
 * - moderator: مدیر
 * - admin: ادمین (بیشترین دسترسی)
 * 
 * محدودیتها:
 * - media_per_post: تعداد مدیا در هر پست
 * - max_file_size_kb: حداکثر حجم فایل (کیلوبایت)
 * - posts_per_day: تعداد پست در روز
 * - video_length_seconds: طول ویدیو (ثانیه)
 * - scheduled_posts: تعداد پستهای زمانبندی شده
 * - rate_limit_per_minute: محدودیت درخواست در دقیقه
 * - hd_upload: امکان آپلود HD
 * - advertisements: امکان ایجاد تبلیغات
 */

return [
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
];
