<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Age Restrictions
    |--------------------------------------------------------------------------
    |
    | This file contains age-related restrictions for the application.
    |
    */

    'minimum_age' => env('MINIMUM_AGE', 15),
    
    'child_age_threshold' => env('CHILD_AGE_THRESHOLD', 18),
];