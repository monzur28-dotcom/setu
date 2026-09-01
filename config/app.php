<?php

return [
    'name'     => env('APP_NAME', 'SheTu'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost'),
    // Store in UTC and render in the viewer's zone. A product with members
    // in Dhaka, Toronto and Sydney has no single "local" time, and every
    // date it renders in a fixed offset is wrong for most of them.
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    // Bangla is the default language of this product, not a translation layer.
    'locale'          => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale'    => 'en_US',

    'cipher' => 'AES-256-CBC',
    'key'    => env('APP_KEY'),

    'maintenance' => [
        'driver' => 'file',
    ],
];
