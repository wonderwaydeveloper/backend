<?php

return [
    'max_question_length' => env('POLL_MAX_QUESTION_LENGTH', 200),
    'min_options' => env('POLL_MIN_OPTIONS', 2),
    'max_options' => env('POLL_MAX_OPTIONS', 4),
    'max_option_length' => env('POLL_MAX_OPTION_LENGTH', 100),
    'min_duration_hours' => env('POLL_MIN_DURATION_HOURS', 1),
    'max_duration_hours' => env('POLL_MAX_DURATION_HOURS', 168), // 7 days
];
