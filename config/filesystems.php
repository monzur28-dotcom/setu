<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local'  => ['driver' => 'local', 'root' => storage_path('app'), 'throw' => false],

        // Member photos. Served through signed, short-lived URLs only —
        // never a public bucket. (Spec 17.2)
        'photos' => ['driver' => 'local', 'root' => storage_path('app/photos'),
                     'visibility' => 'private', 'throw' => false],

        // Connect photos live under a SEPARATE prefix. Wall rule W2.
        'connect_photos' => ['driver' => 'local', 'root' => storage_path('app/connect_photos'),
                     'visibility' => 'private', 'throw' => false],

        // Identity documents. Separate key in production, purged after 30 days.
        'kyc' => ['driver' => 'local', 'root' => storage_path('app/kyc'),
                     'visibility' => 'private', 'throw' => false],

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
