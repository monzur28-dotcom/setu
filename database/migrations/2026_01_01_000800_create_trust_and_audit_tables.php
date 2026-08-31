<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['NID', 'PASSPORT', 'SELFIE', 'EDUCATION', 'EMPLOYMENT']);
            $table->string('provider', 30)->default('manual');
            $table->string('provider_ref', 120)->nullable();
            // Encrypted under a SEPARATE key. Nulled by the purge job 30 days
            // after the decision — only the decision, last four and a hash
            // survive. Never displayed anywhere in the product. Spec 17.1.
            $table->string('document_path')->nullable();
            $table->string('document_last4', 8)->nullable();
            $table->string('document_hash', 64)->nullable();
            $table->enum('status', ['PENDING', 'NEEDS_INFO', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->string('rejection_reason', 80)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('purge_after')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')->constrained('users');
            $table->enum('subject_type', ['PROFILE', 'CONNECT_PROFILE', 'AD', 'MESSAGE', 'GUARDIAN']);
            $table->unsignedBigInteger('subject_id');
            $table->string('reason', 60);
            // ASKING_FOR_MONEY, ALREADY_MARRIED, PHOTOS_NOT_THEIRS, BROKER,
            // ABUSIVE, OFF_PLATFORM_PRESSURE, GUARDIAN_COERCION, SEXTORTION
            $table->text('details')->nullable();
            $table->json('evidence')->nullable();
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->default('MEDIUM');
            $table->enum('status', ['OPEN', 'REVIEWING', 'RESOLVED', 'DISMISSED'])->default('OPEN');
            $table->string('resolution', 200)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('moderation_items', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['PHOTO', 'CONNECT_PHOTO', 'PROFILE', 'AD', 'REPORT']);
            $table->unsignedBigInteger('entity_id');
            // Mode-scoped: a moderator reviews Connect content under Connect
            // policy and matrimonial content under matrimonial policy.
            $table->enum('mode', ['MATRIMONIAL', 'CONNECT'])->default('MATRIMONIAL');
            $table->unsignedTinyInteger('priority')->default(5);
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->enum('status', ['QUEUED', 'IN_REVIEW', 'DONE'])->default('QUEUED');
            $table->unsignedTinyInteger('ml_score')->nullable();
            $table->timestamps();

            $table->index(['status', 'mode', 'priority']);
        });

        // Append-only, retained 7 years. Every staff action, every operator
        // profile view, and every cross-mode lookup by the PRIVACY role.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('actor_role', 20)->nullable();
            $table->string('action', 60);
            $table->string('entity_type', 40)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_id', 'created_at']);
            $table->index('action');
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_hash', 64);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('purpose', 30)->default('REGISTER');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['mobile_hash', 'expires_at']);
        });

        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 60);
            $table->enum('channel', ['PUSH', 'SMS', 'EMAIL', 'IN_APP']);
            $table->enum('mode', ['MATRIMONIAL', 'CONNECT'])->default('MATRIMONIAL');
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'notifications_log', 'otp_codes', 'audit_logs', 'moderation_items',
            'reports', 'verifications',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
