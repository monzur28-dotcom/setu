<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['MALE', 'FEMALE']);

        return [
            'user_id'           => User::factory(),
            'gender'            => $gender,
            'date_of_birth'     => $this->faker->dateTimeBetween('-38 years', '-22 years')->format('Y-m-d'),
            'height_cm'         => $gender === 'MALE' ? $this->faker->numberBetween(160, 185) : $this->faker->numberBetween(148, 170),
            'marital_status'    => 'NEVER_MARRIED',
            'religion'          => 'ISLAM',
            'prayer_habit'      => $this->faker->randomElement(['FIVE_TIMES', 'REGULARLY', 'OCCASIONALLY']),
            'mother_tongue'     => 'BANGLA',
            'marriage_timeline' => $this->faker->randomElement(['WITHIN_6_MONTHS', 'WITHIN_A_YEAR', 'WITHIN_2_YEARS']),
            // A factory profile is a normal, published one; the review
            // states are reached explicitly through ->pending().
            'moderation_status' => 'APPROVED',
            'completeness'      => $this->faker->numberBetween(40, 95),
            'response_rate'     => $this->faker->numberBetween(20, 90),
        ];
    }

    public function pending(): static { return $this->state(fn () => ['moderation_status' => 'PENDING']); }

    public function male(): static   { return $this->state(fn () => ['gender' => 'MALE']); }
    public function female(): static { return $this->state(fn () => ['gender' => 'FEMALE']); }
}
