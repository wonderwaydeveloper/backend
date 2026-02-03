<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign JWT tokens. This should be a long, random
    | string that is kept secret and secure.
    |
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Token TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in seconds) that the token will be valid for.
    | Defaults to 15 minutes for access tokens.
    |
    */
    'access_ttl' => env('JWT_ACCESS_TTL', 900), // 15 minutes

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in seconds) that the refresh token will be
    | valid for. Defaults to 7 days.
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 604800), // 7 days

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the hashing algorithm that will be used to sign the token.
    |
    */
    'algorithm' => 'HS256',

    /*
    |--------------------------------------------------------------------------
    | JWT Issuer
    |--------------------------------------------------------------------------
    |
    | This will be used to set the iss (issuer) claim of the token.
    |
    */
    'issuer' => env('APP_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | JWT Audience
    |--------------------------------------------------------------------------
    |
    | This will be used to set the aud (audience) claim of the token.
    |
    */
    'audience' => env('APP_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Devices Per User
    |--------------------------------------------------------------------------
    |
    | The maximum number of devices/sessions a user can have active at once.
    |
    */
    'max_devices' => env('JWT_MAX_DEVICES', 5),

    /*
    |--------------------------------------------------------------------------
    | Device Fingerprinting
    |--------------------------------------------------------------------------
    |
    | Enable device fingerprinting for additional security.
    |
    */
    'device_fingerprinting' => env('JWT_DEVICE_FINGERPRINTING', true),

    /*
    |--------------------------------------------------------------------------
    | Token Rotation
    |--------------------------------------------------------------------------
    |
    | Enable automatic token rotation on refresh.
    |
    */
    'token_rotation' => env('JWT_TOKEN_ROTATION', true),
];