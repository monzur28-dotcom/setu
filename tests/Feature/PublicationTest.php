<?php

namespace Tests\Feature;

use App\Models\AccessRequest;
use App\Models\BlockedWord;
use App\Models\ModerationItem;
use App\Models\Photo;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileVisibility;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ProfileReview;
use App\Services\VisibilitySerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pre-publication review, the word list, and what a free account is allowed
 * to learn. If one of these fails, either unreviewed text is on the public
 * internet or a free account is reading a name it did not pay for.
 */
class PublicationTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $user = [], array $profile = []): User
    {
        $u = User::factory()->create($user + [
            'status'                 => 'ACTIVE',
            'verification_level'     => 'PHONE',
            'registrant_relationship' => 'SELF',
        ]);
        $p = Profile::factory()->create($profile + ['user_id' => $u->id]);
        ProfileVisibility::create(['profile_id' => $p->id, 'show_name' => true]);

        return $u->fresh();
    }

    private function paid(User $user): User
    {
        $plan = Plan::create([
            'code' => 'standard', 'product' => 'MATRIMONIAL',
            'name_en' => 'Standard', 'name_bn' => 'স্ট্যান্ডার্ড',
            'market' => 'BD', 'currency' => 'BDT', 'price' => 1490, 'duration_days' => 90,
        ]);

        Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'product' => 'MATRIMONIAL',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(30), 'status' => 'ACTIVE',
        ]);

        return $user->fresh();
    }

    /** Requirement 1. Nothing is published until an admin has approved it. */
    public function test_an_unapproved_profile_is_not_discoverable_and_404s_on_its_own_url(): void
    {
        $pending = $this->member(profile: ['moderation_status' => 'PENDING']);

        $this->assertSame(0, Profile::discoverable()->where('id', $pending->profile->id)->count());

        $this->get('/profile/'.$pending->profile_id)->assertNotFound();

        // ...but its owner and staff can still reach it, or nobody could fix
        // the thing that is holding it up.
        $this->actingAs($pending)->get('/profile/'.$pending->profile_id)->assertOk();

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($admin)->get('/profile/'.$pending->profile_id)->assertOk();
    }

    public function test_approval_publishes_the_profile(): void
    {
        $pending = $this->member(profile: ['moderation_status' => 'PENDING']);
        $admin   = User::factory()->create(['role' => 'ADMIN']);

        app(ProfileReview::class)->approve($pending->profile, $admin);

        $this->assertSame('APPROVED', $pending->profile->fresh()->moderation_status);
        $this->assertSame(1, Profile::discoverable()->where('id', $pending->profile->id)->count());
        $this->get('/profile/'.$pending->profile_id)->assertOk();
    }

    /**
     * The answer to "does an edit take my profile down?" is no. The approved
     * text keeps serving while the new text waits.
     */
    public function test_an_edit_to_a_live_profile_waits_without_taking_it_down(): void
    {
        $member = $this->member(profile: [
            'moderation_status' => 'APPROVED',
            'about_me'          => 'The approved sentence.',
        ]);

        app(ProfileReview::class)->submitEdit($member->profile, 'profile', [
            'about_me' => 'The new sentence, not yet read.',
        ]);

        $profile = $member->profile->fresh();

        $this->assertSame('APPROVED', $profile->moderation_status);
        $this->assertSame('The approved sentence.', $profile->about_me);
        $this->assertSame('The new sentence, not yet read.', $profile->pending_about_me);
        $this->assertSame(1, Profile::discoverable()->where('id', $profile->id)->count());

        app(ProfileReview::class)->approve($profile, User::factory()->create(['role' => 'ADMIN']));

        $profile->refresh();
        $this->assertSame('The new sentence, not yet read.', $profile->about_me);
        $this->assertNull($profile->pending_about_me);
    }

    /** Requirement 3. A listed word flags for review — it never rejects. */
    public function test_a_listed_word_raises_the_queue_priority_without_rejecting(): void
    {
        BlockedWord::create(['word' => 'whatsapp', 'locale' => '*']);

        $member = $this->member(profile: [
            'moderation_status' => 'PENDING',
            'about_me'          => 'Reach me on whatsapp any time.',
        ]);

        app(ProfileReview::class)->submitForFirstReview($member->profile);

        $item = ModerationItem::where('entity_type', 'PROFILE')
            ->where('entity_id', $member->profile->id)->firstOrFail();

        $this->assertSame(1, $item->priority);
        $this->assertSame(['whatsapp'], json_decode($item->matched_words, true));

        // Flagged, not judged. It is still waiting for a person.
        $this->assertSame('PENDING', $member->profile->fresh()->moderation_status);
    }

    /** A word must not match inside a longer one. */
    public function test_an_english_entry_matches_whole_words_only(): void
    {
        BlockedWord::create(['word' => 'imo', 'locale' => '*']);

        $member = $this->member(profile: [
            'moderation_status' => 'PENDING',
            'about_me'          => 'I am an optimo enthusiast and a timid person.',
        ]);

        $this->assertSame([], app(ProfileReview::class)->scan($member->profile));
    }

    /** Requirement 2. Registration will not complete without a photograph. */
    public function test_registration_requires_a_photograph(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);

        $this->actingAs($user)->post('/register/details', [
            'gender'         => 'FEMALE',
            'date_of_birth'  => now()->subYears(26)->toDateString(),
            'marital_status' => 'NEVER_MARRIED',
            'religion'       => 'ISLAM',
            'country'        => 'BD',
        ])->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile);
    }

    /** Requirement 5. A free account browses; it does not learn who anyone is. */
    public function test_a_free_account_sees_the_profile_id_where_a_name_would_be(): void
    {
        $subject = $this->member();
        $free    = $this->member();

        $serializer = app(VisibilitySerializer::class);

        $this->assertSame(
            $subject->profile_id,
            $serializer->forViewer($subject->profile, $free)['display_name'],
        );

        // An anonymous visitor is entitled to no more than a free account.
        $this->assertSame(
            $subject->profile_id,
            app(VisibilitySerializer::class)->forViewer($subject->profile, null)['display_name'],
        );
    }

    public function test_a_paid_account_sees_the_name_the_owner_allowed(): void
    {
        $subject = $this->member(['candidate_name' => 'Nadia Sultana']);
        $viewer  = $this->paid($this->member());

        $this->assertSame(
            'Nadia Sultana',
            app(VisibilitySerializer::class)->forViewer($subject->profile, $viewer)['display_name'],
        );
    }

    /**
     * Requirement 4. A hidden photograph opens for one viewer, because the
     * owner granted that viewer's request — and for nobody else.
     */
    public function test_a_hidden_photo_opens_only_for_a_granted_request(): void
    {
        $subject = $this->member();
        $subject->profile->visibility->update(['show_photos' => false]);

        Photo::create([
            'profile_id' => $subject->profile->id,
            'uploaded_by_user_id' => $subject->id,
            'path' => 'demo/a.jpg', 'blur_path' => 'demo/a-blur.jpg',
            'status' => 'APPROVED', 'is_primary' => true,
        ]);

        $asker     = $this->member();
        $bystander = $this->member();

        $blurred = fn (User $who) => app(VisibilitySerializer::class)
            ->forViewer($subject->profile->fresh(), $who)['photos'][0]['blurred'];

        $this->assertTrue($blurred($asker));

        AccessRequest::create([
            'from_profile_id' => $asker->profile->id,
            'to_profile_id'   => $subject->profile->id,
            'type'            => 'PHOTOS',
            'status'          => 'GRANTED',
        ]);

        $this->assertFalse($blurred($asker));
        $this->assertTrue($blurred($bystander));
    }
}
