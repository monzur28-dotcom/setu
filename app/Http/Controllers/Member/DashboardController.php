<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AccessRequest;
use App\Models\Interest;
use App\Models\MailboxThread;
use App\Models\ProfileView;
use App\Services\ProfileReview;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ProfileReview $review)
    {
        $user = $request->user();
        $profile = $user->profile;

        // The `member` middleware guarantees a profile before this runs.
        // The line that used to be here called abort() with a redirect's
        // status code and no Location header, which is a 302 to nowhere.

        $threadIds = MailboxThread::where('profile_a_id', $profile->id)
            ->orWhere('profile_b_id', $profile->id)->pluck('id');

        return view('member.dashboard', [
            'profile'   => $profile,
            'hasPendingEdit' => $review->hasPendingEdit($profile),
            'interests' => Interest::where('to_profile_id', $profile->id)
                ->where('status', 'PENDING')->with('from.user')->latest()->get(),
            'requests'  => AccessRequest::where('to_profile_id', $profile->id)
                ->where('status', 'PENDING')->with('from.user')->get(),
            'unread'    => \App\Models\MailboxMessage::whereIn('thread_id', $threadIds)
                ->where('sender_profile_id', '!=', $profile->id)
                ->whereNull('read_at')->count(),
            // Free members see the COUNT of viewers, paid members see who.
            'viewers'   => ProfileView::where('viewed_profile_id', $profile->id)
                ->where('created_at', '>', now()->subDays(30))->count(),
            'canSeeViewers' => $user->can_('see_viewers'),
            'guardians' => $user->guardianLinks()->with('guardian')->get(),
            'case'      => $user->matchmakerCase()->with('operator')->first(),
        ]);
    }
}
