<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\Interest;
use App\Models\MailboxThread;
use App\Models\PrivateAccess;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user()->profile;
        $tab = $request->query('tab', 'received');

        $query = $tab === 'sent'
            ? Interest::where('from_profile_id', $me->id)->with('to.user')
            : Interest::where('to_profile_id', $me->id)->with('from.user');

        if ($tab === 'received') {
            $query->where('status', 'PENDING');
        }

        return view('member.interests', [
            'tab'       => $tab,
            'interests' => $query->latest()->paginate(20),
            'me'        => $me,
            'counts'    => [
                'received' => Interest::where('to_profile_id', $me->id)->where('status', 'PENDING')->count(),
                'sent'     => Interest::where('from_profile_id', $me->id)->count(),
                'accepted' => Interest::where('to_profile_id', $me->id)->where('status', 'ACCEPTED')->count(),
            ],
        ]);
    }

    /**
     * SENDING is the paywall, and the only paywall. Receiving and replying
     * are free on every plan, forever — that is what lets women participate
     * fully without paying, which is what keeps the marketplace working.
     * Spec 18.2.
     */
    public function store(Request $request, string $profileId)
    {
        $user = $request->user();
        $me = $user->profile;

        $target = User::where('profile_id', $profileId)->firstOrFail()->profile;
        abort_unless($target && $target->id !== $me->id, 404);

        $quota = (int) ($user->plan()['interests_per_day'] ?? 0);

        if ($quota === 0) {
            return redirect()->route('plans')->with('paywall', $profileId);
        }

        if (! Entitlement::consume($user->id, 'interests_per_day', $quota)) {
            return back()->with('status', __('interest.quota_exhausted'));
        }

        Interest::updateOrCreate(
            ['from_profile_id' => $me->id, 'to_profile_id' => $target->id],
            ['status' => 'PENDING', 'expires_at' => now()->addDays(30),
             'message' => $request->input('message')],
        );

        return back()->with('status', __('interest.sent'));
    }

    public function respond(Request $request, Interest $interest)
    {
        $me = $request->user()->profile;
        abort_unless($interest->to_profile_id === $me->id, 403);

        $action = $request->input('action');

        if ($action === 'accept') {
            $interest->update(['status' => 'ACCEPTED', 'responded_at' => now()]);

            // Accepting grants private access BOTH ways and opens the thread.
            PrivateAccess::grantMutually($interest->to_profile_id, $interest->from_profile_id);
            MailboxThread::between($me->id, $interest->from_profile_id);

            return redirect()->route('member.mailbox')->with('status', __('interest.accepted'));
        }

        // A decline is permanent, and the sender is never told it was a
        // decline rather than an expiry. Spec 16.2.
        $interest->update([
            'status'         => 'DECLINED',
            'responded_at'   => now(),
            'decline_reason' => $request->input('reason'),
        ]);

        return back()->with('status', __('interest.declined'));
    }
}
