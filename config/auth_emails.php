<?php

return [
    'verification' => [
        'expire' => env('EMAIL_VERIFICATION_EXPIRE', 15), // minutes
    ],
    
    'passwords' => [
        'users' => [
            'expire' => env('PASSWORD_RESET_EXPIRE', 15), // minutes
        ],
    ],
];