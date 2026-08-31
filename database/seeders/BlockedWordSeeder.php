<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * A starting word list for the pre-publication filter.
 *
 * These are not profanity. They are the two things a Bangladeshi
 * matrimonial moderator actually has to catch:
 *
 *   1. Contact details smuggled into free text, which routes around the
 *      two-sided contact exchange the whole product is built on.
 *   2. Dowry. Illegal under the Dowry Prohibition Act 2018 and the single
 *      clearest reason to refuse to publish a profile.
 *
 * A match only re-orders the queue. Every one of these words has an
 * innocent use — "my brother lives in a joint family and we discussed
 * demands" — and a person decides, which is exactly why nothing here
 * auto-rejects.
 */
class BlockedWordSeeder extends Seeder
{
    private const WORDS = [
        // Contact smuggled past the exchange
        ['whatsapp',  '*',  'Contact detail in free text'],
        ['imo',       'en', 'Contact detail in free text'],
        ['viber',     '*',  'Contact detail in free text'],
        ['telegram',  '*',  'Contact detail in free text'],
        ['messenger', '*',  'Contact detail in free text'],
        ['gmail',     '*',  'Contact detail in free text'],
        ['ইমো',       'bn', 'Contact detail in free text'],
        ['হোয়াটসঅ্যাপ', 'bn', 'Contact detail in free text'],

        // Dowry — illegal under the Dowry Prohibition Act 2018
        ['dowry',   '*',  'Dowry demand'],
        ['যৌতুক',    'bn', 'Dowry demand'],
        ['ডিমান্ড',   'bn', 'Dowry demand'],

        // Money asked for in a profile is a scam pattern, every time
        ['bkash',   '*',  'Payment request in a profile'],
        ['বিকাশ',    'bn', 'Payment request in a profile'],
        ['nagad',   'en', 'Payment request in a profile'],
    ];

    public function run(): void
    {
        $rows = [];

        foreach (self::WORDS as [$word, $locale, $note]) {
            $rows[] = [
                'word' => $word, 'locale' => $locale, 'note' => $note,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }

        DB::table('blocked_words')->insert($rows);

        $this->command?->info('  Word list: '.count($rows).' entries (flag only — never an automatic reject).');
    }
}
