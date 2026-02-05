<?php

return [
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
    ],

    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS_PATH'),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'from_email' => env('SENDGRID_FROM_EMAIL'),
    ],

    'google' => [
        'client_id' => config('authentication.social.google.client_id'),
        'client_secret' => config('authentication.social.google.client_secret'),
        'redirect' => config('authentication.social.google.redirect'),
    ],


];
