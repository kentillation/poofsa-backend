<?php

return [

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],
        'admin-api' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'cashier' => [
            'driver' => 'session',
            'provider' => 'cashiers',
        ],
        'cashier-api' => [
            'driver' => 'sanctum',
            'provider' => 'cashiers',
        ],
        'kitchen' => [
            'driver' => 'session',
            'provider' => 'kitchens',
        ],
        'kitchen-api' => [
            'driver' => 'sanctum',
            'provider' => 'kitchens',
        ],
        'barista' => [
            'driver' => 'session',
            'provider' => 'baristas',
        ],
        'barista-api' => [
            'driver' => 'sanctum',
            'provider' => 'baristas',
        ],
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\AdminModel::class,
        ],
        'cashiers' => [
            'driver' => 'eloquent',
            'model' => App\Models\CashierModel::class,
        ],
        'kitchens' => [
            'driver' => 'eloquent',
            'model' => App\Models\KitchenModel::class,
        ],
        'baristas' => [
            'driver' => 'eloquent',
            'model' => App\Models\BaristaModel::class,
        ],
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
        'cashiers' => [
            'provider' => 'cashiers',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
        'kitchens' => [
            'provider' => 'kitchens',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
        'baristas' => [
            'provider' => 'baristas',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    
    'password_timeout' => 10800,

];
