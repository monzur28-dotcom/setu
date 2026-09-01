<?php

return [
    /*
    |---------------------------------------------------------------------
    | Who publishes this
    |---------------------------------------------------------------------
    | Shown in the footer, the about page and the legal notices. Kept here
    | rather than written into templates so a rebrand is one edit.
    */
    'company' => [
        'name'          => env('SETU_COMPANY_NAME', 'Royal Bengal AI'),
        'incorporated'  => env('SETU_COMPANY_COUNTRY', 'US'),
        'support_email' => env('SETU_SUPPORT_EMAIL', 'hello@setu.example'),
    ],

    /*
    |---------------------------------------------------------------------
    | Platform settings
    |---------------------------------------------------------------------
    | Every business rule that a non-developer might reasonably want to
    | change lives here rather than in code. See the master build
    | specification, chapters 15–18.
    */

    /*
    | Where the product is being run from. Sets the default dial code, the
    | pricing market and the currency a visitor sees before we know anything
    | about them — not a restriction on who may join.
    */
    'home_market' => env('SETU_HOME_MARKET', 'US'),

    'default_country_code' => env('SETU_DEFAULT_COUNTRY_CODE', '+1'),

    /*
    | The prefix on every public profile id. Deliberately not a country code:
    | a member in Toronto with an id beginning BD is being told, every time
    | they see it, that this product is not really for them.
    */
    'profile_id_prefix' => env('SETU_PROFILE_ID_PREFIX', 'ST'),

    'otp' => [
        'ttl'          => (int) env('SETU_OTP_TTL_SECONDS', 300),
        'length'       => 6,
        'max_attempts' => (int) env('SETU_OTP_MAX_ATTEMPTS', 3),
        'resend_after' => 60,
        // Local only. RegisterController requires environment('local') as
        // well, so this flag alone can never switch off phone verification.
        'bypass'       => (bool) env('SETU_OTP_BYPASS', false),
        'max_per_hour' => 3,
    ],

    /*
    | Plan entitlements. The paywall sits at INITIATING contact and nowhere
    | else: receiving and replying to an interest is free on every plan,
    | forever. Do not meter replies. (Spec 18.2)
    */
    'plans' => [
        'free' => [
            'label_en' => 'Free', 'label_bn' => 'ফ্রি',
            'price_bdt' => 0, 'price_usd' => 0, 'days' => null,
            'interests_per_day' => 0,
            // A free account browses; it does not learn who anyone is. The
            // name and every contact field stay hidden, and the profile
            // reads by its opaque id. (Spec 18.2 — the paywall sits at
            // IDENTIFYING and INITIATING, never at looking.)
            'can_see_full_name' => false,
            'can_request_private' => false,
            'can_initiate_mailbox' => false,
            'introductions_per_month' => 0,
            'see_viewers' => false,
        ],
        'standard' => [
            'label_en' => 'Standard', 'label_bn' => 'স্ট্যান্ডার্ড',
            'price_bdt' => 2900, 'price_usd' => 39, 'days' => 150,
            'interests_per_day' => 15,
            'can_see_full_name' => true,
            'can_request_private' => true,
            'can_initiate_mailbox' => true,
            'introductions_per_month' => 4,
            'see_viewers' => true,
        ],
        'ghotok' => [
            'label_en' => 'Ghotok', 'label_bn' => 'ঘটক',
            'price_bdt' => 9900, 'price_usd' => 125, 'days' => 180,
            'interests_per_day' => 30,
            'can_see_full_name' => true,
            'can_request_private' => true,
            'can_initiate_mailbox' => true,
            'introductions_per_month' => 8,
            'see_viewers' => true,
            'matchmaker' => true,
            'success_deposit_bdt' => 20000,   // refundable in full — spec 18.5
        ],
    ],

    'connect_plans' => [
        'free'      => ['likes_per_day' => 15, 'see_likers' => false, 'rewinds' => 0,
                        'price_bdt' => 0,    'days' => null],
        'monthly'   => ['likes_per_day' => null, 'see_likers' => true, 'rewinds' => 1,
                        'price_bdt' => 490,  'days' => 30],
        'quarterly' => ['likes_per_day' => null, 'see_likers' => true, 'rewinds' => 1,
                        'price_bdt' => 1190, 'days' => 90],
    ],

    /*
    | Match scoring weights (spec 15.3). Tunable without a deploy.
    | `complexion` is deliberately absent: the field exists because users
    | expect it, but the product must not amplify the preference.
    */
    'match_weights' => [
        'age' => 18, 'religion' => 18, 'prayer_habit' => 14, 'marital_status' => 12,
        'district' => 12, 'country' => 10, 'education' => 10, 'family_involvement' => 6,
        'profession' => 5, 'marriage_timeline' => 5, 'height' => 4, 'diet' => 4,
    ],

    'landing' => [
        'min_profiles_to_index' => (int) env('SETU_LANDING_MIN_PROFILES', 8),
        'count_cache_minutes'   => 60,
    ],

    /*
    | The front page. Slide images are admin-managed rows in `hero_slides`;
    | only the timing lives here, because it is a design decision rather
    | than content.
    */
    'hero' => [
        'interval_ms' => (int) env('SETU_HERO_INTERVAL_MS', 3000),
    ],

    'moderation' => [
        'photo_sla_hours'        => (int) env('SETU_PHOTO_MODERATION_SLA_HOURS', 4),
        'profile_sla_hours'      => (int) env('SETU_PROFILE_MODERATION_SLA_HOURS', 12),
        // A word-list match never rejects on its own; it re-prioritises the
        // queue and a person decides.
        'word_match_priority'    => 1,
        'auto_hide_report_count' => 3,
        'report_window_days'     => 7,
    ],

    'retention' => [
        'kyc_document_days'   => 30,   // hard delete after the decision
        'connect_purge_days'  => 7,    // faster than the account default
        'classified_ad_days'  => 60,
        'biodata_draft_days'  => 90,
        'messages_months'     => 12,
        'operator_log_months' => 24,
    ],

    'classifieds' => [
        'live_days'      => 60,
        'reminder_day'   => 53,
        'ads_per_number' => 1,
    ],
];
