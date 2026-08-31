<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The soft consent gate (spec 9.5) and the operator consent gate (10.4).
 *
 * A profile someone else created is not a profile until the candidate says
 * so from their own phone. Until then it is not searchable, not indexable,
 * and not visible to an operator.
 */
class ConsentGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unconfirmed_profile_is_not_publicly_visible(): void
    {
        $user = User::factory()->unconfirmed()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->isPubliclyVisible());

        $this->get('/profile/'.$user->profile_id)->assertNotFound();
    }

    public function test_confirming_makes_the_profile_visible(): void
    {
        $user = User::factory()->unconfirmed()->create(['public_indexing' => 'INDEXED']);
        Profile::factory()->create(['user_id' => $user->id]);

        $user->update(['candidate_confirmed_at' => now(), 'status' => 'ACTIVE']);

        $this->assertTrue($user->fresh()->isPubliclyVisible());
    }

    public function test_declining_removes_the_profile(): void
    {
        $user = User::factory()->unconfirmed()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $token = bin2hex(random_bytes(16));
        cache()->put('candidate_confirm:'.$token, $user->id, now()->addDay());

        $this->post('/confirm/'.$token.'/reject')->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'CLOSED']);
        $this->assertSoftDeleted('profiles', ['user_id' => $user->id]);
    }

    public function test_an_operator_view_without_a_live_consent_is_logged_as_a_violation(): void
    {
        $operator = User::factory()->operator()->create();
        $subject  = User::factory()->create();
        Profile::factory()->create(['user_id' => $subject->id]);

        $case = \App\Models\MatchmakerCase::create([
            'client_user_id' => $subject->id,
            'operator_id'    => $operator->id,
        ]);

        app(\App\Services\ConsentGate::class)
            ->viewAsOperator($operator, $subject->profile, $case, 'sourcing');

        $this->assertDatabaseHas('operator_access_logs', [
            'operator_id'     => $operator->id,
            'consent_present' => false,
        ]);
    }
}
