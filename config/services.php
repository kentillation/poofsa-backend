<?php

return [

    'paymongo' => [
        'mode' => env('PAYMONGO_MODE', 'live'),

        'public_key' => env(
            env('PAYMONGO_MODE') === 'live'
                ? 'PAYMONGO_PUBLIC_KEY_LIVE'
                : 'PAYMONGO_PUBLIC_KEY_TEST'
        ),

        'secret_key' => env(
            env('PAYMONGO_MODE') === 'live'
                ? 'PAYMONGO_SECRET_KEY_LIVE'
                : 'PAYMONGO_SECRET_KEY_TEST'
        ),
        'base_url' => env('PAYMONGO_BASE_URL'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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
