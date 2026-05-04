<?php

return [
    // Microservices Configuration
    'authentication' => [
        'url' => env('AUTHENTICATION_SERVICE_URL', 'http://localhost:8001'),
        'timeout' => 30,
    ],

    'patient' => [
        'url' => env('PATIENT_SERVICE_URL', 'http://localhost:8002'),
        'timeout' => 30,
    ],

    'triage' => [
        'url' => env('TRIAGE_SERVICE_URL', 'http://localhost:8003'),
        'timeout' => 30,
    ],

    'configuration' => [
        'url' => env('CONFIGURATION_SERVICE_URL', 'http://localhost:8004'),
        'timeout' => 30,
    ],

    'laboratory' => [
        'url' => env('LABORATORY_SERVICE_URL', 'http://localhost:8006'),
        'timeout' => 30,
    ],

    'appointment' => [
        'url' => env('APPOINTMENT_SERVICE_URL', 'http://localhost:8007'),
        'timeout' => 30,
    ],

    'gateway' => [
        'url' => env('API_GATEWAY_URL', 'http://localhost:3000'),
    ],

    // Service-to-Service Authentication Token
    'service_token' => env('SERVICE_TOKEN', 'shared-secret-token-for-microservices-2024'),

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
