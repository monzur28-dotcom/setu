<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every consent in this system is a ROW WITH A TIMESTAMP AND EVIDENCE,
        // never a boolean column. You will need to prove when and how
        // something was agreed. Spec 16.4.
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('consent_type', 60);
            // TERMS, PRIVACY_POLICY, CANDIDATE_CONFIRMATION, PUBLIC_INDEXING,
            // GUARDIAN_VISIBILITY, OPERATOR_ACCESS, OPERATOR_SHARING,
            // SUCCESS_STORY_TEXT, SUCCESS_STORY_PHOTO, CONNECT_PARTICIPATION,
            // MARKETING, SPECIAL_CATEGORY_RELIGION
            $table->boolean('granted')->default(true);
            $table->string('version', 20)->nullable();
            $table->json('evidence')->nullable();      // ip, user agent, document hash
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
