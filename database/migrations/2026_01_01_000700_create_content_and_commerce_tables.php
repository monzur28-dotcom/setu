<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Free public matrimonial ads — the newspaper পাত্র-পাত্রী column.
        Schema::create('classified_ads', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->foreignId('poster_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('looking_for', ['BRIDE', 'GROOM']);
            $table->string('headline', 120);
            $table->text('body');
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('education', 40)->nullable();
            $table->string('profession', 60)->nullable();
            $table->string('religion', 20)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->foreignId('district_id')->nullable()->constrained('geo_districts');

            // Public by design — this is a newspaper ad. OTP-verified first.
            $table->string('contact_phone', 20);

            // "নো-মিডিয়া" — no intermediaries. Honoured by excluding the ad
            // from EVERY operator-facing query at the data layer, not by
            // policy. It is an explicit request from someone who trusted a
            // free service. Spec 5.6.
            $table->boolean('no_media_flag')->default(false);

            $table->enum('status', ['PENDING', 'LIVE', 'EXPIRED', 'REMOVED'])->default('PENDING');
            $table->foreignId('moderated_by')->nullable()->constrained('users');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'looking_for', 'district_id']);
            $table->index('no_media_flag');
        });

        // The free biodata maker's output. No account attached — that is the
        // point. `converted_user_id` is how the funnel is measured.
        Schema::create('biodata_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->json('payload');
            $table->string('locale', 5)->default('bn');
            $table->string('template', 30)->default('traditional');
            $table->foreignId('converted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Landing pages are DATA, not code. Managed from the admin SEO console.
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120);
            $table->string('locale', 5)->default('bn');
            $table->json('filter_json');
            $table->string('h1', 160);
            $table->text('intro_bn')->nullable();
            $table->text('intro_en')->nullable();
            $table->json('faq_json')->nullable();
            $table->unsignedInteger('match_count')->default(0);
            $table->timestamp('count_updated_at')->nullable();
            // AUTO applies the inventory threshold nightly: fewer than 8
            // matching profiles and the page noindexes itself, which is what
            // keeps a page network from becoming a doorway-page penalty.
            $table->enum('index_status', ['INDEX', 'NOINDEX', 'AUTO'])->default('AUTO');
            $table->json('internal_links')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'locale']);
        });

        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title_en', 160);
            $table->string('title_bn', 200);
            $table->longText('body_en')->nullable();
            $table->longText('body_bn')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        Schema::create('success_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_a_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->foreignId('profile_b_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->text('body_bn')->nullable();
            $table->text('body_en')->nullable();
            $table->string('photo_path')->nullable();
            // NOT NULL: a story without a recorded consent cannot exist.
            $table->foreignId('consent_id')->constrained('consents');
            $table->string('city', 60)->nullable();
            $table->unsignedSmallInteger('weeks_to_connect')->nullable();
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'WITHDRAWN'])->default('DRAFT');
            $table->timestamps();
        });

        // Pricing is data, never code.
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->enum('product', ['MATRIMONIAL', 'CONNECT'])->default('MATRIMONIAL');
            $table->string('name_en', 60);
            $table->string('name_bn', 80);
            $table->string('market', 10)->default('BD');
            $table->string('currency', 3)->default('BDT');
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->enum('product', ['MATRIMONIAL', 'CONNECT'])->default('MATRIMONIAL');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->enum('status', ['ACTIVE', 'EXPIRED', 'CANCELLED', 'REFUNDED'])->default('ACTIVE');
            $table->foreignId('transaction_id')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'product', 'status', 'ends_at']);
        });

        // Quota counters. Decremented inside a transaction with a row lock —
        // checking then writing without a lock is how members get free
        // interests. Spec 22.6.
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);              // interests_per_day, likes_per_day, …
            $table->unsignedInteger('allowance')->default(0);
            $table->unsignedInteger('used')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->unique(['user_id', 'key', 'period_start']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('plan_id')->nullable()->constrained();
            $table->string('provider', 30);                       // sslcommerz | bkash | nagad | stripe
            $table->string('provider_txn_id', 120)->unique();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('vat_amount')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->enum('status', ['INITIATED', 'SUCCESS', 'FAILED', 'CANCELLED', 'REFUNDED'])
                  ->default('INITIATED');
            // Entitlements are granted only after a SERVER-SIDE verification.
            // Redirect parameters are advisory, never authoritative.
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->json('raw_payload')->nullable();
            // Neutral descriptor: a line item naming a dating product on a
            // shared household statement is a real exposure risk. Spec 18.6.
            $table->string('statement_descriptor', 40)->default('SETU MEMBERSHIP');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no', 30)->unique();     // sequential, VAT-compliant
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->enum('type', ['PERCENT', 'FIXED']);
            $table->unsignedInteger('value');
            $table->unsignedInteger('max_uses')->default(0);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'coupons', 'invoices', 'transactions', 'entitlements', 'subscriptions', 'plans',
            'success_stories', 'guides', 'landing_pages', 'biodata_drafts', 'classified_ads',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
