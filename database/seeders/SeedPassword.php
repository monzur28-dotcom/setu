<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

/**
 * The password every seeded account gets.
 *
 * On a laptop this is "password", because a demo you cannot log into is
 * useless. On a public host it is a random string generated once per seed
 * run and printed to the deploy log — because "password" on an account
 * called admin@setu.test, reachable from the internet, is not a demo
 * account, it is an open door.
 *
 * Set SETU_SEED_PASSWORD to choose your own. Resolved once so that every
 * seeder in a run agrees on the answer.
 */
class SeedPassword
{
    private static ?string $resolved = null;

    public static function get(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $given = env('SETU_SEED_PASSWORD');

        if (is_string($given) && $given !== '') {
            return self::$resolved = $given;
        }

        return self::$resolved = app()->environment('production')
            ? Str::password(20, symbols: false)
            : 'password';
    }

    /** True when the password was generated and the operator must read it. */
    public static function isGenerated(): bool
    {
        return self::get() !== 'password' && ! env('SETU_SEED_PASSWORD');
    }

    /** Only for tests, which need a clean slate between runs. */
    public static function forget(): void
    {
        self::$resolved = null;
    }
}
