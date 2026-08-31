<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 *  THE WALL
 * ============================================================================
 *  Connect is a SEPARATE PRODUCT, not a flag on the matrimonial profile.
 *
 *  A `mode` column on one profiles table would mean every query in the system
 *  has to remember to filter on it — and the one query that forgets is a
 *  cross-mode leak, which is the worst failure this product can have.
 *  Separate tables make that leak impossible by construction: there is no
 *  join path from `profiles` to `connect_profiles` except through `users`,
 *  and only the PRIVACY role may use it.
 *
 *  Wall rules W1–W10, master specification chapter 4.3.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // W7: a DIFFERENT opaque identifier, not derivable from profile_id.
            $table->string('connect_id', 12)->unique();

            $table->string('display_name', 30);          // first name only, ever
            $table->unsignedTinyInteger('age');
            $table->string('city', 60);                  // city only — never a distance,
                                                          // never coordinates. Spec 4.5.
            $table->text('bio')->nullable();
            $table->enum('intentions', [
                'MARRIAGE_WITHIN_YEAR', 'SERIOUS_RELATIONSHIP', 'GETTING_TO_KNOW',
            ]);
            $table->string('faith_practice', 40)->nullable();
            $table->string('education_coarse', 40)->nullable();
            $table->string('profession_coarse', 40)->nullable();
            $table->json('interests')->nullable();

            // Default protects the people who never open settings.
            $table->enum('photo_visibility', ['BLURRED_UNTIL_MATCH', 'VISIBLE_TO_SUGGESTIONS'])
                  ->default('BLURRED_UNTIL_MATCH');

            $table->enum('status', ['ACTIVE', 'PAUSED', 'DELETED'])->default('ACTIVE');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'city']);

            // NOTE: deliberately absent — no family fields, no income, no home
            // district, no height preference, no guardian link. The absence
            // is the specification.
        });

        Schema::create('connect_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connect_profile_id')->constrained()->cascadeOnDelete();
            // W2: a SEPARATE storage disk (config/filesystems.php: connect_photos).
            $table->string('path');
            $table->string('blur_path')->nullable();
            // Compared against `photos.phash` ONLY to warn the member that
            // reusing an image lets someone link the two identities.
            // The comparison result is never stored and never exposed.
            $table->string('phash', 64)->nullable();
            $table->unsignedTinyInteger('order')->default(0);
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamps();
        });

        Schema::create('connect_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connect_profile_id')->constrained()->cascadeOnDelete();
            $table->string('question_key', 60);
            $table->string('answer', 200);
            $table->timestamps();
        });

        Schema::create('connect_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connect_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('age_min')->default(21);
            $table->unsignedTinyInteger('age_max')->default(38);
            $table->json('cities')->nullable();
            $table->json('intentions')->nullable();
            $table->json('faith_practice')->nullable();
            $table->timestamps();
        });

        // A like is NEVER exposed to the recipient unless it is mutual.
        // Enforced in the serializer, not the client. Spec 27.3 S2.
        Schema::create('connect_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_connect_id')->constrained('connect_profiles')->cascadeOnDelete();
            $table->foreignId('to_connect_id')->constrained('connect_profiles')->cascadeOnDelete();
            $table->enum('action', ['LIKE', 'PASS']);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['from_connect_id', 'to_connect_id']);
            $table->index(['to_connect_id', 'action']);
        });

        // Created ONLY on a mutual like. There is no other path to contact,
        // at any price. Spec 4.5.
        Schema::create('connect_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('a_connect_id')->constrained('connect_profiles')->cascadeOnDelete();
            $table->foreignId('b_connect_id')->constrained('connect_profiles')->cascadeOnDelete();
            $table->timestamp('matched_at')->useCurrent();
            $table->enum('status', ['ACTIVE', 'UNMATCHED'])->default('ACTIVE');
            $table->foreignId('closed_by')->nullable()->constrained('connect_profiles');
            $table->timestamps();

            $table->unique(['a_connect_id', 'b_connect_id']);
        });

        Schema::create('connect_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('connect_matches')->cascadeOnDelete();
            $table->foreignId('sender_connect_id')->constrained('connect_profiles');
            $table->text('body');
            $table->boolean('is_filtered')->default(false);
            $table->string('filter_reason', 40)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'created_at']);
        });

        // Absolute and silent. Removes both from each other's existence,
        // and the blocked person is never told. Spec 4.5.
        Schema::create('connect_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_connect_id')->constrained('connect_profiles')->cascadeOnDelete();
            $table->foreignId('blocked_connect_id')->constrained('connect_profiles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['blocker_connect_id', 'blocked_connect_id']);
        });

        // Daily deck. There is no search endpoint and no directory.
        Schema::create('connect_decks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connect_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_connect_id')->constrained('connect_profiles');
            $table->unsignedTinyInteger('score')->default(0);
            $table->date('for_date');
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->unique(['connect_profile_id', 'candidate_connect_id', 'for_date'], 'deck_unique');
            $table->index(['connect_profile_id', 'for_date']);
        });

        Schema::create('connect_hidden_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connect_profile_id')->constrained()->cascadeOnDelete();
            $table->string('mobile_hash', 64);
            $table->timestamps();
            $table->unique(['connect_profile_id', 'mobile_hash']);
        });
    }

    public function down(): void
    {
        foreach ([
            'connect_hidden_contacts', 'connect_decks', 'connect_blocks', 'connect_messages',
            'connect_matches', 'connect_likes', 'connect_preferences', 'connect_prompts',
            'connect_photos', 'connect_profiles',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
