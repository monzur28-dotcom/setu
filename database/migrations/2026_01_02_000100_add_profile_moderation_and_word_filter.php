<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-publication review.
 *
 * Nothing a member writes reaches the public until a human has read it. The
 * approval state lives on `profiles` rather than in `moderation_items`
 * because it is a property of the profile, and because `scopeDiscoverable`
 * must be able to filter on it in the same query as every other gate — a
 * check applied in PHP after the query is a check that will eventually be
 * forgotten on one code path.
 *
 * The `pending_*` columns are what let an approved profile stay live while an
 * edit waits: the approved text keeps serving, the edit sits beside it, and
 * approval is a copy from one column to the other. Without them the choice is
 * between publishing unreviewed text and taking a member's profile dark every
 * time they fix a typo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // DRAFT   — registration incomplete, never submitted.
            // PENDING — waiting for a moderator. Not discoverable.
            // APPROVED/REJECTED — decided, with a reason on a rejection.
            $table->enum('moderation_status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'])
                  ->default('DRAFT')->after('completeness');
            $table->string('moderation_reason', 160)->nullable()->after('moderation_status');
            $table->foreignId('moderated_by')->nullable()->after('moderation_reason')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->timestamp('submitted_at')->nullable()->after('moderated_at');

            // The unreviewed edit, held beside the approved text.
            $table->string('pending_headline', 100)->nullable()->after('submitted_at');
            $table->text('pending_about_me')->nullable()->after('pending_headline');

            $table->index('moderation_status');
        });

        Schema::table('preferences', function (Blueprint $table) {
            $table->text('pending_about_partner')->nullable()->after('about_partner');
        });

        Schema::table('profile_families', function (Blueprint $table) {
            $table->text('pending_about_family')->nullable()->after('about_family');
        });

        /*
         | The admin's word list. A match never rejects a profile on its own —
         | it raises the profile to the top of the moderation queue with the
         | matched words attached, and a person decides. An automatic reject
         | would make every false positive a silent, unappealable ban.
         */
        Schema::create('blocked_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 60);
            $table->string('locale', 5)->default('*');   // '*' = every locale
            $table->string('note', 120)->nullable();     // why it is on the list
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['word', 'locale']);
        });

        Schema::table('access_requests', function (Blueprint $table) {
            // The serializer asks "has this viewer been granted this photo?"
            // once per card on every browsing grid. The existing index is on
            // the receiving side; this is the asking side.
            $table->index(['from_profile_id', 'type', 'status']);
        });

        Schema::table('moderation_items', function (Blueprint $table) {
            // Which listed words this item matched, so the moderator sees the
            // reason without re-running the filter.
            $table->json('matched_words')->nullable()->after('ml_score');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_items', function (Blueprint $table) {
            $table->dropColumn('matched_words');
        });

        Schema::dropIfExists('blocked_words');

        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropIndex(['from_profile_id', 'type', 'status']);
        });

        Schema::table('profile_families', function (Blueprint $table) {
            $table->dropColumn('pending_about_family');
        });

        Schema::table('preferences', function (Blueprint $table) {
            $table->dropColumn('pending_about_partner');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn([
                'moderation_status', 'moderation_reason', 'moderated_at',
                'submitted_at', 'pending_headline', 'pending_about_me',
            ]);
        });
    }
};
