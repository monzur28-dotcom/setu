<?php

namespace App\Services;

use App\Models\AccessRequest;
use App\Models\GuardianLink;
use App\Models\PrivateAccess;
use App\Models\Profile;
use App\Models\User;

/**
 * =============================================================================
 *  THE SINGLE DISCLOSURE PATH
 * =============================================================================
 *  Every response that contains another person's data goes through here.
 *  Nothing else in the application is permitted to assemble a profile payload.
 *
 *  Why: this product has roughly eight viewer types (anonymous, member,
 *  interested, private-access granted, contact-exchanged, guardian at three
 *  levels, operator under consent) across two products. That matrix is too
 *  large for each controller to decide for itself — eventually one of them
 *  leaks an employer name onto a family dashboard.
 *
 *  One place to audit, one place to test, one place to fix.
 *  Master specification 20.2, and acceptance criteria 27.2 P1/P16.
 * =============================================================================
 */
class VisibilitySerializer
{
    public const ANONYMOUS  = 'anonymous';
    public const MEMBER     = 'member';
    public const PRIVATE_OK = 'private';
    public const EXCHANGED  = 'exchanged';
    public const SELF       = 'self';
    public const GUARDIAN   = 'guardian';
    public const OPERATOR   = 'operator';

    /**
     * Fields that may appear in a PUBLIC profile — a thin, deliberately
     * non-identifying subset. Anything not on this list cannot reach an
     * unauthenticated response, whatever the caller does.
     */
    private const PUBLIC_FIELDS = [
        'profile_id', 'age', 'height_cm', 'marital_status', 'has_children',
        'religion', 'sect', 'prayer_habit', 'mother_tongue', 'education_level',
        'division', 'district', 'home_district', 'country', 'verified',
        'last_active', 'headline', 'relocation_intent',
        // Owner-toggled, off unless explicitly enabled:
        'display_name', 'gender', 'city', 'profession', 'hobbies', 'photos',
    ];

    /** Fields that appear only once private access has been granted. */
    private const PRIVATE_FIELDS = [
        'full_name', 'about_me', 'institution', 'employer', 'job_title',
        'income_band', 'area', 'family', 'languages_known', 'diet',
        'smoking', 'drinking', 'complexion', 'body_type', 'physical_status',
    ];

    /**
     * Contact NEVER appears in a serialised profile — not even at the private
     * level. It passes only through an accepted, two-sided ContactExchange.
     * No plan, coupon, admin action or support path substitutes for that.
     */
    public const NEVER_IN_PROFILE = ['mobile', 'email', 'social'];

    public function forViewer(Profile $subject, ?User $viewer, array $opts = []): array
    {
        $level = $opts['level'] ?? $this->levelFor($subject, $viewer);

        $out = $this->publicPayload($subject, $level, $viewer);

        if (in_array($level, [self::PRIVATE_OK, self::EXCHANGED, self::SELF], true)) {
            $out += $this->privatePayload($subject);
        }

        if ($level === self::GUARDIAN) {
            // A guardian sees the candidate's own progress, never a third
            // party's private data, and never — at any level — the mailbox.
            $out['guardian_view'] = true;
        }

        $out['_level'] = $level;

        return $out;
    }

    /**
     * Work out what this viewer is entitled to see. Deliberately conservative:
     * every unknown case falls through to ANONYMOUS.
     */
    public function levelFor(Profile $subject, ?User $viewer): string
    {
        if (! $viewer) {
            return self::ANONYMOUS;
        }

        if ($viewer->id === $subject->user_id) {
            return self::SELF;
        }

        if ($viewer->role === 'OPERATOR') {
            // Operators get PUBLIC by default. Private data requires a live
            // CaseConsent row, granted through ConsentGate — never here.
            return self::OPERATOR;
        }

        $viewerProfile = $viewer->profile;

        if (! $viewerProfile) {
            return self::MEMBER;
        }

        if (PrivateAccess::exists_($subject->id, $viewerProfile->id)) {
            return self::PRIVATE_OK;
        }

        return self::MEMBER;
    }

    // ---------------------------------------------------------------- public

    private function publicPayload(Profile $p, string $level, ?User $viewer = null): array
    {
        $v = $p->visibility;
        $loc = $p->location;
        $career = $p->career;

        // A viewer at MEMBER level or above sees the same public field set as
        // an anonymous visitor. Paying more never reveals more — only the
        // owner's own consent does.
        // A hidden photograph opens for one viewer at a time, and only
        // because the owner granted that viewer's request. Nothing else —
        // no plan, no staff role — turns the blur off.
        $showPhotos = $v?->show_photos
            || in_array($level, [self::PRIVATE_OK, self::EXCHANGED, self::SELF], true)
            || $this->photoAccessGranted($p, $viewer);

        return array_filter([
            'profile_id'        => $p->user->profile_id,
            'display_name'      => $this->nameFor($p, $v, $level, $viewer),
            'age'               => $p->age,
            'height_cm'         => $v?->show_height ? $p->height_cm : null,
            'gender'            => $v?->show_gender ? $p->gender : null,
            'marital_status'    => $p->marital_status,
            'has_children'      => $p->has_children,
            'religion'          => $p->religion,
            'sect'              => $p->sect,
            'prayer_habit'      => $p->prayer_habit,
            'mother_tongue'     => $p->mother_tongue,
            'headline'          => $p->headline,
            'education_level'   => $career?->education_level,
            'profession'        => $v?->show_profession ? $career?->profession : null,
            'division'          => $loc?->division?->name(),
            'district'          => $loc?->district?->name(),
            'home_district'     => $loc?->homeDistrict?->name(),
            'city'              => $v?->show_city ? $loc?->city : null,
            'country'           => $loc?->country,
            'relocation_intent' => $loc?->relocation_intent,
            'verified'          => $p->user->verification_level,
            'last_active'       => $p->user->last_active_at?->diffForHumans(),
            'photos'            => $this->photoPayload($p, $showPhotos),
        ], fn ($x) => $x !== null);
    }

