<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Going global, at the schema level.
 *
 * The religion enum held the five options that cover Bangladesh. That is a
 * reasonable list for one country and a poor one for a product expecting
 * members in Toronto, Birmingham and Kuala Lumpur — a Sikh or Jewish member
 * had nothing to pick but "Other", on the field this product weights most
 * heavily when it matches people.
 *
 * Written per driver rather than through $table->enum()->change(), because
 * that emits invalid SQL on PostgreSQL:
 *
 *   alter table "profiles" alter column "religion" type varchar(255)
 *       check ("religion" in (...))
 *
 * which Postgres rejects at "check" — a type change and a constraint are two
 * statements there, not one. Laravel represents an enum as varchar plus a
 * CHECK named <table>_<column>_check, so widening it means replacing that
 * constraint. MySQL has a real ENUM type and takes MODIFY. SQLite has
 * neither and rebuilds the table, which the framework does correctly.
 *
 * Locale, currency and timezone defaults are deliberately NOT touched here.
 * A column default is the wrong place to decide them: the right answer
 * depends on the country the member just chose, so RegisterController sets
 * all three explicitly at sign-up.
 */
return new class extends Migration
{
    private const WIDENED = [
        'ISLAM', 'HINDUISM', 'CHRISTIANITY', 'BUDDHISM',
        'SIKHISM', 'JUDAISM', 'JAINISM', 'ZOROASTRIANISM',
        'SPIRITUAL', 'NOT_RELIGIOUS', 'OTHER',
    ];

    private const ORIGINAL = ['ISLAM', 'HINDUISM', 'CHRISTIANITY', 'BUDDHISM', 'OTHER'];

    public function up(): void
    {
        $this->setReligion(self::WIDENED);
    }

    public function down(): void
    {
        // Anyone on one of the new options becomes OTHER. Without this the
        // narrowed constraint cannot be applied and the rollback fails.
        DB::table('profiles')
            ->whereNotIn('religion', self::ORIGINAL)
            ->update(['religion' => 'OTHER']);

        $this->setReligion(self::ORIGINAL);
    }

    private function setReligion(array $values): void
    {
        $quoted = implode(', ', array_map(fn ($v) => "'".$v."'", $values));

        match (DB::getDriverName()) {
            'pgsql' => $this->postgres($quoted),
            'mysql', 'mariadb' => DB::statement(
                "ALTER TABLE profiles MODIFY religion ENUM({$quoted}) NOT NULL"
            ),
            // SQLite rebuilds the table; the framework's own path is correct
            // there and there is no constraint to name.
            default => Schema::table('profiles', function (Blueprint $table) use ($values) {
                $table->enum('religion', $values)->change();
            }),
        };
    }

    private function postgres(string $quoted): void
    {
        // IF EXISTS so this is safe on a database where the constraint was
        // never created under that name.
        DB::statement('ALTER TABLE profiles DROP CONSTRAINT IF EXISTS profiles_religion_check');
        DB::statement(
            "ALTER TABLE profiles ADD CONSTRAINT profiles_religion_check
             CHECK (religion::text IN ({$quoted}))"
        );
    }
};
