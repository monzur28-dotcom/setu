<?php

namespace App\Services;

use App\Models\CaseConsent;
use App\Models\MatchmakerCase;
use App\Models\OperatorAccessLog;
use App\Models\Profile;
use App\Models\User;

/**
 * The operator boundary.
 *
 * An operator gets the PUBLIC field set by default, exactly like any visitor.
 * Private data is returned only against a live CaseConsent row, and every
 * single view — public or private — is written to operator_access_logs and
 * surfaced back to the member.
 *
 * This is the whole difference from the reference model, whose published
 * privacy page permits a matchmaker to disclose any candidate's full profile
 * "to anyone for matchmaking purposes at will". Spec 16.5.
 */
class ConsentGate
{
    public function __construct(private readonly VisibilitySerializer $serializer) {}

    public function viewAsOperator(User $operator, Profile $subject, ?MatchmakerCase $case, string $reason = ''): array
    {
        $consent = null;

        if ($case) {
            $this->assertOwnCase($operator, $case);
            $consent = CaseConsent::live($case->id, $subject->user_id, 'VIEW_PRIVATE');
        }

        // The subject can switch operator access off entirely, for everyone.
        if ($consent && ! $subject->visibility?->allow_operator_access) {
            $consent = null;
        }

        $level = $consent
            ? VisibilitySerializer::PRIVATE_OK
            : VisibilitySerializer::ANONYMOUS;

        OperatorAccessLog::create([
            'operator_id'        => $operator->id,
            'case_id'            => $case?->id,
            'subject_profile_id' => $subject->id,
            'fields_returned'    => $consent ? 'PRIVATE' : 'PUBLIC',
            'reason'             => $reason ?: 'sourcing',
            'consent_present'    => (bool) $consent,
            'ip'                 => request()?->ip(),
        ]);

        return $this->serializer->forViewer($subject, $operator, ['level' => $level]);
    }

    /** An operator sees only their own assigned cases. Never anyone else's. */
    public function assertOwnCase(User $operator, MatchmakerCase $case): void
    {
        abort_unless($case->operator_id === $operator->id, 403,
            'An operator may only act inside their own assigned cases.');
    }

    /**
     * Showing a candidate's profile to a client requires that CANDIDATE's
     * own consent — they bought nothing and owe the business nothing.
     */
    public function mayShareWithClient(MatchmakerCase $case, Profile $candidate): bool
    {
        return CaseConsent::live($case->id, $candidate->user_id, 'SHARE_PER_CASE') !== null
            || CaseConsent::live($case->id, $candidate->user_id, 'SHARE_BLANKET') !== null;
    }
}
