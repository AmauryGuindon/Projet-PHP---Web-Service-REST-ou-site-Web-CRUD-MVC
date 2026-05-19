<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Paths : routes soumises à la politique CORS. On expose l'API + Sanctum.
    |
    | Allowed origins : whitelist explicite. En dev, on accepte le frontend
    | servi par `npx serve` (port 3000) et Vite (port 5173). En production,
    | renseigner uniquement les domaines réels via la variable d'env
    | FRONTEND_URL (séparés par des virgules).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(array_merge(
        [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:8080',
        ],
        explode(',', (string) env('FRONTEND_URL', ''))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'Origin'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
