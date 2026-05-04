<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Microservices Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for inter-service communication
    |
    */

    'configuration_service' => [
        'url' => env('CONFIGURATION_SERVICE_URL', 'http://127.0.0.1:8004'),
        'timeout' => env('CONFIGURATION_SERVICE_TIMEOUT', 30),
    ],

    'authentication_service' => [
        'url' => env('AUTHENTICATION_SERVICE_URL', 'http://127.0.0.1:8001'),
        'timeout' => env('AUTHENTICATION_SERVICE_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Authentication
    |--------------------------------------------------------------------------
    |
    | Credentials for service-to-service authentication
    |
    */

    'service_auth' => [
        'client_id' => env('MICROSERVICE_CLIENT_ID', 'patient-service'),
        'client_secret' => env('MICROSERVICE_CLIENT_SECRET', 'patient-service-secret'),
        'token_cache_minutes' => env('SERVICE_TOKEN_CACHE_MINUTES', 30),
    ],
];