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
        Schema::table('profiles', function (Blueprint $table) {
            $table->enum('religion', self::WIDENED)->change();
        });
    }

    public function down(): void
    {
        // Anyone on one of the new options becomes OTHER. Without this the
        // narrowed constraint cannot be applied and the rollback fails.
        DB::table('profiles')
            ->whereNotIn('religion', self::ORIGINAL)
            ->update(['religion' => 'OTHER']);

        Schema::table('profiles', function (Blueprint $table) {
            $table->enum('religion', self::ORIGINAL)->change();
        });
    }
};
