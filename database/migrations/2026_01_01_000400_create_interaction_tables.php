<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sending is metered and paid. Receiving and replying are free on
        // every plan, forever. Spec 18.2 — do not meter replies.
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('to_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->enum('status', ['PENDING', 'ACCEPTED', 'DECLINED', 'EXPIRED', 'CANCELLED'])
                  ->default('PENDING');
            $table->string('message', 300)->nullable();
            $table->string('decline_reason', 60)->nullable();   // never shown to the sender
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['from_profile_id', 'to_profile_id']);
            $table->index(['to_profile_id', 'status']);
        });

        // Granting private access grants it in return. One action, both
        // directions — neither party is more exposed. Spec 16.1.
        Schema::create('private_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grantor_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('grantee_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['grantor_profile_id', 'grantee_profile_id']);
        });

        // Photo requests are separate from private-profile requests: many
        // members share details before pictures, and forcing one decision
        // loses both. Spec 7.3.
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('to_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->enum('type', ['PRIVATE_PROFILE', 'PHOTOS']);
            $table->enum('status', ['PENDING', 'GRANTED', 'DECLINED'])->default('PENDING');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['to_profile_id', 'status']);
        });

        Schema::create('mailbox_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_a_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('profile_b_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('interest_id')->nullable()->constrained('interests');
            $table->timestamp('last_message_at')->nullable();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->foreignId('closed_by')->nullable()->constrained('profiles');
            $table->timestamps();

            $table->unique(['profile_a_id', 'profile_b_id']);
        });

        Schema::create('mailbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('mailbox_threads')->cascadeOnDelete();
            $table->foreignId('sender_profile_id')->constrained('profiles');
            $table->text('body');
            $table->boolean('is_filtered')->default(false);   // contact pattern was masked
            $table->string('filter_reason', 40)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });

        // Contact is exchanged by a two-sided action, never sold.
        // No plan, coupon, admin action or support path substitutes for this.
        Schema::create('contact_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->unique()->constrained('mailbox_threads')->cascadeOnDelete();
            $table->foreignId('offered_by')->constrained('profiles');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('note', 200)->nullable();
            $table->timestamps();
            $table->unique(['profile_id', 'target_profile_id']);
        });

        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->foreignId('viewed_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('source', 24)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['viewed_profile_id', 'created_at']);
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('blocked_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('reason', 60)->nullable();
            $table->timestamps();
            $table->unique(['blocker_profile_id', 'blocked_profile_id']);
        });

        // "Hide me from these numbers" — paste in relatives' and colleagues'
        // numbers and become invisible to them. No competitor does this well.
        Schema::create('hidden_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mobile_hash', 64);
            $table->timestamps();
            $table->unique(['user_id', 'mobile_hash']);
            $table->index('mobile_hash');
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->json('filters');
            // Alerts are FREE on every plan. For a free-browse product this
            // is the cheapest retention mechanism that exists. Spec 8.4.
            $table->enum('alert_frequency', ['NONE', 'DAILY', 'WEEKLY'])->default('WEEKLY');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        // Curated introductions — the paid discovery layer.
        // Two INDEPENDENT status columns: this is where the no-rejection-signal
        // rule physically lives. Spec 15.4.
        Schema::create('introductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_a_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('profile_b_id')->constrained('profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->text('rationale')->nullable();
            $table->enum('rationale_author', ['SYSTEM', 'HUMAN'])->default('SYSTEM');
            $table->enum('status_a', ['PENDING', 'INTERESTED', 'PASSED', 'EXPIRED'])->default('PENDING');
            $table->enum('status_b', ['PENDING', 'INTERESTED', 'PASSED', 'EXPIRED'])->default('PENDING');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['profile_a_id', 'profile_b_id']);
        });

        // Rejected pairs from the curation console. Within six months this
        // is the most valuable dataset the company owns. Never purge.
        Schema::create('curation_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_a_id')->constrained('profiles');
            $table->foreignId('profile_b_id')->constrained('profiles');
            $table->string('reason_code', 40);
            $table->foreignId('rejected_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'curation_rejections', 'introductions', 'saved_searches', 'hidden_contacts',
            'blocks', 'profile_views', 'shortlists', 'contact_exchanges', 'mailbox_messages',
            'mailbox_threads', 'access_requests', 'private_accesses', 'interests',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
