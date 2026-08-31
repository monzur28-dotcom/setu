<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Forty demo members across the eight divisions, plus a diaspora slice.
 *
 * Two things about this data are deliberate and should survive editing:
 *
 *   1. Most profiles default to PRIVATE with photos hidden. If the demo
 *      data were mostly public, every screenshot would show the product
 *      behaving in the way the spec says it must not.
 *   2. Two profiles are registrant-created and left unconfirmed, so the
 *      consent gate is visible the moment you log in as an operator and
 *      cannot find them.
 *
 * Password for every demo account: "password".
 */
class DemoMemberSeeder extends Seeder
{
    private const MALE_NAMES = [
        'Tanvir Hasan', 'Rakibul Islam', 'Shafiqur Rahman', 'Mahmudul Karim',
        'Arif Chowdhury', 'Nazmul Hoque', 'Sabbir Ahmed', 'Imran Kabir',
        'Ashiqur Rahman', 'Fahim Reza', 'Zahid Hasan', 'Mizanur Rahman',
        'Rezaul Karim', 'Sadman Sakib', 'Rafiul Alam', 'Tawhidul Islam',
        'Anisur Rahman', 'Mehedi Hassan', 'Shahriar Kabir', 'Nafis Iqbal',
    ];

    private const FEMALE_NAMES = [
        'Nusrat Jahan', 'Farhana Akter', 'Sumaiya Islam', 'Tasnim Rahman',
        'Rubaiya Haque', 'Mahmuda Khatun', 'Sadia Afrin', 'Ishrat Jahan',
        'Nabila Hossain', 'Marufa Yeasmin', 'Sanjida Akter', 'Tahmina Begum',
        'Lamia Chowdhury', 'Rownak Ara', 'Shamima Nasrin', 'Umme Habiba',
        'Jarin Tasnim', 'Fariha Anjum', 'Sumona Rahman', 'Nadia Sultana',
    ];

    private const PROFESSIONS = [
        ['Software Engineer', 'BACHELOR', 'PRIVATE', 'Dhaka'],
        ['Doctor', 'MBBS', 'GOVERNMENT', 'Dhaka'],
        ['Banker', 'MASTER', 'PRIVATE', 'Chattogram'],
        ['Teacher', 'MASTER', 'GOVERNMENT', 'Sylhet'],
        ['Civil Engineer', 'ENGINEERING', 'PRIVATE', 'Dhaka'],
        ['Government Officer', 'MASTER', 'GOVERNMENT', 'Rajshahi'],
        ['Business Owner', 'BACHELOR', 'BUSINESS', 'Cumilla'],
        ['Pharmacist', 'BACHELOR', 'PRIVATE', 'Khulna'],
        ['Lecturer', 'MPHIL', 'GOVERNMENT', 'Mymensingh'],
        ['Accountant', 'MASTER', 'PRIVATE', 'Dhaka'],
        ['Nurse', 'DIPLOMA', 'GOVERNMENT', 'Barishal'],
        ['Architect', 'BACHELOR', 'PRIVATE', 'Dhaka'],
        ['NGO Programme Officer', 'MASTER', 'NGO', 'Rangpur'],
        ['Garments Merchandiser', 'BACHELOR', 'PRIVATE', 'Gazipur'],
        ['Lawyer', 'MASTER', 'SELF_EMPLOYED', 'Dhaka'],
    ];

    /** Diaspora slice: country code, city, residency, relocation posture. */
    private const DIASPORA = [
        ['US', 'New York',  'CITIZEN',        'PARTNER_RELOCATES'],
        ['US', 'Michigan',  'PERMANENT_RESIDENT', 'PARTNER_RELOCATES'],
        ['CA', 'Toronto',   'PERMANENT_RESIDENT', 'PARTNER_RELOCATES'],
        ['GB', 'London',    'CITIZEN',        'PARTNER_RELOCATES'],
        ['GB', 'Birmingham', 'CITIZEN',       'OPEN'],
        ['AE', 'Dubai',     'WORK_VISA',      'WILL_RELOCATE'],
    ];

