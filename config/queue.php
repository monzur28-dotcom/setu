<?php

return [
    'default'     => env('QUEUE_CONNECTION', 'database'),
    'connections' => [
        'sync'     => ['driver' => 'sync'],
        'database' => ['driver' => 'database', 'table' => 'jobs', 'queue' => 'default', 'retry_after' => 90],
    ],
    'failed' => ['driver' => 'database-uuids', 'database' => null, 'table' => 'failed_jobs'],
];
