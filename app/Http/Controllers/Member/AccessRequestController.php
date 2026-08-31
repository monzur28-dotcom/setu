<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AccessRequest;
use App\Models\PrivateAccess;
use App\Models\User;
use Illuminate\Http\Request;

class AccessRequestController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user()->profile;

        return view('member.access-requests', [
            'requests' => AccessRequest::where('to_profile_id', $me->id)
                ->where('status', 'PENDING')->with('from.user')->get(),
            // Photo grants the member has already made, so they can take one
            // back without hunting through a history.
            'photoGrants' => AccessRequest::where('to_profile_id', $me->id)
                ->where('type', 'PHOTOS')->where('status', 'GRANTED')->with('from.user')->get(),
        ]);
    }

    public function store(Request $request, string $profileId)
    {
        $user = $request->user();
        $type = $request->input('type') === 'PHOTOS' ? 'PHOTOS' : 'PRIVATE_PROFILE';

        // Asking to see a hidden photograph is NOT a paid feature. What
        // opens the photo is the owner saying yes, and a plan must never be
        // able to stand in for that — nor may a paywall stop someone from
        // asking. Only the private-profile request is metered.
        if ($type === 'PRIVATE_PROFILE' && ! $user->can_('can_request_private')) {
            return redirect()->route('plans')->with('paywall', $profileId);
        }

        $target = User::where('profile_id', $profileId)->firstOrFail()->profile;

        abort_if($target->id === $user->profile?->id, 403);

        AccessRequest::updateOrCreate([
            'from_profile_id' => $user->profile->id,
            'to_profile_id'   => $target->id,
            'type'            => $type,
        ], ['status' => 'PENDING', 'responded_at' => null]);

        return back()->with('status', __('access.requested'));
    }

    public function respond(Request $request, AccessRequest $accessRequest)
    {
        $me = $request->user()->profile;
        abort_unless($accessRequest->to_profile_id === $me->id, 403);

        if ($request->input('action') === 'grant') {
            $accessRequest->update(['status' => 'GRANTED', 'responded_at' => now()]);

            if ($accessRequest->type === 'PRIVATE_PROFILE') {
                // Mutual. One action, both directions. Spec 16.1.
                PrivateAccess::grantMutually($me->id, $accessRequest->from_profile_id);
            }

            // A PHOTOS grant needs no second record: the granted row IS the
            // permission, and it is one-directional and revocable by
            // declining later. The owner opened their photo for one person.

            return back()->with('status', __('access.granted'));
        }

        $accessRequest->update(['status' => 'DECLINED', 'responded_at' => now()]);

        return back()->with('status', __('access.declined'));
    }
}
