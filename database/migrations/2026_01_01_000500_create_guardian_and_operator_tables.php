<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A guardian is a real account with its own login and its own
        // dashboard — but the CANDIDATE always holds the control.
        Schema::create('guardian_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('relationship', [
                'FATHER', 'MOTHER', 'BROTHER', 'SISTER', 'AUNT', 'UNCLE', 'COUSIN', 'LEGAL_GUARDIAN',
            ]);

            // L1 is the default in EVERY case, including for a guardian who
            // created the profile. Consent is granted, not assumed. Spec 12.2 G3.
            $table->enum('visibility_level', ['L1_PROGRESS', 'L2_INTRODUCTIONS', 'L3_FULL'])
                  ->default('L1_PROGRESS');
            $table->enum('link_status', ['INVITED', 'ACTIVE', 'REVOKED', 'DECLINED'])->default('INVITED');
            $table->string('invite_token', 64)->nullable()->unique();
            $table->boolean('created_profile')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['guardian_user_id', 'candidate_user_id']);
        });

        // Every guardian read, surfaced back to the candidate.
        // Transparency runs both ways, deliberately. Spec 12.2 G10.
        Schema::create('guardian_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_link_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('subject_ref', 20)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('guardian_link_id');
        });

        // Private to the guardian. The candidate cannot read them, and
        // neither can staff.
        Schema::create('guardian_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_link_id')->constrained()->cascadeOnDelete();
            $table->string('subject_ref', 20)->nullable();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('matchmaker_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('users');   // named to the client
            $table->enum('office', ['DHAKA', 'NORTH_AMERICA', 'UK'])->default('DHAKA');
            $table->enum('tier', ['GHOTOK_BD', 'GHOTOK_NA'])->default('GHOTOK_BD');
            $table->text('brief')->nullable();       // the intake call — worth more than the profile
            $table->enum('stage', [
                'INTAKE', 'SOURCING', 'APPROACHING', 'MEETING', 'TALKING', 'OUTCOME',
            ])->default('INTAKE');
            $table->enum('outcome', [
                'OPEN', 'NOT_PROCEEDING', 'TALKING', 'ENGAGED', 'MARRIED',
            ])->default('OPEN');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'outcome']);
        });

        // No row, no access. This table is what makes the service tier
        // honest — and it is the whole difference from the reference model,
        // whose privacy page permits disclosure "at will". Spec 16.5.
        Schema::create('case_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('matchmaker_cases')->cascadeOnDelete();
            $table->foreignId('subject_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('scope', ['VIEW_PRIVATE', 'SHARE_BLANKET', 'SHARE_PER_CASE']);
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'subject_user_id']);
        });

        // Every operator profile view. Shown to the member at /matchmaker/consent.
        // Retained 24 months — it is the evidence base for the promise.
        Schema::create('operator_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('users');
            $table->foreignId('case_id')->nullable()->constrained('matchmaker_cases');
            $table->foreignId('subject_profile_id')->constrained('profiles');
            $table->string('fields_returned', 20)->default('PUBLIC');   // PUBLIC | PRIVATE
            $table->string('reason', 80)->nullable();
            $table->boolean('consent_present')->default(false);   // must be true for PRIVATE
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_profile_id', 'created_at']);
            $table->index('consent_present');
        });

        // An unlogged contact is treated as not having happened,
        // including for commission. Spec 17.5 rule 4.
        Schema::create('case_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('matchmaker_cases')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('users');
            $table->string('party', 40);
            $table->enum('channel', ['CALL', 'SMS', 'WHATSAPP', 'MEETING', 'EMAIL']);
            $table->text('summary');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('case_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('matchmaker_cases')->cascadeOnDelete();
            $table->foreignId('candidate_profile_id')->constrained('profiles');
            $table->string('note', 300)->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'candidate_profile_id']);
        });

        // Recommended structure: a REFUNDABLE DEPOSIT collected upfront.
        // "If we don't find you a match in six months you get all of it back"
        // converts better than "pay us later if it works" — and it collects.
        Schema::create('success_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('matchmaker_cases')->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('users');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('BDT');
            $table->enum('structure', ['CONTRACTUAL', 'DEPOSIT', 'MILESTONE'])->default('DEPOSIT');
            $table->string('trigger_event', 40)->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            // Must differ from recorded_by. An operator whose income depends
            // on success fees must not be the sole recorder of success.
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->enum('status', ['DUE', 'INVOICED', 'PAID', 'REFUNDED', 'WAIVED', 'WRITTEN_OFF'])
                  ->default('DUE');
            $table->string('waiver_reason', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'success_fees', 'case_shortlists', 'case_contacts', 'operator_access_logs',
            'case_consents', 'matchmaker_cases', 'guardian_notes', 'guardian_access_logs',
            'guardian_links',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
