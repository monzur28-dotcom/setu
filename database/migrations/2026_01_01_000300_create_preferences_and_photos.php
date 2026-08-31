<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every criterion carries a POSTURE: must / prefer / open.
        // Only "must" is a hard constraint. This distinction is the
        // difference between a member getting matches and getting none.
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->unsignedSmallInteger('height_min_cm')->nullable();
            $table->unsignedSmallInteger('height_max_cm')->nullable();

            $table->json('marital_status')->nullable();
            $table->json('religion')->nullable();
            $table->json('sect')->nullable();
            $table->json('prayer_habit')->nullable();
            $table->json('districts')->nullable();
            $table->json('exclude_districts')->nullable();   // a genuinely useful local feature
            $table->json('countries')->nullable();
            $table->json('education_level')->nullable();
            $table->json('profession')->nullable();
            $table->json('family_involvement')->nullable();
            $table->json('marriage_timeline')->nullable();
            $table->string('income_band_min', 30)->nullable();
            $table->enum('diet', ['ANY', 'HALAL_ONLY', 'VEGETARIAN', 'NON_VEGETARIAN'])->default('ANY');
            $table->enum('smoking', ['ANY', 'NO', 'OCCASIONALLY'])->default('ANY');
            $table->enum('drinking', ['ANY', 'NO', 'OCCASIONALLY'])->default('ANY');
            $table->enum('relocation', ['ANY', 'WILL_RELOCATE', 'WILL_NOT', 'PARTNER_RELOCATES'])
                  ->default('ANY');

            // {"age":"MUST","religion":"MUST","education":"PREFER", ...}
            $table->json('postures')->nullable();
            // Permits the engine to relax exactly one non-hard criterion.
            $table->string('surprise_me_on', 40)->nullable();

            $table->text('about_partner')->nullable();
            $table->timestamps();
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();

            // Only the account holder may upload their own photographs.
            // Enforced by a check constraint below, not by policy text.
            $table->foreignId('uploaded_by_user_id')->constrained('users');

            $table->string('path');           // private disk; signed URLs only
            $table->string('blur_path')->nullable();
            $table->string('phash', 64)->nullable();   // duplicate + cross-mode detection
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->string('rejection_reason', 80)->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users');
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
        Schema::dropIfExists('preferences');
    }
};
