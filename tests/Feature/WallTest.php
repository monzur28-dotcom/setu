<?php

namespace Tests\Feature;

use App\Models\ConnectProfile;
use App\Models\Profile;
use App\Models\User;
use App\Services\ConnectWall;
use App\Services\VisibilitySerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * The wall between the matrimonial product and Connect.
 *
 * These are not style tests. Each one corresponds to a wall rule in the
 * spec, and each one has a matching failure mode that would put a member
 * in a dating product in front of their family.
 */
class WallTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attrs = []): User
    {
        $u = User::factory()->create($attrs);
        Profile::factory()->create(['user_id' => $u->id]);

        return $u->fresh();
    }

    /** W8 — only the PRIVACY role can learn that a Connect profile exists. */
    public function test_connect_membership_is_unreadable_by_ordinary_staff(): void
    {
        $member = $this->member(['dating_enabled' => true]);

        foreach (['MEMBER', 'GUARDIAN', 'OPERATOR', 'MODERATOR', 'ADMIN'] as $role) {
            $staff = User::factory()->create(['role' => $role]);

            try {
                ConnectWall::membershipFor($member, $staff);
                $this->fail("Role {$role} was able to read Connect membership.");
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }

        $privacy = User::factory()->create(['role' => 'PRIVACY']);
        $this->assertTrue(ConnectWall::membershipFor($member, $privacy));

        // and the read itself is audited
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $privacy->id]);
    }

    /** W1 — a Connect profile is never reachable from the matrimonial side. */
    public function test_connect_profile_is_not_serialised_by_the_matrimonial_serializer(): void
    {
        $member = $this->member(['dating_enabled' => true]);
        ConnectProfile::create([
            'user_id'      => $member->id,
            'connect_id'   => ConnectProfile::generateConnectId(),
            'display_name' => 'Rimi',
            'age'          => 27,
            'city'         => 'Dhaka',
            'intentions'   => 'GETTING_TO_KNOW',
        ]);

        $viewer  = $this->member();
        $payload = app(VisibilitySerializer::class)->forViewer($member->profile, $viewer);

        $flat = json_encode($payload);
        $this->assertStringNotContainsString('Rimi', $flat);
        $this->assertStringNotContainsString('connect', strtolower($flat));
    }

    /** W5 — Connect pages are never indexable and are 404 for staff roles. */
    public function test_connect_routes_are_closed_to_operators_and_send_noindex(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)->get('/connect')->assertNotFound();

        $member = $this->member(['dating_enabled' => true]);
        $this->actingAs($member)->get('/connect')
             ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    /** W4 — deleting Connect leaves the marriage profile untouched. */
    public function test_deleting_connect_does_not_touch_the_marriage_profile(): void
    {
        $member = $this->member(['dating_enabled' => true]);
        ConnectProfile::create([
            'user_id'      => $member->id,
            'connect_id'   => ConnectProfile::generateConnectId(),
            'display_name' => 'Rimi',
            'age'          => 27,
            'city'         => 'Dhaka',
            'intentions'   => 'GETTING_TO_KNOW',
        ]);

        ConnectWall::deleteConnectOnly($member);

        $this->assertDatabaseMissing('connect_profiles', ['user_id' => $member->id]);
        $this->assertDatabaseHas('profiles', ['user_id' => $member->id]);
        $this->assertFalse($member->fresh()->dating_enabled);
    }
}
