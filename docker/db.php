<?php

/**
 * A driver-agnostic database probe for the container entrypoint.
 *
 * Deliberately plain PDO and not artisan: this runs before the migrations
 * table exists, has to report failure through an exit code, and must not
 * depend on the framework booting correctly to tell you the database is
 * unreachable.
 *
 * Usage:
 *   php docker/db.php ready   → exit 0 when the database accepts a connection
 *   php docker/db.php users   → prints the number of rows in `users`
 */

$argument = $argv[1] ?? 'ready';

/**
 * Connection details from DB_URL where present, individual variables
 * otherwise. Supabase and Railway both hand you a URL; a hand-rolled host
 * gets the separate variables. Laravel resolves these the same way, so the
 * entrypoint and the application always agree on where they are pointed.
 */
function connectionDetails(): array
{
    $url = getenv('DB_URL') ?: null;

    if ($url) {
        $parts  = parse_url($url);
        $scheme = $parts['scheme'] ?? 'mysql';

        // postgres:// and postgresql:// are both current in the wild.
        $driver = str_starts_with($scheme, 'postgres') ? 'pgsql' : 'mysql';

        return [
            'driver'   => $driver,
            'host'     => $parts['host'] ?? '127.0.0.1',
            'port'     => $parts['port'] ?? ($driver === 'pgsql' ? 5432 : 3306),
            'database' => ltrim($parts['path'] ?? '', '/'),
            'username' => isset($parts['user']) ? urldecode($parts['user']) : '',
            'password' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
        ];
    }

    $driver = getenv('DB_CONNECTION') ?: 'mysql';

    return [
        'driver'   => $driver,
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: ($driver === 'pgsql' ? 5432 : 3306),
        'database' => getenv('DB_DATABASE') ?: 'postgres',
        'username' => getenv('DB_USERNAME') ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: '',
    ];
}

$c = connectionDetails();

// SQLite would be a misconfiguration on a container whose filesystem resets,
// so say so rather than appearing to succeed against a file that vanishes.
if ($c['driver'] === 'sqlite') {
    fwrite(STDERR, "DB_CONNECTION=sqlite on a container: data will not survive a redeploy.\n");
    exit(1);
}

$dsn = $c['driver'] === 'pgsql'
    ? sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $c['host'], $c['port'], $c['database'], getenv('DB_SSLMODE') ?: 'require')
    : sprintf('mysql:host=%s;port=%s;dbname=%s', $c['host'], $c['port'], $c['database']);

try {
    $pdo = new PDO($dsn, $c['username'], $c['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}

if ($argument === 'users') {
    try {
        echo (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (Throwable) {
        // No users table yet — an empty database, which is a valid answer.
        echo '0';
    }
}

exit(0);