    public function run(): void
    {
        $districts = DB::table('geo_districts')->pluck('id', 'name_en');
        $dhaka     = $districts['Dhaka'];

        $created = 0;
        $males = $females = [];

        foreach ([['MALE', self::MALE_NAMES], ['FEMALE', self::FEMALE_NAMES]] as [$gender, $names]) {
            foreach ($names as $i => $name) {
                [$profession, $education, $employedIn, $city] = self::PROFESSIONS[$i % count(self::PROFESSIONS)];

                $isDiaspora = $i >= 16;                       // last four of each set
                $diaspora   = $isDiaspora ? self::DIASPORA[($i - 16) % count(self::DIASPORA)] : null;

                // Registrant: roughly a third of profiles are made by family.
                $byFamily     = $i % 3 === 1;
                $relationship = $byFamily
                    ? ($gender === 'FEMALE' ? 'FATHER' : 'SISTER')
                    : 'SELF';

                // Two profiles stay unconfirmed so the consent gate is visible.
                $unconfirmed = $byFamily && $i < 4;

                $age    = $gender === 'MALE' ? 27 + ($i % 9) : 23 + ($i % 8);
                $mobile = sprintf('+88017%08d', 10000000 + $created);

                $user = new User([
                    'profile_id'              => User::generateProfileId(),
                    'registrant_relationship' => $relationship,
                    'registrant_name'         => $byFamily ? 'Family member' : null,
                    'candidate_name'          => $name,
                    'email'                   => 'demo'.($created + 1).'@setu.test',
                    'password'                => Hash::make('password'),
                    'role'                    => 'MEMBER',
                    'status'                  => $unconfirmed ? 'UNVERIFIED' : 'ACTIVE',
                    'verification_level'      => $i % 4 === 0 ? 'NID_SELFIE' : ($i % 2 === 0 ? 'NID' : 'PHONE'),
                    // Public profiles are the minority, as the spec intends.
                    'public_indexing'         => $i % 5 === 0 ? 'INDEXED' : 'NOINDEX',
                    'locale'                  => $isDiaspora ? 'en' : 'bn',
                    'currency'                => $isDiaspora ? 'USD' : 'BDT',
                ]);
                $user->setMobile($mobile);
                $user->mobile_verified_at     = now();
                $user->candidate_confirmed_at = $unconfirmed ? null : now()->subDays(30 - ($i % 25));
                $user->last_active_at         = now()->subHours($i * 3);
                $user->save();

                $profile = Profile::create([
                    'user_id'          => $user->id,
                    'gender'           => $gender,
                    'date_of_birth'    => now()->subYears($age)->subDays($i * 7)->toDateString(),
                    'height_cm'        => $gender === 'MALE' ? 165 + ($i % 15) : 152 + ($i % 13),
                    'marital_status'   => $i % 11 === 0 ? 'DIVORCED' : 'NEVER_MARRIED',
                    'religion'         => $i % 9 === 0 ? 'HINDUISM' : 'ISLAM',
                    'prayer_habit'     => ['FIVE_TIMES', 'REGULARLY', 'OCCASIONALLY', 'PREFER_NOT_TO_SAY'][$i % 4],
                    'mother_tongue'    => $city === 'Sylhet' ? 'SYLHETI' : 'BANGLA',
                    'headline'         => $this->headline($profession, $i),
                    'about_me'         => $this->about($name, $profession, $i),
                    'marriage_timeline' => ['WITHIN_6_MONTHS', 'WITHIN_A_YEAR', 'WITHIN_2_YEARS', 'NO_FIXED_TIMELINE'][$i % 4],
                    'completeness'     => 55 + ($i % 40),
                    'response_rate'    => 40 + ($i % 55),
                    // Most demo profiles are published, so the browsing
                    // surfaces have something to show. Every fourth one is
                    // left in the queue: an admin logging in should find
                    // real work waiting, not an empty screen that hides
                    // the fact approval is required at all.
                    'moderation_status' => $i % 4 === 3 ? 'PENDING' : 'APPROVED',
                    'moderated_at'      => $i % 4 === 3 ? null : now()->subDays(29 - ($i % 25)),
                    'submitted_at'      => now()->subDays(30 - ($i % 25)),
                ]);

                // Visibility. The default row is the privacy-protective one;
                // two members in five have opened the screen and loosened it,
                // so both the clear and the blurred state appear while browsing.
                DB::table('profile_visibility')->insert([
                    'profile_id'            => $profile->id,
                    'show_photos'           => $i % 5 < 2,
                    'show_name'             => $i % 5 === 0,
                    'show_gender'           => true,
                    'show_height'           => true,
                    'show_city'             => true,
                    'show_profession'       => true,
                    'show_hobbies'          => true,
                    'allow_operator_access' => $i % 7 === 0,
                    'created_at'            => now(), 'updated_at' => now(),
                ]);

                DB::table('profile_locations')->insert([
                    'profile_id'        => $profile->id,
                    'country'           => $diaspora[0] ?? 'BD',
                    'district_id'       => $districts[$city] ?? $dhaka,
                    'home_district_id'  => $districts[array_rand($districts->toArray())] ?? $dhaka,
                    'city'              => $diaspora[1] ?? $city,
                    'residency_status'  => $diaspora[2] ?? 'CITIZEN',
                    'relocation_intent' => $diaspora[3] ?? ($gender === 'FEMALE' ? 'WILL_RELOCATE' : 'WILL_NOT'),
                    'sponsorship_willing' => $isDiaspora ? 'DISCUSS' : null,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);

                DB::table('profile_careers')->insert([
                    'profile_id'      => $profile->id,
                    'education_level' => $education,
                    'education_detail' => $education.' — '.$profession,
                    'institution'     => ['University of Dhaka', 'BUET', 'North South University', 'Rajshahi University', 'Chittagong University'][$i % 5],
                    'profession'      => $profession,
                    'job_title'       => $profession,
                    'employed_in'     => $employedIn,
                    'created_at'      => now(), 'updated_at' => now(),
                ]);

                DB::table('profile_families')->insert([
                    'profile_id'         => $profile->id,
                    'father_occupation'  => ['Retired', 'Businessman', 'Teacher', 'Farmer', 'Government service'][$i % 5],
                    'mother_occupation'  => ['Homemaker', 'Teacher', 'Homemaker', 'Doctor', 'Homemaker'][$i % 5],
                    'siblings'           => json_encode(['brothers' => $i % 3, 'sisters' => ($i + 1) % 3]),
                    'family_type'        => $i % 2 === 0 ? 'NUCLEAR' : 'JOINT',
                    'family_status'      => ['MIDDLE_CLASS', 'UPPER_MIDDLE', 'MIDDLE_CLASS'][$i % 3],
                    'family_values'      => ['TRADITIONAL', 'MODERATE', 'MODERATE', 'LIBERAL'][$i % 4],
                    'family_involvement' => ['FAMILY_LED', 'FAMILY_INVOLVED', 'MY_DECISION_FAMILY_INFORMED', 'MY_DECISION'][$i % 4],
                    'created_at'         => now(), 'updated_at' => now(),
                ]);

                DB::table('profile_lifestyles')->insert([
                    'profile_id' => $profile->id,
                    'diet'       => $i % 9 === 0 ? 'NON_VEGETARIAN' : 'HALAL_ONLY',
                    'smoking'    => 'NO',
                    'drinking'   => 'NO',
                    'hobbies'    => json_encode(array_slice(
                        ['Reading', 'Cricket', 'Cooking', 'Travelling', 'Photography', 'Gardening', 'Football', 'Music'],
                        $i % 5, 3)),
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                DB::table('preferences')->insert([
                    'profile_id'    => $profile->id,
                    'age_min'       => $gender === 'MALE' ? 20 : 26,
                    'age_max'       => $gender === 'MALE' ? 30 : 38,
                    'height_min_cm' => $gender === 'MALE' ? 148 : 165,
                    'height_max_cm' => $gender === 'MALE' ? 172 : 190,
                    'marital_status' => json_encode(['NEVER_MARRIED']),
                    'religion'      => json_encode([$i % 9 === 0 ? 'HINDUISM' : 'ISLAM']),
                    'prayer_habit'  => json_encode(['FIVE_TIMES', 'REGULARLY']),
                    'districts'     => json_encode([]),
                    'exclude_districts' => json_encode([]),
                    'countries'     => json_encode($isDiaspora ? ['BD', $diaspora[0]] : ['BD']),
                    'education_level' => json_encode(['BACHELOR', 'MASTER', 'MBBS', 'ENGINEERING']),
                    'profession'    => json_encode([]),
                    'family_involvement' => json_encode([]),
                    'marriage_timeline'  => json_encode([]),
                    'diet'          => 'ANY',
                    'smoking'       => 'NO',
                    'drinking'      => 'NO',
                    'relocation'    => 'ANY',
                    // Postures: a soft preference does not disqualify anyone.
                    'postures'      => json_encode([
                        'religion'   => 'MUST',
                        'age'        => 'PREFER',
                        'education'  => 'PREFER',
                        'district'   => 'NICE_TO_HAVE',
                        'profession' => 'NICE_TO_HAVE',
                    ]),
                    'about_partner' => 'Someone kind, practising, and honest about what they want from life.',
                    'created_at'    => now(), 'updated_at' => now(),
                ]);

                // Registration consent — a row, not a boolean. Spec 9.2.
                DB::table('consents')->insert([
                    'user_id'      => $user->id,
                    'consent_type' => 'TERMS_AND_PRIVACY',
                    'granted'      => true,
                    'version'      => '2026-01',
                    'evidence'     => json_encode(['ip' => '203.0.113.'.(($i % 250) + 1), 'ua' => 'seeder', 'at' => now()->toIso8601String()]),
                    'granted_at'   => now()->subDays(30),
                    'created_at'   => now(), 'updated_at' => now(),
                ]);

                $gender === 'MALE' ? $males[] = $user : $females[] = $user;
                $created++;
            }
        }

        $this->interactions($males, $females);
        $this->guardians($males, $females);
        $this->queuePendingProfiles();

        $this->command?->info("  Demo: {$created} members (password: \"password\"), 2 left unconfirmed.");
    }

    /**
     * The profiles left at PENDING need a queue row, or the moderation
     * screen shows nothing and the approval requirement looks optional.
     * Run through ProfileReview so the word scan is the real one.
     */
    private function queuePendingProfiles(): void
    {
        $review = app(\App\Services\ProfileReview::class);

        $pending = Profile::with(['family', 'preference'])
            ->where('moderation_status', 'PENDING')->get();

        // One profile carries exactly what the word list exists to catch —
        // a phone contact smuggled past the two-sided exchange — so the
        // flagged path in the moderation queue is visible on a fresh
        // install rather than something you have to construct by hand.
        if ($flagged = $pending->first()) {
            $flagged->forceFill([
                'about_me' => $flagged->about_me.' Message me on whatsapp, it is faster.',
            ])->save();
        }

        foreach ($pending as $profile) {
            $review->submitForFirstReview($profile);
        }

        $this->command?->info('  Review queue: '.$pending->count().' profiles awaiting a first read.');
    }

    /** A believable trail of interests, views and one exchanged contact. */
    private function interactions(array $males, array $females): void
    {
        foreach (array_slice($males, 0, 10) as $i => $m) {
            $f = $females[$i] ?? null;
            if (! $f) { continue; }

            $mp = $m->profile->id;
            $fp = $f->profile->id;

            DB::table('interests')->insert([
                'from_profile_id' => $mp,
                'to_profile_id'   => $fp,
                'status'          => ['PENDING', 'ACCEPTED', 'DECLINED', 'PENDING'][$i % 4],
                'responded_at'    => $i % 4 === 0 ? null : now()->subDays($i),
                'expires_at'      => now()->addDays(30),
                'created_at'      => now()->subDays($i + 2), 'updated_at' => now(),
            ]);

            DB::table('profile_views')->insert([
                'viewer_profile_id' => $fp,
                'viewed_profile_id' => $mp,
                'source'            => 'SEARCH',
                'created_at'        => now()->subDays($i),
            ]);

            if ($i < 3) {
                // Mutual private access — one action, both directions.
                foreach ([[$mp, $fp], [$fp, $mp]] as [$a, $b]) {
                    DB::table('private_accesses')->insert([
                        'grantor_profile_id' => $a,
                        'grantee_profile_id' => $b,
                        'granted_at'         => now()->subDays($i),
                        'created_at'         => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /** Three guardian links, one at each visibility level. */
    private function guardians(array $males, array $females): void
    {
        $levels = ['L1_PROGRESS', 'L2_INTRODUCTIONS', 'L3_FULL'];

        foreach ($levels as $i => $level) {
            $candidate = $females[$i];
            $guardian  = $males[10 + $i];

            DB::table('guardian_links')->insert([
                'guardian_user_id'  => $guardian->id,
                'candidate_user_id' => $candidate->id,
                'relationship'      => ['FATHER', 'BROTHER', 'MOTHER'][$i],
                'visibility_level'  => $level,
                'link_status'       => 'ACTIVE',
                'invite_token'      => bin2hex(random_bytes(16)),
                'created_profile'   => $i === 0,
                'accepted_at'       => now()->subDays(20 - $i),
                'created_at'        => now()->subDays(21), 'updated_at' => now(),
            ]);

            // The candidate's consent to that link, at that level.
            DB::table('consents')->insert([
                'user_id'      => $candidate->id,
                'consent_type' => 'GUARDIAN_LINK_'.$level,
                'granted'      => true,
                'version'      => '2026-01',
                'evidence'     => json_encode(['guardian_user_id' => $guardian->id, 'level' => $level]),
                'granted_at'   => now()->subDays(20 - $i),
                'created_at'   => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function headline(string $profession, int $i): string
    {
        return [
            "{$profession}. Practising, family-oriented, looking to settle.",
            "{$profession} in Dhaka. Straightforward about what I want.",
            "{$profession}. My family and I are looking together.",
            "{$profession}. Quiet person, close to my parents.",
        ][$i % 4];
    }

    private function about(string $name, string $profession, int $i): string
    {
        return trim(preg_replace('/\s+/', ' ', [
            "I work as a {$profession}. I pray regularly, I am close to my family, and
             I would like a partner who is honest and patient. I am not looking to rush,
             but I am serious about marriage within the year.",

            "Alhamdulillah, life has been kind. I finished my studies, I have a stable
             job, and my parents are now looking with me. I would rather meet somebody
             who is clear about what they want than talk for months without direction.",

            "I am a {$profession} and the first in my family to finish a master's.
             I want a partner who values education and is comfortable with my mother
             living with us. Please be practising.",

            "I like reading, cooking and long walks. My work as a {$profession} keeps me
             busy but I always make time for family. Looking for someone kind and settled.",
        ][$i % 4]));
    }
}
