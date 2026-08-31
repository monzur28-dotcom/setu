<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'profile_id'              => 'BD'.$this->faker->unique()->numberBetween(1000000, 9999999),
            'registrant_relationship' => 'SELF',
            'candidate_name'          => $this->faker->name(),
            'mobile_enc'              => '',            // set by configure()
            'mobile_hash'             => '',
            'email'                   => $this->faker->unique()->safeEmail(),
            'password'                => Hash::make('password'),
            'role'                    => 'MEMBER',
            'status'                  => 'ACTIVE',
            'verification_level'      => 'PHONE',
            'mobile_verified_at'      => now(),
            'candidate_confirmed_at'  => now(),
            'public_indexing'         => 'NOINDEX',
            'locale'                  => 'bn',
            'currency'                => 'BDT',
            'remember_token'          => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            if ($user->mobile_hash === '') {
                $user->setMobile('+88017'.$this->faker->unique()->numerify('########'));
            }
        });
    }

    /** A profile someone else created and the candidate has not confirmed. */
    public function unconfirmed(): static
    {
        return $this->state(fn () => [
            'registrant_relationship' => 'FATHER',
            'registrant_name'         => $this->faker->name('male'),
            'candidate_confirmed_at'  => null,
            'status'                  => 'UNVERIFIED',
        ]);
    }

    public function operator(): static
    {
        return $this->state(fn () => ['role' => 'OPERATOR', 'verification_level' => 'NID_SELFIE']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'ADMIN', 'verification_level' => 'NID_SELFIE']);
    }
}
