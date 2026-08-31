<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\GuardianAccessLog;
use App\Models\GuardianLink;
use App\Models\GuardianNote;
use App\Services\VisibilitySerializer;
use Illuminate\Http\Request;

/**
 * ---------------------------------------------------------------------------
 *  THE FAMILY DASHBOARD
 * ---------------------------------------------------------------------------
 *  Read chapter 12.2 before adding anything here.
 *
 *  Things this controller deliberately CANNOT do, at any level, for anyone,
 *  including on a support request:
 *
 *    · read the candidate's mailbox            (G4)
 *    · accept or decline an interest           (G5)
 *    · edit the profile or upload photos       (G6)
 *    · learn that Connect exists for them      (G12 — the wall)
 *
 *  There is no method below that does any of those, and none should be added.
 *  The design-review question for every new feature here is:
 *  "If this candidate were a 26-year-old woman whose father does not want her
 *   to marry outside the district, does this feature help her or expose her?"
 * ---------------------------------------------------------------------------
 */
class FamilyDashboardController extends Controller
{
    public function __construct(private readonly VisibilitySerializer $serializer) {}

    public function index(Request $request)
    {
        $link = $request->attributes->get('guardian_link');

        $this->log($link, 'dashboard_viewed');

        return view('family.dashboard', [
            'link'    => $link,
            'payload' => $this->serializer->forGuardian($link),
            'notes'   => $link->notes()->latest()->get(),
        ]);
    }

    public function introductions(Request $request)
    {
        $link = $request->attributes->get('guardian_link');

        // At L1 this is empty by construction, not by filtering a fuller list.
        abort_unless($link->may('see_connected'), 403, __('family.level_too_low'));

        $this->log($link, 'introductions_viewed');

        $profile = $link->candidate->profile;

        $connected = \App\Models\MailboxThread::where('profile_a_id', $profile->id)
            ->orWhere('profile_b_id', $profile->id)->get()
            ->map(function ($thread) use ($profile, $request) {
                $other = \App\Models\Profile::find($thread->otherProfileId($profile->id));

                return $other ? $this->serializer->forViewer($other, $request->user()) : null;
            })->filter();

        return view('family.introductions', compact('link', 'connected'));
    }

    public function storeNote(Request $request)
    {
        $link = $request->attributes->get('guardian_link');

        $request->validate(['body' => ['required', 'string', 'max:2000']]);

        // Private to the guardian. The candidate cannot read these, and
        // neither can staff.
        GuardianNote::create(['guardian_link_id' => $link->id, 'body' => $request->input('body')]);

        return back()->with('status', __('family.note_saved'));
    }

    /**
     * Guardian-to-guardian contact needs FOUR consents: both candidates and
     * both guardians. A decline is reported as "not available right now" and
     * is never attributed to a specific person — attributing it creates
     * family friction the product should not cause. Spec 7.4.
     */
    public function requestFamilyContact(Request $request)
    {
        $link = $request->attributes->get('guardian_link');

        abort_unless($link->may('contact_other_family'), 403);

        $this->log($link, 'family_contact_requested');

        return back()->with('status', __('family.contact_requested'));
    }

    public function accept(string $token)
    {
        $link = GuardianLink::where('invite_token', $token)->firstOrFail();

        $link->update(['link_status' => 'ACTIVE', 'accepted_at' => now(), 'invite_token' => null]);

        return view('family.accepted', compact('link'));
    }

    /** Every guardian read is logged and shown back to the candidate. */
    private function log(GuardianLink $link, string $action, ?string $ref = null): void
    {
        GuardianAccessLog::create([
            'guardian_link_id' => $link->id,
            'action'           => $action,
            'subject_ref'      => $ref,
            'ip'               => request()->ip(),
        ]);
    }
}
