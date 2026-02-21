<?php

return [
    'user' => [
        'name' => ['max_length' => 50],
        'email' => ['max_length' => 255],
        'bio' => ['max_length' => 500],
        'location' => ['max_length' => 100],
        'website' => ['max_length' => 255],
    ],
    
    'password' => ['min_length' => 8],
    'date' => ['before_rule' => 'before:today'],
    
    'search' => [
        'query' => ['min_length' => 2, 'max_length' => 100],
        'posts' => ['per_page' => 20, 'max_per_page' => 100],
        'users' => ['per_page' => 20, 'max_per_page' => 100],
        'hashtags' => ['per_page' => 20, 'max_per_page' => 100],
        'rate_limits' => ['posts' => 450, 'users' => 180, 'window' => 15],
    ],
    
    'trending' => [
        'limit' => ['default' => 10, 'max' => 100],
        'timeframe' => ['default' => 24, 'max' => 720],
    ],
    
    'content' => [
        'post' => ['max_length' => 280, 'min_length' => 1, 'max_links' => 2, 'max_mentions' => 5],
        'comment' => ['max_length' => 280, 'min_length' => 1],
        'message' => ['max_length' => 1000, 'min_length' => 1],
        'community' => ['name_max_length' => 100, 'description_max_length' => 500],
    ],
    
    'file_upload' => [
        'image' => ['max_size_kb' => 5120, 'allowed_types' => ['jpeg', 'png', 'jpg', 'gif', 'webp'], 'allowed_mimes' => 'jpeg,png,jpg,gif,webp'],
        'avatar' => ['max_size_kb' => 2048, 'allowed_types' => ['jpeg', 'png', 'jpg', 'gif'], 'allowed_mimes' => 'jpeg,png,jpg,gif'],
        'video' => ['max_size_kb' => 102400, 'allowed_types' => ['mp4', 'mov', 'avi', 'mkv', 'webm'], 'allowed_mimes' => 'mp4,mov,avi,mkv,webm'],
        'media_general' => ['max_size_kb' => 10240, 'allowed_types' => ['jpeg', 'png', 'gif', 'webp', 'mp4', 'mov'], 'allowed_mimes' => 'jpeg,png,gif,webp,mp4,mov'],
    ],
    
    'max' => [
        'name' => 100,
        'title' => 100,
        'description' => 500,
        'content' => 300,
        'url' => 255,
        'token' => 500,
        'reason' => 200,
        'text_short' => 50,
        'text_medium' => 100,
        'text_long' => 200,
        'array_small' => 4,
        'array_medium' => 10,
        'array_large' => 25,
        'age' => 100,
        'percentage' => 100,
        'instances' => 10,
        'sources' => 3,
        'tags' => 5,
        'rules' => 10,
        'media' => 4,
        'attachments' => 10,
        'participants' => 100,
        'poll_options' => 4,
        'thread_posts' => 25,
        'interests' => 10,
        'coupon' => 20,
        'version' => 20,
        'account_number' => 50,
        'routing_number' => 20,
        'alt_text' => 200,
        'banner_size' => 1024,
    ],
    
    'min' => [
        'search' => 1,
        'mention' => 2,
        'community_note' => 10,
        'poll_options' => 2,
        'thread_posts' => 2,
        'moment_posts' => 2,
        'age' => 13,
        'instances' => 1,
        'month' => 1,
        'limit' => 1,
        'participants' => 2,
    ],
];