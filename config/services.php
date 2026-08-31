<?php

return [
    'sms' => [
        'driver'    => env('SMS_DRIVER', 'log'),
        'sender_id' => env('SMS_SENDER_ID', 'SETU'),
        'url'       => env('SMS_API_URL'),
        'key'       => env('SMS_API_KEY'),
    ],
    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'sandbox'  => (bool) env('SSLCOMMERZ_SANDBOX', true),
    ],
    'bkash' => [
        'app_key' => env('BKASH_APP_KEY'), 'app_secret' => env('BKASH_APP_SECRET'),
        'username' => env('BKASH_USERNAME'), 'password' => env('BKASH_PASSWORD'),
    ],
    'stripe' => ['key' => env('STRIPE_KEY'), 'secret' => env('STRIPE_SECRET')],
    'kyc'    => ['driver' => env('KYC_DRIVER', 'manual'), 'key' => env('KYC_API_KEY')],
];
