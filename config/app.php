<?php

return [
    'name'     => env('APP_NAME', 'Setu'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Dhaka',

    // Bangla is the default language of this product, not a translation layer.
    'locale'          => env('APP_LOCALE', 'bn'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale'    => 'en_US',

    'cipher' => 'AES-256-CBC',
    'key'    => env('APP_KEY'),

    'maintenance' => [
        'driver' => 'file',
    ],
];
