<?php

return [
    'authenticate' => [
        'base_uri' => env('AUTH_SERVICE_BASE_URI'),
        'secret' => env('AUTH_SERVICE_SECRET')
    ],
    'risk' => [
        'base_uri' => env('RISK_SERVICE_BASE_URI'),
        'secret' => env('RISK_SERVICE_SECRET'),
    ],
    'comm' => [
        'base_uri' => env('COMM_SERVICE_BASE_URI'),
        'secret' => env('COMM_SERVICE_SECRET'),
    ],
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_ACCOUNT_TOKEN'),
        'key' => env('TWILIO_API_KEY'),
        'secret' => env('TWILIO_API_SECRET')
     ],
     'stripe' => [
        'model'  => App\Models\User::class,
        'key'    => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
];