<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Opaque, non-sequential public identifier (e.g. BD4829173).
            // Used everywhere in place of a name. Spec 14.1.
            $table->string('profile_id', 12)->unique();

            // Who filled the form in. The reference model's key insight:
            // a parent or sibling is often the registrant, and the product
            // should know that from the first screen. Spec 2.4.
            $table->enum('registrant_relationship', [
                'SELF', 'FATHER', 'MOTHER', 'BROTHER', 'SISTER',
                'RELATIVE', 'FRIEND', 'GUARDIAN',
            ])->default('SELF');
            $table->string('registrant_name', 60)->nullable();
            $table->string('candidate_name', 60);

            // Encrypted at rest, with a searchable hash for uniqueness and
            // for the "hide me from these numbers" feature.
            $table->text('mobile_enc');
            $table->string('mobile_hash', 64)->unique();
            $table->string('email', 120)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('password');

            $table->enum('role', ['MEMBER', 'GUARDIAN', 'OPERATOR', 'MODERATOR', 'PRIVACY', 'ADMIN'])
                  ->default('MEMBER');
            $table->enum('status', ['UNVERIFIED', 'ACTIVE', 'PAUSED', 'SUSPENDED', 'BANNED', 'CLOSED'])
                  ->default('UNVERIFIED');
            $table->enum('verification_level', ['NONE', 'PHONE', 'PHONE_EMAIL', 'NID', 'NID_SELFIE'])
                  ->default('NONE');

            // NULL until the candidate confirms a profile someone else made.
            // While NULL the profile is not public, not indexed, and not
            // visible to any operator. Spec 9.5 — the soft consent gate.
            $table->timestamp('candidate_confirmed_at')->nullable();

            $table->enum('public_indexing', ['NOINDEX', 'INDEXED'])->default('NOINDEX');
            $table->string('locale', 5)->default('bn');
            $table->string('currency', 3)->default('BDT');
            $table->string('timezone', 64)->default('Asia/Dhaka');

            // ---- THE WALL ----
            // Whether this person also has a Connect profile. Readable only
            // by the PRIVACY role, and every read is written to audit_logs.
            // Never exposed to another member, a guardian, an operator, or
            // an ordinary staff account. Wall rule W8.
            $table->boolean('dating_enabled')->default(false);

            $table->timestamp('last_active_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'role']);
            $table->index('last_active_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
