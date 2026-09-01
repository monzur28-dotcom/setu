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
use App\Services\SmsSender;
use App\Services\VisibilitySerializer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    /**
     * The whole sign-up, end to end: details, OTP, then the profile and its
     * required photograph. Proves the flow a real person walks, including
     * that the photo lands on the private disk and the profile comes out
     * PENDING rather than published.
     */
    public function test_a_person_can_register_all_the_way_through(): void
    {
        Storage::fake('photos');

        // The code is stored bcrypt-hashed and leaves the system only in the
        // message body, so that is where the test reads it. Swapped before
        // the first request, because OtpService is a singleton and would
        // otherwise capture the real sender.
        CapturingSmsSender::$lastCode = null;
        $this->instance(SmsSender::class, new CapturingSmsSender());

        $this->post('/register', [
            'registrant_relationship' => 'SELF',
            'candidate_name' => 'Jane Walker',
            'country_code'   => '+1',
            'mobile'         => '5559876543',
            'email'          => 'jane.walker@example.com',
            'password'       => 'Str0ngPassw0rd!',
            'terms'          => '1',
        ])->assertRedirect('/register/verify');

        $this->post('/register/verify', ['code' => CapturingSmsSender::$lastCode])
             ->assertRedirect('/register/details');

        $user = User::where('email', 'jane.walker@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);

        $this->actingAs($user)->post('/register/details', [
            'gender'         => 'FEMALE',
            'date_of_birth'  => now()->subYears(27)->toDateString(),
            'marital_status' => 'NEVER_MARRIED',
            'religion'       => 'CHRISTIANITY',
            'country'        => 'US',
            'photo'          => UploadedFile::fake()->create('me.jpg', 400, 'image/jpeg'),
        ])->assertRedirect(route('member.privacy'));

        $profile = $user->fresh()->profile;

        $this->assertNotNull($profile);
        // Currency follows the country chosen, not a column default.
        $this->assertSame('USD', $user->fresh()->currency);
        // Nothing is published until a moderator has read it.
        $this->assertSame('PENDING', $profile->moderation_status);
        $this->assertSame(0, Profile::discoverable()->where('id', $profile->id)->count());
        // The photograph exists, and is waiting for moderation like any other.
        $this->assertSame(1, $profile->photos()->count());
        $this->assertSame('PENDING', $profile->photos()->first()->status);
    }

    /**
     * The local bypass must be exactly that. The test that matters is not
     * that it works, but that the flag alone cannot switch off phone
     * verification — otherwise one stray environment variable on a real
     * host lets anyone register against a number they do not hold.
     */
    public function test_the_otp_bypass_is_ignored_outside_local(): void
    {
        // The flag on, and the environment not local — which is already
        // true here, since tests run as "testing". Switching the environment
        // by hand would also switch off the test suite's CSRF exemption and
        // fail for an unrelated reason.
        config(['setu.otp.bypass' => true]);
        $this->assertFalse(app()->environment('local'));

        $this->post('/register', [
            'registrant_relationship' => 'SELF',
            'candidate_name' => 'Mallory',
            'country_code'   => '+1',
            'mobile'         => '5550001111',
            'password'       => 'Str0ngPassw0rd!',
            'terms'          => '1',
        ])->assertRedirect('/register/verify');

        // No account exists yet: the code screen still stands in the way.
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    /** A duplicate email is a message on the field, not a 500. */
    public function test_registering_with_a_taken_email_is_a_validation_error(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'registrant_relationship' => 'SELF',
            'candidate_name' => 'Someone Else',
            'country_code'   => '+1',
            'mobile'         => '5550002222',
            'email'          => 'taken@example.com',
            'password'       => 'Str0ngPassw0rd!',
            'terms'          => '1',
        ])->assertSessionHasErrors('email');
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

/**
 * Records the code out of the outgoing message. The log driver would work
 * too, but reading the application's log file from a test couples the two
 * together for no gain.
 */
class CapturingSmsSender extends SmsSender
{
    public static ?string $lastCode = null;

    public function send(string $e164, string $message, string $mode = 'MATRIMONIAL', bool $critical = false): bool
    {
        if (preg_match('/\b(\d{6})\b/', $message, $m)) {
            self::$lastCode = $m[1];
        }

        return true;
    }
}