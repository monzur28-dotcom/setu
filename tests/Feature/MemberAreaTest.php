<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\ProfileVisibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The member area assumes a member profile.
 *
 * Six controllers read `$request->user()->profile` and used the result
 * without checking, so any account without one — a staff login, or someone
 * who abandoned registration at step two — got a 500 on whichever member
 * page they opened. These cover the guard that replaced that.
 */
class MemberAreaTest extends TestCase
{
    use RefreshDatabase;

    /** Every route the guard protects, so a new one cannot quietly regress. */
    public static function memberRoutes(): array
    {
        return [
            'dashboard' => ['/me'],
            'search'    => ['/me/search'],
            'privacy'   => ['/me/privacy'],
            'interests' => ['/interests'],
            'mailbox'   => ['/mailbox'],
            'access'    => ['/access-requests'],
            'family'    => ['/family'],
            'settings'  => ['/settings'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('memberRoutes')]
    public function test_staff_are_sent_to_their_console_not_a_500(string $uri): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);

        $this->assertNull($admin->profile);

        $this->actingAs($admin)->get($uri)->assertRedirect(route('admin.dashboard'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('memberRoutes')]
    public function test_an_unfinished_registration_is_sent_to_finish_it(string $uri): void
    {
        $user = User::factory()->create(['role' => 'MEMBER', 'status' => 'ACTIVE']);

        $this->actingAs($user)->get($uri)->assertRedirect(route('register.step2'));
    }

    public function test_a_member_with_a_profile_gets_through(): void
    {
        $user = User::factory()->create([
            'role' => 'MEMBER', 'status' => 'ACTIVE',
            'verification_level' => 'PHONE', 'registrant_relationship' => 'SELF',
        ]);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        ProfileVisibility::create(['profile_id' => $profile->id]);

        $this->actingAs($user->fresh())->get('/me')->assertOk();
        $this->actingAs($user->fresh())->get('/me/search')->assertOk();
    }

    /**
     * The two routes that CREATE a profile must stay outside the guard, or
     * nobody could ever finish signing up — the guard would bounce them to
     * the very page it is guarding.
     */
    public function test_the_profile_creating_routes_stay_reachable(): void
    {
        $user = User::factory()->create(['role' => 'MEMBER', 'status' => 'ACTIVE']);

        $this->assertNull($user->profile);

        $this->actingAs($user)->get('/register/details')->assertOk();
    }
}
