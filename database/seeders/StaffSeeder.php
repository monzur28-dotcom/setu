<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Four staff accounts, one per role that exists. They are separate people
 * on purpose: the success-fee rule requires that the person who records a
 * fee is not the person who confirms it, and a single shared admin login
 * quietly defeats that. Spec 19.4.
 *
 * The PRIVACY role is the only one that can read `dating_enabled`, and
 * every such read is written to audit_logs. Wall rule W8.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['admin@setu.test',    '+8801700000001', 'Admin',            'ADMIN'],
            ['ghotok@setu.test',   '+8801700000002', 'Rehana Ghotok',    'OPERATOR'],
            ['ghotok2@setu.test',  '+8801700000003', 'Kamrul Ghotok',    'OPERATOR'],
            ['mod@setu.test',      '+8801700000004', 'Moderator',        'MODERATOR'],
            ['privacy@setu.test',  '+8801700000005', 'Privacy Officer',  'PRIVACY'],
        ];

        foreach ($staff as [$email, $mobile, $name, $role]) {
            $u = new User([
                'profile_id'              => User::generateProfileId(),
                'registrant_relationship' => 'SELF',
                'candidate_name'          => $name,
                'email'                   => $email,
                'password'                => Hash::make('password'),
                'role'                    => $role,
                'status'                  => 'ACTIVE',
                'verification_level'      => 'NID_SELFIE',
                'locale'                  => 'en',
            ]);
            $u->setMobile($mobile);
            $u->email_verified_at      = now();
            $u->mobile_verified_at     = now();
            $u->candidate_confirmed_at = now();
            $u->save();
        }

        $this->command?->info('  Staff: '.count($staff).' accounts (password: "password").');
    }
}
