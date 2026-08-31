<?php

namespace Tests\Feature;

use App\Models\GuardianLink;
use App\Models\PrivateAccess;
use App\Models\Profile;
use App\Models\SuccessFee;
use App\Models\User;
use App\Services\ContactMasker;
use App\Services\VisibilitySerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Everything about who can see what. If one of these fails, somebody's
 * phone number is on the internet.
 */
class DisclosureTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attrs = []): User
    {
        $u = User::factory()->create($attrs);
        Profile::factory()->create(['user_id' => $u->id]);

        return $u->fresh();
    }

    /** A contact detail is never a profile field, at any visibility level. */
    public function test_contact_details_are_never_in_a_serialised_profile(): void
    {
        $subject = $this->member();
        $viewer  = $this->member();

        foreach ([null, $viewer, $subject] as $who) {
            $payload = json_encode(
                app(VisibilitySerializer::class)->forViewer($subject->profile, $who)
            );

            foreach (VisibilitySerializer::NEVER_IN_PROFILE as $field) {
                $this->assertStringNotContainsString(
                    '"'.$field.'"', $payload,
                    "Field `{$field}` leaked at viewer level."
                );
            }
        }
    }

    /** Private access is mutual — one action, both directions. */
    public function test_granting_private_access_is_mutual(): void
    {
        $a = $this->member();
        $b = $this->member();

        PrivateAccess::grantMutually($a->profile->id, $b->profile->id);

        $this->assertDatabaseHas('private_accesses', [
            'grantor_profile_id' => $a->profile->id,
            'grantee_profile_id' => $b->profile->id,
        ]);
        $this->assertDatabaseHas('private_accesses', [
            'grantor_profile_id' => $b->profile->id,
            'grantee_profile_id' => $a->profile->id,
        ]);
    }

    /** The twelve things a guardian can never do, at any level. */
    public function test_a_guardian_cannot_read_messages_or_act_at_any_level(): void
    {
        foreach (['L1_PROGRESS', 'L2_INTRODUCTIONS', 'L3_FULL'] as $level) {
            $link = new GuardianLink(['visibility_level' => $level, 'link_status' => 'ACTIVE']);

            foreach (['read_mailbox', 'act_on_interest', 'edit_profile', 'upload_photos', 'see_connect'] as $capability) {
                $this->assertFalse(
                    $link->may($capability),
                    "Guardian at {$level} was allowed to {$capability}."
                );
            }
        }
    }

    /** The masker catches Bengali digits too — that is the common bypass. */
    public function test_contact_masker_catches_bengali_digits(): void
    {
        [$masked, $filtered, $reason] = app(ContactMasker::class)
            ->mask('আমার নম্বর ০১৭১২৩৪৫৬৭৮ এ কল দিন');

        $this->assertTrue($filtered);
        $this->assertSame('phone_bn', $reason);
        $this->assertStringNotContainsString('০১৭১২৩৪৫৬৭৮', $masked);
    }

    /** A success fee cannot be recorded and confirmed by the same person. */
    public function test_success_fee_needs_two_people(): void
    {
        $operator = User::factory()->operator()->create();
        $client   = $this->member();

        $case = \App\Models\MatchmakerCase::create([
            'client_user_id' => $client->id,
            'operator_id'    => $operator->id,
        ]);

        $fee = SuccessFee::create([
            'case_id'        => $case->id,
            'client_user_id' => $client->id,
            'amount'         => 50000,
            'currency'       => 'BDT',
            'recorded_by'    => $operator->id,
        ]);

        $this->expectException(RuntimeException::class);
        $fee->confirm($operator);
    }
}
