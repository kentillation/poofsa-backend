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
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'reference/*', 'broadcasting/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8080', 
        'http://localhost:8081', 
        'http://localhost:8082', 
        'https://poofsa-vent.vercel.app',
        'https://poofsa-tend.vercel.app',
        'https://poofsa-bris.vercel.app',
        'https://poofsa-cook.vercel.app',
        'https://poofsa-des.vercel.app',
        'https://poofsa-stom.vercel.app',
        'https://poofsa-yals.vercel.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
