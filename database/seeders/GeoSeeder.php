<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The eight divisions and sixty-four districts of Bangladesh.
 *
 * Profiles store district IDs, never names — the Bangla spelling of a
 * district is revised more often than you would expect, and a stored
 * string turns every revision into a data migration. Spec 6.2.
 */
class GeoSeeder extends Seeder
{
    /** division slug => [en, bn, [district_en, district_bn], ...] */
    private const DATA = [
        'dhaka' => ['Dhaka', 'ঢাকা', [
            ['Dhaka', 'ঢাকা'], ['Gazipur', 'গাজীপুর'], ['Kishoreganj', 'কিশোরগঞ্জ'],
            ['Manikganj', 'মানিকগঞ্জ'], ['Munshiganj', 'মুন্সিগঞ্জ'], ['Narayanganj', 'নারায়ণগঞ্জ'],
            ['Narsingdi', 'নরসিংদী'], ['Tangail', 'টাঙ্গাইল'], ['Faridpur', 'ফরিদপুর'],
            ['Gopalganj', 'গোপালগঞ্জ'], ['Madaripur', 'মাদারীপুর'], ['Rajbari', 'রাজবাড়ী'],
            ['Shariatpur', 'শরীয়তপুর'],
        ]],
        'chattogram' => ['Chattogram', 'চট্টগ্রাম', [
            ['Chattogram', 'চট্টগ্রাম'], ["Cox's Bazar", 'কক্সবাজার'], ['Bandarban', 'বান্দরবান'],
            ['Rangamati', 'রাঙ্গামাটি'], ['Khagrachhari', 'খাগড়াছড়ি'], ['Feni', 'ফেনী'],
            ['Lakshmipur', 'লক্ষ্মীপুর'], ['Noakhali', 'নোয়াখালী'], ['Cumilla', 'কুমিল্লা'],
            ['Chandpur', 'চাঁদপুর'], ['Brahmanbaria', 'ব্রাহ্মণবাড়িয়া'],
        ]],
        'rajshahi' => ['Rajshahi', 'রাজশাহী', [
            ['Rajshahi', 'রাজশাহী'], ['Bogura', 'বগুড়া'], ['Joypurhat', 'জয়পুরহাট'],
            ['Naogaon', 'নওগাঁ'], ['Natore', 'নাটোর'], ['Chapainawabganj', 'চাঁপাইনবাবগঞ্জ'],
            ['Pabna', 'পাবনা'], ['Sirajganj', 'সিরাজগঞ্জ'],
        ]],
        'khulna' => ['Khulna', 'খুলনা', [
            ['Khulna', 'খুলনা'], ['Bagerhat', 'বাগেরহাট'], ['Chuadanga', 'চুয়াডাঙ্গা'],
            ['Jashore', 'যশোর'], ['Jhenaidah', 'ঝিনাইদহ'], ['Kushtia', 'কুষ্টিয়া'],
            ['Magura', 'মাগুরা'], ['Meherpur', 'মেহেরপুর'], ['Narail', 'নড়াইল'],
            ['Satkhira', 'সাতক্ষীরা'],
        ]],
        'barishal' => ['Barishal', 'বরিশাল', [
            ['Barishal', 'বরিশাল'], ['Barguna', 'বরগুনা'], ['Bhola', 'ভোলা'],
            ['Jhalokati', 'ঝালকাঠি'], ['Patuakhali', 'পটুয়াখালী'], ['Pirojpur', 'পিরোজপুর'],
        ]],
        'sylhet' => ['Sylhet', 'সিলেট', [
            ['Sylhet', 'সিলেট'], ['Habiganj', 'হবিগঞ্জ'], ['Moulvibazar', 'মৌলভীবাজার'],
            ['Sunamganj', 'সুনামগঞ্জ'],
        ]],
        'rangpur' => ['Rangpur', 'রংপুর', [
            ['Rangpur', 'রংপুর'], ['Dinajpur', 'দিনাজপুর'], ['Gaibandha', 'গাইবান্ধা'],
            ['Kurigram', 'কুড়িগ্রাম'], ['Lalmonirhat', 'লালমনিরহাট'], ['Nilphamari', 'নীলফামারী'],
            ['Panchagarh', 'পঞ্চগড়'], ['Thakurgaon', 'ঠাকুরগাঁও'],
        ]],
        'mymensingh' => ['Mymensingh', 'ময়মনসিংহ', [
            ['Mymensingh', 'ময়মনসিংহ'], ['Jamalpur', 'জামালপুর'], ['Netrokona', 'নেত্রকোণা'],
            ['Sherpur', 'শেরপুর'],
        ]],
    ];

    /** A handful of upazilas for the districts a demo actually browses. */
    private const UPAZILAS = [
        'dhaka'       => [['Dhanmondi', 'ধানমন্ডি'], ['Uttara', 'উত্তরা'], ['Mirpur', 'মিরপুর'], ['Savar', 'সাভার'], ['Keraniganj', 'কেরানীগঞ্জ']],
        'chattogram'  => [['Pahartali', 'পাহাড়তলী'], ['Hathazari', 'হাটহাজারী'], ['Patiya', 'পটিয়া'], ['Sitakunda', 'সীতাকুণ্ড']],
        'sylhet'      => [['Sylhet Sadar', 'সিলেট সদর'], ['Beanibazar', 'বিয়ানীবাজার'], ['Golapganj', 'গোলাপগঞ্জ'], ['Jaintiapur', 'জৈন্তাপুর']],
        'cumilla'     => [['Cumilla Sadar', 'কুমিল্লা সদর'], ['Debidwar', 'দেবিদ্বার'], ['Laksam', 'লাকসাম']],
        'khulna'      => [['Khulna Sadar', 'খুলনা সদর'], ['Dumuria', 'ডুমুরিয়া'], ['Phultala', 'ফুলতলা']],
        'rajshahi'    => [['Boalia', 'বোয়ালিয়া'], ['Paba', 'পবা'], ['Godagari', 'গোদাগাড়ী']],
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::DATA as $slug => [$en, $bn, $districts]) {
            $divisionId = DB::table('geo_divisions')->insertGetId([
                'name_en' => $en, 'name_bn' => $bn, 'slug' => $slug,
            ]);

            foreach ($districts as [$dEn, $dBn]) {
                $dSlug = str($dEn)->slug()->toString();

                $districtId = DB::table('geo_districts')->insertGetId([
                    'division_id' => $divisionId,
                    'name_en'     => $dEn,
                    'name_bn'     => $dBn,
                    'slug'        => $dSlug,
                ]);

                if (isset(self::UPAZILAS[$dSlug])) {
                    DB::table('geo_upazilas')->insert(collect(self::UPAZILAS[$dSlug])
                        ->map(fn ($u) => [
                            'district_id' => $districtId,
                            'name_en'     => $u[0],
                            'name_bn'     => $u[1],
                        ])->all());
                }
            }
        }

        $this->command?->info(sprintf(
            '  Geo: %d divisions, %d districts, %d upazilas.',
            DB::table('geo_divisions')->count(),
            DB::table('geo_districts')->count(),
            DB::table('geo_upazilas')->count(),
        ));

        unset($now);
    }
}
