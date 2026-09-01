<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local'  => ['driver' => 'local', 'root' => storage_path('app'), 'throw' => false],

        /*
        | Member media.
        |
        | MEDIA_DRIVER switches all three between the local disk and an
        | S3-compatible bucket — Supabase Storage, in this deployment —
        | without a line of application code changing. A container's
        | filesystem resets on every redeploy, so `local` there means a
        | member's photograph survives until the next deploy and no longer.
        |
        | Every one of these stays PRIVATE on either driver. They are served
        | through short-lived signed URLs by MediaController, never from a
        | public bucket URL, and switching storage must not quietly change
        | that. If the bucket is created public, this application's privacy
        | model is bypassed entirely and nothing here will warn you.
        |
        | The S3 keys sit alongside the local ones: the local driver ignores
        | what it does not recognise, so one block serves both rather than
        | two blocks drifting apart.
        */
        'photos' => [
            'driver'     => env('MEDIA_DRIVER', 'local'),
            'root'       => storage_path('app/photos'),
            'visibility' => 'private',
            'throw'      => false,
            'key'        => env('MEDIA_S3_KEY'),
            'secret'     => env('MEDIA_S3_SECRET'),
            'region'     => env('MEDIA_S3_REGION', 'us-east-1'),
            'bucket'     => env('MEDIA_S3_BUCKET', 'member-photos'),
            'endpoint'   => env('MEDIA_S3_ENDPOINT'),
            // Supabase Storage addresses buckets by path, not by subdomain.
            'use_path_style_endpoint' => true,
        ],

        // Connect photos live under a SEPARATE prefix, and on their own
        // bucket when object storage is in use. Wall rule W2 is about them
        // never sharing a location with matrimonial media.
        'connect_photos' => [
            'driver'     => env('MEDIA_DRIVER', 'local'),
            'root'       => storage_path('app/connect_photos'),
            'visibility' => 'private',
            'throw'      => false,
            'key'        => env('MEDIA_S3_KEY'),
            'secret'     => env('MEDIA_S3_SECRET'),
            'region'     => env('MEDIA_S3_REGION', 'us-east-1'),
            'bucket'     => env('MEDIA_S3_CONNECT_BUCKET', 'connect-photos'),
            'endpoint'   => env('MEDIA_S3_ENDPOINT'),
            'use_path_style_endpoint' => true,
        ],

        // Identity documents. Purged after 30 days, and worth a separate
        // bucket with its own retention rule wherever that is possible.
        'kyc' => [
            'driver'     => env('MEDIA_DRIVER', 'local'),
            'root'       => storage_path('app/kyc'),
            'visibility' => 'private',
            'throw'      => false,
            'key'        => env('MEDIA_S3_KEY'),
            'secret'     => env('MEDIA_S3_SECRET'),
            'region'     => env('MEDIA_S3_REGION', 'us-east-1'),
            'bucket'     => env('MEDIA_S3_KYC_BUCKET', 'kyc-documents'),
            'endpoint'   => env('MEDIA_S3_ENDPOINT'),
            'use_path_style_endpoint' => true,
        ],

        // Front-page slideshow art. Marketing media, written straight into
        // the web root: no signed URLs, no expiry, and no chance of a stock
        // photograph ending up on the same disk as a member's face.
        'hero' => ['driver' => 'local', 'root' => public_path('img/hero'),
                    'url' => '/img/hero', 'visibility' => 'public', 'throw' => false],

        'public' => ['driver' => 'local', 'root' => storage_path('app/public'),
                     'url' => env('APP_URL').'/storage', 'visibility' => 'public', 'throw' => false],
    ],

    'links' => [public_path('storage') => storage_path('app/public')],
];
