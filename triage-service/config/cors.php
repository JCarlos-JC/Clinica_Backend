<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8000',  // API Gateway
        'http://localhost:8001',  // Authentication Service
        'http://localhost:8002',  // Patient Service
        'http://localhost:8003',  // Appointment Service
        'http://localhost:8004',  // Configuration Service
        'http://localhost:8006',  // Laboratory Service
        'http://localhost:8007',  // Prescription Service
        'http://localhost:3000',  // React Frontend (Development)
        'http://localhost:3001',  // React Frontend (Alternative Port)
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
