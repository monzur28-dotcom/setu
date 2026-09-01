<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver'         => 'mysql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'setu'),
            'username'       => env('DB_USERNAME', 'root'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',   // Bangla text throughout
            'prefix'         => '',
            'strict'         => true,
            'engine'         => 'InnoDB',
        ],

        /*
        | PostgreSQL — what Supabase provides.
        |
        | DB_URL takes the whole connection string, which is what Supabase
        | hands you and what Railway injects for its own Postgres. The
        | individual variables below are the fallback for a hand-rolled
        | setup; Laravel prefers the URL when both are present.
        |
        | sslmode=require by default: Supabase refuses unencrypted
        | connections, and a default of "prefer" silently downgrades rather
        | than failing loudly, which is the wrong way for that to go wrong.
        |
        | Use the SESSION pooler or a direct connection, not the transaction
        | pooler on 6543. Transaction pooling hands a different backend to
        | each statement, which breaks the prepared statements Laravel's
        | PDO driver relies on.
        */
        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '5432'),
            'database'       => env('DB_DATABASE', 'postgres'),
            'username'       => env('DB_USERNAME', 'postgres'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => env('DB_SSLMODE', 'require'),
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'   => '',
            'foreign_key_constraints' => true,
        ],
    ],

    'migrations' => 'migrations',

    'redis' => [
        'client'  => env('REDIS_CLIENT', 'phpredis'),
        'options' => ['prefix' => Str::slug(env('APP_NAME', 'setu'), '_').'_db_'],
        'default' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => '0',
        ],
    ],
];
