<?php

return [
    'elasticsearch' => [
        'host' => env('ELASTICSEARCH_HOST', 'localhost:9200'),
        'index' => env('ELASTICSEARCH_INDEX', 'microblogging'),
        'username' => env('ELASTICSEARCH_USERNAME'),
        'password' => env('ELASTICSEARCH_PASSWORD'),
    ],
    
    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'endpoints' => [
            'images' => env('CDN_IMAGES_URL', 'https://cdn-images.microblogging.com'),
            'videos' => env('CDN_VIDEOS_URL', 'https://cdn-videos.microblogging.com'),
            'static' => env('CDN_STATIC_URL', 'https://cdn-static.microblogging.com'),
        ],
        'aws' => [
            'cloudfront_distribution_id' => env('AWS_CLOUDFRONT_DISTRIBUTION_ID'),
        ]
    ],
    
    'graphql' => [
        'enabled' => env('GRAPHQL_ENABLED', true),
        'endpoint' => env('GRAPHQL_ENDPOINT', '/graphql'),
        'playground' => env('GRAPHQL_PLAYGROUND', false),
    ]
];