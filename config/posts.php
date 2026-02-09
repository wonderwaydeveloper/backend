<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Post Edit Timeout
    |--------------------------------------------------------------------------
    |
    | The number of minutes after post creation that a user can edit their post.
    | After this time, posts cannot be edited.
    |
    */
    'edit_timeout_minutes' => env('POST_EDIT_TIMEOUT_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Thread Limits
    |--------------------------------------------------------------------------
    */
    'max_thread_posts' => 25,
];