    private function privatePayload(Profile $p): array
    {
        $career = $p->career;
        $fam = $p->family;
        $life = $p->lifestyle;

        return array_filter([
            'full_name'       => $p->user->candidate_name,
            'about_me'        => $p->about_me,
            'complexion'      => $p->complexion,
            'body_type'       => $p->body_type,
            'physical_status' => $p->physical_status,
            'languages_known' => $p->languages_known,
            'institution'     => $career?->institution,
            'employer'        => $career?->employer,
            'job_title'       => $career?->job_title,
            'income_band'     => $career?->income_band,
            'area'            => $p->location?->area,
            'diet'            => $life?->diet,
            'smoking'         => $life?->smoking,
            'drinking'        => $life?->drinking,
            'hobbies'         => $life?->hobbies,
            'family'          => $fam ? array_filter([
                'father_occupation'  => $fam->father_occupation,
                'mother_occupation'  => $fam->mother_occupation,
                'siblings'           => $fam->siblings,
                'family_type'        => $fam->family_type,
                'family_values'      => $fam->family_values,
                'family_involvement' => $fam->family_involvement,
                'about_family'       => $fam->about_family,
            ]) : null,
        ], fn ($x) => $x !== null);
    }

    /**
     * Who this viewer is allowed to know the subject as.
     *
     * A free account browses; it does not learn who anyone is. It sees the
     * opaque profile id — the same string an anonymous visitor sees — and a
     * name only once it has paid, or once the owner has granted it private
     * access, or once contact has been exchanged. Requirement: the free tier
     * may visit other candidates, not identify them.
     */
    private function nameFor(Profile $p, $v, string $level, ?User $viewer): string
    {
        if (in_array($level, [self::PRIVATE_OK, self::EXCHANGED, self::SELF, self::GUARDIAN], true)) {
            return $p->displayName(full: true);
        }

        // An operator's disclosure is decided by their case consent, not by
        // a plan; it is enforced in ConsentGate, so honour the owner here.
        if ($level === self::OPERATOR) {
            return $p->displayName(full: (bool) ($v?->show_name));
        }

        if (! $viewer?->can_('can_see_full_name')) {
            return $p->user->profile_id;
        }

        return $p->displayName(full: (bool) ($v?->show_name));
    }

    /**
     * Has the subject granted this viewer a look at hidden photographs?
     *
     * One indexed existence check per card. This class is a singleton, so
     * nothing here is cached: a stale grant is the one failure mode the
     * single disclosure path must not have, and an index makes the honest
     * version cheap enough that there is nothing to trade.
     */
    private function photoAccessGranted(Profile $subject, ?User $viewer): bool
    {
        $viewerProfileId = $viewer?->profile?->id;

        if (! $viewerProfileId) {
            return false;
        }

        return AccessRequest::query()
            ->where('from_profile_id', $viewerProfileId)
            ->where('to_profile_id', $subject->id)
            ->where('type', 'PHOTOS')
            ->where('status', 'GRANTED')
            ->exists();
    }

    private function photoPayload(Profile $p, bool $clear): array
    {
        return $p->approvedPhotos->map(fn ($photo) => [
            'id'      => $photo->id,
            'url'     => $photo->url(blurred: ! $clear),
            'blurred' => ! $clear,
        ])->all();
    }

    /**
     * A guardian's dashboard payload, filtered by the level the CANDIDATE
     * chose. Note what is structurally absent: there is no branch of this
     * method that can return message content, and none that can return
     * anything about Connect. Spec 12.1.
     */
    public function forGuardian(GuardianLink $link): array
    {
        $candidate = $link->candidate;
        $profile = $candidate->profile;

        $payload = [
            'candidate_name' => $candidate->candidate_name,
            'verified'       => $candidate->verification_level,
            'completeness'   => $profile?->completeness,
            'last_active'    => $candidate->last_active_at?->diffForHumans(),
            'level'          => $link->visibility_level,
            'counts'         => [
                'interests_received' => $profile?->receivedInterests()->count() ?? 0,
                'connections'        => $profile
                    ? \App\Models\MailboxThread::where('profile_a_id', $profile->id)
                        ->orWhere('profile_b_id', $profile->id)->count()
                    : 0,
            ],
        ];

        if ($link->may('see_connected')) {
            $payload['connected'] = [];   // populated by the controller
        }

        // read_mailbox and see_connect have no branch here, by design.
        return $payload;
    }
}
