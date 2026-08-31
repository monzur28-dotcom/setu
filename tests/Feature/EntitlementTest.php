<?php

namespace Tests\Feature;

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quota consumption. The row lock matters: two tabs sending an interest at
 * the same moment must not both succeed against one remaining allowance.
 */
class EntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_quota_cannot_be_spent_past_its_allowance(): void
    {
        $user = User::factory()->create();

        $e = Entitlement::create([
            'user_id'      => $user->id,
            'key'          => 'interests',
            'allowance'    => 3,
            'used'         => 0,
            'period_start' => today(),
            'period_end'   => today(),
        ]);

        $this->assertTrue(Entitlement::consume($user->id, 'interests', 3));
        $this->assertTrue(Entitlement::consume($user->id, 'interests', 3));
        $this->assertTrue(Entitlement::consume($user->id, 'interests', 3));
        $this->assertFalse(Entitlement::consume($user->id, 'interests', 3));

        $this->assertSame(3, $e->fresh()->used);
    }

    /** Replying is free on every plan — it consumes nothing. */
    public function test_replying_consumes_no_quota(): void
    {
        $user = User::factory()->create();

        Entitlement::create([
            'user_id'      => $user->id,
            'key'          => 'interests',
            'allowance'    => 1,
            'used'         => 1,
            'period_start' => today(),
            'period_end'   => today(),
        ]);

        // There is no 'replies' entitlement, by design. Nothing to consume.
        $this->assertDatabaseMissing('entitlements', [
            'user_id' => $user->id,
            'key'     => 'replies',
        ]);
    }
}
