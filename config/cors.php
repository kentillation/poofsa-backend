<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'reference/*', 'broadcasting/auth', 'storage/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:8082',
        'http://localhost:3000',
        'capacitor://localhost',
        'https://localhost',
        'http://localhost',
        'capacitor://localhost:8080',
        'https://locinder.vercel.app',
        'https://locinder-admin.vercel.app',
        'https://locinder.poofsa.com',
        'https://locinder-admin.poofsa.com',
        'https://poofsa-vent.vercel.app',
        'https://poofsa-tend.vercel.app',
        'https://poofsa-bris.vercel.app',
        'https://poofsa-cook.vercel.app',
        'https://poofsa-des.vercel.app',
        'https://poofsa-stom.vercel.app',
        'https://poofsa-yals.vercel.app',
        'https://poofsa-dev.vercel.app',
        'https://poofsa-marketplace.vercel.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
