<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->enum('gender', ['MALE', 'FEMALE']);
            $table->date('date_of_birth');
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->enum('marital_status', [
                'NEVER_MARRIED', 'DIVORCED', 'LEGALLY_SEPARATED', 'WIDOWED', 'OTHER',
            ])->default('NEVER_MARRIED');
            $table->enum('has_children', ['NONE', 'YES_WITH_ME', 'YES_NOT_WITH_ME'])->default('NONE');
            $table->unsignedTinyInteger('children_count')->default(0);

            // Kept because users expect it, defaulted to "prefer not to say",
            // and deliberately excluded from the match score. Spec 14.2.
            $table->enum('complexion', ['FAIR', 'WHEATISH', 'BROWN', 'DARK', 'PREFER_NOT_TO_SAY'])
                  ->default('PREFER_NOT_TO_SAY');
            $table->enum('body_type', ['SLIM', 'AVERAGE', 'ATHLETIC', 'HEAVY'])->nullable();
            $table->enum('physical_status', ['NORMAL', 'PHYSICALLY_CHALLENGED'])->default('NORMAL');
            $table->string('blood_group', 4)->nullable();

            $table->enum('religion', ['ISLAM', 'HINDUISM', 'CHRISTIANITY', 'BUDDHISM', 'OTHER']);
            $table->string('sect', 40)->nullable();
            $table->enum('prayer_habit', [
                'FIVE_TIMES', 'REGULARLY', 'OCCASIONALLY', 'NOT_PRACTISING', 'PREFER_NOT_TO_SAY',
            ])->default('PREFER_NOT_TO_SAY');
            $table->string('hijab_beard', 30)->nullable();
            $table->enum('mother_tongue', [
                'BANGLA', 'SYLHETI', 'CHITTAGONIAN', 'CHAKMA', 'URDU', 'ENGLISH', 'OTHER',
            ])->default('BANGLA');
            $table->json('languages_known')->nullable();

            $table->string('headline', 100)->nullable();
            $table->text('about_me')->nullable();
            $table->enum('marriage_timeline', [
                'WITHIN_6_MONTHS', 'WITHIN_A_YEAR', 'WITHIN_2_YEARS', 'NO_FIXED_TIMELINE',
            ])->default('NO_FIXED_TIMELINE');

            $table->foreignId('primary_photo_id')->nullable();
            $table->unsignedTinyInteger('completeness')->default(0);
            $table->unsignedInteger('report_count')->default(0);
            $table->unsignedTinyInteger('response_rate')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // The composite index every search leans on. Spec 15.1.
            $table->index(['gender', 'religion', 'date_of_birth']);
            $table->index('marital_status');
        });

        // The public/private split lives here. Read by the visibility
        // serializer on every render. Spec 16.1.
        Schema::create('profile_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();

            // Defaults are the privacy-protective ones: a member who never
            // opens this screen is still covered.
            $table->boolean('show_photos')->default(false);
            $table->boolean('show_name')->default(false);
            $table->boolean('show_gender')->default(true);
            $table->boolean('show_height')->default(true);
            $table->boolean('show_city')->default(true);
            $table->boolean('show_profession')->default(true);
            $table->boolean('show_hobbies')->default(true);

            $table->boolean('allow_operator_access')->default(false);
            $table->timestamps();
        });

        Schema::create('profile_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('country', 2)->default('BD');
            $table->foreignId('division_id')->nullable()->constrained('geo_divisions');
            $table->foreignId('district_id')->nullable()->constrained('geo_districts');
            $table->foreignId('upazila_id')->nullable()->constrained('geo_upazilas');
            $table->string('city', 80)->nullable();
            $table->string('area', 80)->nullable();          // never public

            // The "desher bari" question, asked in every match conversation.
            $table->foreignId('home_district_id')->nullable()->constrained('geo_districts');

            $table->enum('residency_status', [
                'CITIZEN', 'PERMANENT_RESIDENT', 'WORK_VISA', 'STUDENT_VISA', 'DEPENDENT', 'OTHER',
            ])->nullable();

            // A hard filter, not a weight: introducing someone who will not
            // leave London to someone who will not leave Dhaka is a wasted
            // introduction, not a near miss. Spec 14.4.
            $table->enum('relocation_intent', [
                'WILL_RELOCATE', 'WILL_NOT', 'PARTNER_RELOCATES', 'OPEN', 'UNDECIDED',
            ])->default('UNDECIDED');
            $table->enum('sponsorship_willing', ['YES', 'NO', 'DISCUSS'])->nullable();
            $table->enum('wedding_location_pref', [
                'BANGLADESH', 'CURRENT_COUNTRY', 'EITHER', 'UNDECIDED',
            ])->default('UNDECIDED');
            $table->date('visiting_bd_from')->nullable();
            $table->date('visiting_bd_to')->nullable();
            $table->timestamps();

            $table->index(['country', 'district_id']);
            $table->index('home_district_id');
            $table->index('relocation_intent');
        });

        Schema::create('profile_careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('education_level', 40)->nullable();   // incl. DAKHIL/ALIM/FAZIL/KAMIL
            $table->string('education_detail', 120)->nullable();
            $table->string('institution', 120)->nullable();      // never public
            $table->string('profession', 60)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('employer', 120)->nullable();         // never public
            $table->enum('employed_in', [
                'GOVERNMENT', 'PRIVATE', 'BUSINESS', 'DEFENCE', 'SELF_EMPLOYED',
                'NGO', 'STUDENT', 'NOT_WORKING', 'ABROAD',
            ])->nullable();
            $table->string('income_band', 30)->nullable();       // never public
            $table->timestamps();

            $table->index('profession');
            $table->index('education_level');
        });

        Schema::create('profile_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('father_occupation', 80)->nullable();
            $table->string('mother_occupation', 80)->nullable();
            $table->json('siblings')->nullable();
            $table->enum('family_type', ['JOINT', 'NUCLEAR'])->nullable();
            $table->enum('family_status', ['MIDDLE_CLASS', 'UPPER_MIDDLE', 'AFFLUENT'])->nullable();
            $table->enum('family_values', ['TRADITIONAL', 'MODERATE', 'LIBERAL'])->nullable();
            $table->foreignId('family_origin_district_id')->nullable()->constrained('geo_districts');

            // Two people can match on every demographic field and still be
            // incompatible because one expects their parents to decide and
            // the other expects to decide themselves. Spec 14.6.
            $table->enum('family_involvement', [
                'FAMILY_LED', 'FAMILY_INVOLVED', 'MY_DECISION_FAMILY_INFORMED', 'MY_DECISION',
            ])->nullable();
            $table->text('about_family')->nullable();
            $table->timestamps();
        });

        Schema::create('profile_lifestyles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('diet', ['HALAL_ONLY', 'VEGETARIAN', 'NON_VEGETARIAN', 'OTHER'])->nullable();
            $table->enum('smoking', ['NO', 'OCCASIONALLY', 'YES'])->default('NO');
            $table->enum('drinking', ['NO', 'OCCASIONALLY', 'YES'])->default('NO');
            $table->json('hobbies')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_lifestyles');
        Schema::dropIfExists('profile_families');
        Schema::dropIfExists('profile_careers');
        Schema::dropIfExists('profile_locations');
        Schema::dropIfExists('profile_visibility');
        Schema::dropIfExists('profiles');
    }
};
