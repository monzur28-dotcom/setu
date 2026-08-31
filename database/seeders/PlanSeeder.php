<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Plans, in three markets. The prices live here rather than in config
 * because finance edits them; the *rules* they buy live in config/setu.php
 * because engineering owns those.
 *
 * Read the features JSON carefully: no plan, at any price, contains a
 * key that reveals another member's contact. That is the product promise
 * and it is enforced in ContactExchange, not here. Spec 18.2.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $f = fn (array $o) => json_encode($o + [
            'sell_contact' => false,   // never true. Not a pricing lever.
        ], JSON_UNESCAPED_UNICODE);

        DB::table('plans')->insert([
            // ---- Matrimonial, Bangladesh ----
            [
                'code' => 'free', 'product' => 'MATRIMONIAL',
                'name_en' => 'Free', 'name_bn' => 'ফ্রি',
                'market' => 'BD', 'currency' => 'BDT', 'price' => 0, 'duration_days' => 3650,
                'features' => $f([
                    'interests_per_month' => 5,
                    'reply_free'          => true,
                    'search'              => true,
                    'private_requests'    => 2,
                    'mailbox'             => true,
                    'boost'               => false,
                ]),
                'is_active' => true, 'sort_order' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'standard', 'product' => 'MATRIMONIAL',
                'name_en' => 'Standard', 'name_bn' => 'স্ট্যান্ডার্ড',
                'market' => 'BD', 'currency' => 'BDT', 'price' => 1490, 'duration_days' => 90,
                'features' => $f([
                    'interests_per_month' => 60,
                    'reply_free'          => true,
                    'search'              => true,
                    'private_requests'    => 30,
                    'mailbox'             => true,
                    'boost'               => true,
                    'see_who_viewed'      => true,
                    'priority_support'    => false,
                ]),
                'is_active' => true, 'sort_order' => 2,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'standard_6m', 'product' => 'MATRIMONIAL',
                'name_en' => 'Standard, six months', 'name_bn' => 'স্ট্যান্ডার্ড, ছয় মাস',
                'market' => 'BD', 'currency' => 'BDT', 'price' => 2490, 'duration_days' => 180,
                'features' => $f([
                    'interests_per_month' => 60,
                    'reply_free'          => true,
                    'search'              => true,
                    'private_requests'    => 30,
                    'mailbox'             => true,
                    'boost'               => true,
                    'see_who_viewed'      => true,
                    'priority_support'    => true,
                ]),
                'is_active' => true, 'sort_order' => 3,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'ghotok', 'product' => 'MATRIMONIAL',
                'name_en' => 'Ghotok service', 'name_bn' => 'ঘটক সেবা',
                'market' => 'BD', 'currency' => 'BDT', 'price' => 20000, 'duration_days' => 180,
                'features' => $f([
                    'interests_per_month' => 60,
                    'reply_free'          => true,
                    'search'              => true,
                    'private_requests'    => 60,
                    'mailbox'             => true,
                    'boost'               => true,
                    'see_who_viewed'      => true,
                    'priority_support'    => true,
                    'assigned_operator'   => true,
                    'refundable_deposit'  => true,   // until first introduction
                    'success_fee'         => true,   // two-person confirmation
                ]),
                'is_active' => true, 'sort_order' => 4,
                'created_at' => now(), 'updated_at' => now(),
            ],

            // ---- Matrimonial, diaspora ----
            [
                'code' => 'standard_intl', 'product' => 'MATRIMONIAL',
                'name_en' => 'Standard (international)', 'name_bn' => 'স্ট্যান্ডার্ড (আন্তর্জাতিক)',
                'market' => 'INTL', 'currency' => 'USD', 'price' => 39, 'duration_days' => 90,
                'features' => $f([
                    'interests_per_month' => 60,
                    'reply_free'          => true,
                    'search'              => true,
                    'private_requests'    => 30,
                    'mailbox'             => true,
                    'boost'               => true,
                    'see_who_viewed'      => true,
                ]),
                'is_active' => true, 'sort_order' => 5,
                'created_at' => now(), 'updated_at' => now(),
            ],

            // ---- Connect. Separate product, separate billing line. ----
            [
                'code' => 'connect_free', 'product' => 'CONNECT',
                'name_en' => 'Connect free', 'name_bn' => 'Connect ফ্রি',
                'market' => 'BD', 'currency' => 'BDT', 'price' => 0, 'duration_days' => 3650,
                'features' => $f(['likes_per_day' => 15, 'see_likers' => false, 'rewinds' => 0]),
                'is_active' => true, 'sort_order' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'connect_monthly', 'product' => 'CONNECT',
                'name_en' => 'Connect monthly', 'name_bn' => 'Connect মাসিক',
                'market' => 'BD', 'currency' => 'BDT', 'price' => 490, 'duration_days' => 30,
                'features' => $f(['likes_per_day' => null, 'see_likers' => true, 'rewinds' => 1]),
                'is_active' => true, 'sort_order' => 2,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'connect_quarterly', 'product' => 'CONNECT',
                'name_en' => 'Connect, three months', 'name_bn' => 'Connect, তিন মাস',
                'market' => 'BD', 'currency' => 'BDT', 'price' => 1190, 'duration_days' => 90,
                'features' => $f(['likes_per_day' => null, 'see_likers' => true, 'rewinds' => 1]),
                'is_active' => true, 'sort_order' => 3,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        DB::table('coupons')->insert([
            [
                'code' => 'EARLYBIRD', 'type' => 'PERCENT', 'value' => 25,
                'max_uses' => 500, 'used_count' => 0,
                'valid_from' => now(), 'valid_to' => now()->addMonths(3),
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $this->command?->info('  Plans: '.DB::table('plans')->count().' rows, 1 coupon.');
    }
}
