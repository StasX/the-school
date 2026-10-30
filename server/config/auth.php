<?php

use App\Models\Administrator;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'administrators'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'administrators',
        ],
    ],

    'providers' => [
        'administrators' => [
            'driver' => 'eloquent',
            'model' => Administrator::class,
        ],
    ],

    'passwords' => [
        'administrators' => [
            'provider' => 'administrators',
            'table' => env(
                'AUTH_PASSWORD_RESET_TOKEN_TABLE',
                'password_reset_tokens'
            ),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    'login_rate_limit' => (int) env('LOGIN_RATE_LIMIT', 5),
];
