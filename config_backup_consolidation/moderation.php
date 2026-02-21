<?php

return [
    'spam' => [
        'thresholds' => [
            'post' => 70,
            'comment' => 60,
            'user' => 50,
        ],
        
        'penalties' => [
            'spam_keyword' => 20,
            'multiple_links_high' => 50,
            'multiple_links_medium' => 25,
            'single_link' => 10,
            'suspicious_pattern' => 15,
            'short_content' => 10,
            'excessive_emoji' => 15,
            'new_account' => 20,
            'multiple_reports' => 25,
            'flagged_user' => 30,
            'suspicious_follower_ratio' => 15,
            'high_frequency' => 30,
            'medium_frequency' => 15,
            'duplicate_content' => 25,
        ],
        
        'limits' => [
            'url_count_high' => 3,
            'url_count_medium' => 2,
            'min_content_length' => 10,
            'max_emoji_count' => 10,
            'new_user_days' => 1,
            'report_threshold' => 5,
            'following_threshold' => 100,
            'follower_threshold' => 10,
            'posts_per_hour_high' => 10,
            'posts_per_hour_medium' => 5,
        ],
    ],
];
