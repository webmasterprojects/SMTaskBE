<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
        'http://localhost:3004',
        'http://127.0.0.1:3004',
        'http://localhost:4000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:3002',
        'http://127.0.0.1:5173',
        'https://fire.serversui.com',
        'http://fire.serversui.com',
        'https://fire.fieldacts.com',
        'http://fire.fieldacts.com',
        'https://demo.fieldacts.com',
        'http://demo.fieldacts.com',
        'https://peru-grasshopper-519927.hostingersite.com',
        'https://pg.servicematrix.com.au',
        'http://pg.servicematrix.com.au',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
