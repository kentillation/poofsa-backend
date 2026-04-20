<?php

return [

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],
        'dev' => [
            'driver' => 'session',
            'provider' => 'devOnly',
        ],
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],
        'customer_api' => [  // Add this for API token auth
            'driver' => 'sanctum',
            'provider' => 'customers',
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'cashier' => [
            'driver' => 'session',
            'provider' => 'cashiers',
        ],
        'kitchen' => [
            'driver' => 'session',
            'provider' => 'kitchens',
        ],
        'barista' => [
            'driver' => 'session',
            'provider' => 'baristas',
        ],
    ],

    'providers' => [
        'devOnly' => [
            'driver' => 'eloquent',
            'model' => App\Models\DevModel::class,
        ],
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\CustomerModel::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\AdminModel::class,
        ],
        'cashiers' => [
            'driver' => 'eloquent',
            'model' => App\Models\CashierModel::class,
        ],
        'kitchen_personnel' => [
            'driver' => 'eloquent',
            'model' => App\Models\KitchenPersonnelModel::class,
        ],
        'baristas' => [
            'driver' => 'eloquent',
            'model' => App\Models\BaristaModel::class,
        ],
    ],

    'passwords' => [
        'customers' => [
            'provider' => 'customers',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
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
        'kitchen_personnel' => [
            'provider' => 'kitchen_personnel',
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
