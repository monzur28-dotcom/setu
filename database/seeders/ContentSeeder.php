<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Programmatic landing pages, guides, one success story and a handful of
 * classified advertisements.
 *
 * Landing pages ship with index_status = AUTO. The nightly job flips a
 * page to noindex when fewer than `setu.landing.min_profiles` live
 * profiles match it — a thin page that ranks is worse than no page.
 * Spec 22.3.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->landingPages();
        $this->guides();
        $this->stories();
        $this->ads();
    }

    private function landingPages(): void
    {
        $districts = DB::table('geo_districts')->pluck('id', 'name_en');

        $rows = [];

        // district x gender — the highest-intent shape there is
        foreach (['Dhaka', 'Chattogram', 'Sylhet', 'Khulna', 'Rajshahi', 'Cumilla'] as $d) {
            foreach ([['bride', 'FEMALE', 'পাত্রী', 'Bride'], ['groom', 'MALE', 'পাত্র', 'Groom']] as [$slugWord, $gender, $bn, $en]) {
                $rows[] = [
                    'slug'        => strtolower($d).'-'.$slugWord,
                    'locale'      => 'bn',
                    'h1'          => "{$d} জেলার যাচাইকৃত {$bn}",
                    'filter_json' => json_encode(['district_id' => $districts[$d] ?? null, 'gender' => $gender]),
                    'intro_bn'    => "{$d} জেলার যাচাইকৃত {$bn}দের প্রোফাইল। প্রতিটি প্রোফাইলের মালিক নিজে ঠিক করেন কোন তথ্য পাবলিক থাকবে — আমরা কারো ফোন নম্বর বিক্রি করি না।",
                    'intro_en'    => "Verified {$en} profiles from {$d}. Each person decides which fields stay public. We never sell a phone number.",
                    'faq_json'    => json_encode([
                        ['q' => "Are these profiles verified?", 'a' => "Every profile shows its verification level. NID and selfie checks are done by our team and the documents are deleted within 30 days."],
                        ['q' => "Can I see contact details?", 'a' => "Only after both of you agree to exchange them. No plan buys that."],
                    ], JSON_UNESCAPED_UNICODE),
                    'match_count'      => 0,
                    'count_updated_at' => now(),
                    'index_status'     => 'AUTO',
                    'internal_links'   => json_encode([]),
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        }

        // profession x gender
        foreach ([['doctor', 'Doctor', 'ডাক্তার'], ['engineer', 'Civil Engineer', 'প্রকৌশলী'], ['teacher', 'Teacher', 'শিক্ষক'], ['banker', 'Banker', 'ব্যাংকার']] as [$slug, $prof, $bn]) {
            foreach ([['bride', 'FEMALE', 'পাত্রী'], ['groom', 'MALE', 'পাত্র']] as [$slugWord, $gender, $genderBn]) {
                $rows[] = [
                    'slug'        => $slug.'-'.$slugWord,
                    'locale'      => 'bn',
                    'h1'          => "পেশায় {$bn} {$genderBn}",
                    'filter_json' => json_encode(['profession' => $prof, 'gender' => $gender]),
                    'intro_bn'    => "পেশায় {$bn} — যাচাইকৃত প্রোফাইল।",
                    'intro_en'    => "Verified profiles working as a {$prof}.",
                    'faq_json'    => json_encode([]),
                    'match_count' => 0, 'count_updated_at' => now(),
                    'index_status' => 'AUTO', 'internal_links' => json_encode([]),
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        }

        // diaspora
        foreach ([['usa', 'US', 'যুক্তরাষ্ট্র', 'the USA'], ['canada', 'CA', 'কানাডা', 'Canada'], ['uk', 'GB', 'যুক্তরাজ্য', 'the UK']] as [$slug, $cc, $bn, $en]) {
            $rows[] = [
                'slug'        => 'bangladeshi-'.$slug,
                'locale'      => 'bn',
                'h1'          => "{$bn}ে বসবাসরত বাংলাদেশি পাত্র-পাত্রী",
                'filter_json' => json_encode(['country' => $cc]),
                'intro_bn'    => "{$bn}ে বসবাসরত বাংলাদেশিদের প্রোফাইল।",
                'intro_en'    => "Bangladeshi profiles living in {$en}.",
                'faq_json'    => json_encode([]),
                'match_count' => 0, 'count_updated_at' => now(),
                'index_status' => 'AUTO', 'internal_links' => json_encode([]),
                'created_at' => now(), 'updated_at' => now(),
            ];
        }

        DB::table('landing_pages')->insert($rows);

        // Cross-link every page to three siblings, so no page is an orphan.
        $all = DB::table('landing_pages')->pluck('slug', 'id');
        foreach ($all as $id => $slug) {
            DB::table('landing_pages')->where('id', $id)->update([
                'internal_links' => json_encode(
                    $all->reject(fn ($s) => $s === $slug)->random(min(3, $all->count() - 1))->values()->all()
                ),
            ]);
        }

        $this->command?->info('  Content: '.count($rows).' landing pages (all AUTO — thin pages noindex themselves).');
    }

    private function guides(): void
    {
        $guides = [
            ['how-to-write-a-biodata',
             'How to write a biodata that gets replies',
             'যে বায়োডাটায় উত্তর আসে',
             "The three mistakes almost every biodata makes: it lists qualifications without saying anything about the person, it hides the marriage timeline, and it names a salary. Fix those three and your reply rate roughly doubles.\n\nWrite four short paragraphs: who you are, what your day looks like, what your family is like, and what you actually want in a partner. Be specific. \"Practising\" means different things to different families — say what it means in yours.",
             "প্রায় প্রতিটি বায়োডাটায় তিনটি ভুল থাকে: যোগ্যতার তালিকা থাকে কিন্তু মানুষটির কথা থাকে না, বিয়ের সময়সীমা লুকানো থাকে, আর বেতনের অঙ্ক লেখা থাকে। এই তিনটি ঠিক করলেই উত্তরের হার প্রায় দ্বিগুণ হয়।\n\nচারটি ছোট অনুচ্ছেদ লিখুন: আপনি কে, আপনার দিন কেমন কাটে, আপনার পরিবার কেমন, আর সঙ্গীর কাছে আপনি আসলে কী চান।"],

            ['questions-to-ask-before-marriage',
             'Twenty questions worth asking before you say yes',
             'হ্যাঁ বলার আগে যে বিশটি প্রশ্ন করা উচিত',
             "Money, in-laws, where you will live, whether both of you will work, how many children and when, how decisions get made when you disagree. None of these are rude questions. Not asking them is what turns into a hard first year.",
             "টাকা, শ্বশুরবাড়ি, কোথায় থাকবেন, দুজনেই চাকরি করবেন কি না, সন্তান কয়টি ও কখন, মতভেদ হলে সিদ্ধান্ত কীভাবে হবে। এর একটিও অভদ্র প্রশ্ন নয়। না জিজ্ঞেস করাই প্রথম বছরটিকে কঠিন করে তোলে।"],

            ['dowry-is-illegal',
             'Dowry: what the law actually says',
             'যৌতুক: আইন আসলে কী বলে',
             "The Dowry Prohibition Act makes demanding, giving and taking dowry an offence — for both families. Any profile, message or advertisement on this platform that mentions it is removed, and the account can be closed.\n\nIf a family is asking you for money to proceed, that is not a negotiation. Report it.",
             "যৌতুক নিরোধ আইনে যৌতুক চাওয়া, দেওয়া ও নেওয়া — তিনটিই অপরাধ, দুই পরিবারের জন্যই। এই প্ল্যাটফর্মে যে প্রোফাইল, বার্তা বা বিজ্ঞাপনে এর উল্লেখ থাকবে তা সরিয়ে ফেলা হবে এবং অ্যাকাউন্ট বন্ধ হতে পারে।"],

            ['meeting-safely',
             'Meeting for the first time, safely',
             'প্রথম দেখা: নিরাপদে',
             "Meet in a public place. Tell somebody in your family where you are going and when you expect to be back. Arrange your own transport there and back. Do not send money to anybody you have met online, for any reason, no matter how convincing the story.",
             "প্রকাশ্য জায়গায় দেখা করুন। পরিবারের কাউকে জানিয়ে যান কোথায় যাচ্ছেন ও কখন ফিরবেন। যাওয়া-আসার ব্যবস্থা নিজে করুন। অনলাইনে পরিচিত কাউকে কোনো কারণেই টাকা পাঠাবেন না — গল্প যত বিশ্বাসযোগ্যই হোক।"],

            ['for-parents',
             'For parents: what your child sees, and what you see',
             'অভিভাবকদের জন্য: সন্তান যা দেখে, আপনি যা দেখেন',
             "You can be linked to your child's profile at one of three levels, and they choose the level. At every level you can see progress. At no level can you read their messages, accept an interest on their behalf, or edit their profile. That is not a setting we have hidden — the capability does not exist.\n\nEvery time you open their dashboard, they can see that you did.",
             "আপনি সন্তানের প্রোফাইলের সাথে তিনটি স্তরের যেকোনো একটিতে যুক্ত থাকতে পারেন, আর স্তরটি সন্তান বেছে দেয়। সব স্তরেই আপনি অগ্রগতি দেখবেন। কোনো স্তরেই তাদের বার্তা পড়া, তাদের হয়ে আগ্রহ গ্রহণ করা বা প্রোফাইল সম্পাদনা করা যায় না। এটি লুকানো কোনো সেটিং নয় — এই সুবিধাটি তৈরিই করা হয়নি।\n\nআপনি যতবার তাদের ড্যাশবোর্ড খুলবেন, ততবার তারা দেখতে পাবে।"],
        ];

        DB::table('guides')->insert(collect($guides)->map(fn ($g) => [
            'slug' => $g[0], 'title_en' => $g[1], 'title_bn' => $g[2],
            'body_en' => $g[3], 'body_bn' => $g[4],
            'published' => true,
            'created_at' => now(), 'updated_at' => now(),
        ])->all());
    }

    private function stories(): void
    {
        $profiles = DB::table('profiles')->orderBy('id')->limit(2)->pluck('id');
        if ($profiles->count() < 2) { return; }

        $userId = DB::table('profiles')->where('id', $profiles[0])->value('user_id');

        // A story cannot exist without a consent row. The column is NOT NULL
        // for exactly this reason — spec 23.5.
        $consentId = DB::table('consents')->insertGetId([
            'user_id'      => $userId,
            'consent_type' => 'SUCCESS_STORY_PUBLICATION',
            'granted'      => true,
            'version'      => '2026-01',
            'evidence'     => json_encode(['both_parties_signed' => true, 'method' => 'written']),
            'granted_at'   => now()->subDays(10),
            'created_at'   => now(), 'updated_at' => now(),
        ]);

        DB::table('success_stories')->insert([
            'profile_a_id'     => $profiles[0],
            'profile_b_id'     => $profiles[1],
            'body_en'          => "We matched in March. Our families met three weeks later in Dhaka, and we were married in July. What we both liked was that neither of us could see the other's number until we both agreed — it made the first month feel unhurried.",
            'body_bn'          => "মার্চে আমাদের পরিচয়। তিন সপ্তাহ পর ঢাকায় দুই পরিবারের দেখা, আর জুলাইয়ে বিয়ে। আমাদের দুজনেরই ভালো লেগেছে যে, দুজনে রাজি না হওয়া পর্যন্ত কেউ কারো নম্বর দেখতে পাইনি — প্রথম মাসটা তাই তাড়াহুড়ো ছাড়াই কেটেছে।",
            'city'             => 'Dhaka',
            'weeks_to_connect' => 16,
            'consent_id'       => $consentId,
            'status'           => 'PUBLISHED',
            'created_at'       => now()->subDays(9), 'updated_at' => now(),
        ]);
    }

    private function ads(): void
    {
        $districts = DB::table('geo_districts')->pluck('id', 'name_en');
        $posters   = DB::table('users')->where('role', 'MEMBER')->orderBy('id')->limit(6)->pluck('id');

        $ads = [
            ['BRIDE', 'Government officer, 31, Dhaka — bride wanted',
             'Muslim family, Dhaka. Our son is 31, works in a government office, never married, prays regularly. Looking for a practising, educated bride between 24 and 28. Family is nuclear and settled in Mirpur.', 31, 'MASTER', 'Government Officer', 'Dhaka'],
            ['GROOM', 'Doctor, 27, Chattogram — groom wanted',
             'Our daughter is 27, MBBS, currently doing residency. We are looking for an educated, practising groom, preferably a doctor or engineer, aged 28 to 34. Family is originally from Chattogram.', 27, 'MBBS', 'Doctor', 'Chattogram'],
            ['BRIDE', 'Software engineer in the UK, 29 — bride wanted',
             'British Bangladeshi family in London. Our son is 29, software engineer, British citizen. Seeking a bride from Bangladesh or the UK, 23 to 27, practising, willing to relocate after marriage. Sponsorship discussed openly.', 29, 'BACHELOR', 'Software Engineer', 'Sylhet'],
            ['GROOM', 'Teacher, 25, Sylhet — groom wanted',
             'Our daughter is 25, a school teacher, from a religious family in Sylhet. Looking for a practising groom with a stable job, 27 to 33. No dowry — please do not ask.', 25, 'MASTER', 'Teacher', 'Sylhet'],
            ['BRIDE', 'Businessman, 34, Cumilla — bride wanted',
             'Divorced, no children, running a family business in Cumilla. Looking for a bride 26 to 32, never married or divorced, understanding and family-oriented.', 34, 'BACHELOR', 'Business Owner', 'Cumilla'],
            ['GROOM', 'Banker, 28, Khulna — groom wanted',
             'Our sister is 28, works in a private bank in Khulna. We are looking for a groom 30 to 36 with a stable income. Both families should meet before anything is decided.', 28, 'MASTER', 'Banker', 'Khulna'],
        ];

        DB::table('classified_ads')->insert(collect($ads)->map(fn ($a, $i) => [
            'slug'           => str($a[1])->slug()->limit(70, '')->toString().'-'.($i + 1),
            'poster_user_id' => $posters[$i] ?? $posters[0],
            'looking_for'    => $a[0],
            'headline'       => $a[1],
            'body'           => $a[2],
            'age'            => $a[3],
            'education'      => $a[4],
            'profession'     => $a[5],
            'religion'       => 'ISLAM',
            'marital_status' => $i === 4 ? 'DIVORCED' : 'NEVER_MARRIED',
            'district_id'    => $districts[$a[6]] ?? null,
            'contact_phone'  => sprintf('+88018%08d', 20000000 + $i),
            // Two advertisers do not want ghotoks calling them.
            'no_media_flag'  => in_array($i, [1, 3], true),
            'status'         => 'LIVE',
            'expires_at'     => now()->addDays(30),
            'created_at'     => now()->subDays($i), 'updated_at' => now(),
        ])->all());
    }
}
