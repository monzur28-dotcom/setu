<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\User;
use App\Services\VisibilitySerializer;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function __construct(private readonly VisibilitySerializer $serializer) {}

    public function show(Request $request, string $profileId)
    {
        $user = User::where('profile_id', $profileId)->firstOrFail();

        // Not candidate-confirmed, not active, or blocked → a generic 404.
        // Never confirm that the profile exists. Spec 27.2 P7.
        abort_unless($user->isPubliclyVisible(), 404);

        $profile = $user->profile;
        abort_unless($profile, 404);

        $viewer = $request->user();

        // The direct URL is the ONE read path that does not run through
        // scopeDiscoverable, so the approval gate is repeated here. Without
        // this an unapproved profile is public to anyone holding its link,
        // which is exactly what pre-publication review exists to prevent.
        abort_unless(
            $profile->isPublished()
                || $viewer?->id === $user->id
                || (bool) $viewer?->isStaff(),
            404
        );

        if ($viewer?->profile) {
            $blocked = \App\Models\Block::where(function ($q) use ($viewer, $profile) {
                $q->where('blocker_profile_id', $profile->id)
                  ->where('blocked_profile_id', $viewer->profile->id);
            })->orWhere(function ($q) use ($viewer, $profile) {
                $q->where('blocker_profile_id', $viewer->profile->id)
                  ->where('blocked_profile_id', $profile->id);
            })->exists();

            abort_if($blocked, 404);

            ProfileView::create([
                'viewer_profile_id' => $viewer->profile->id,
                'viewed_profile_id' => $profile->id,
                'source'            => $request->query('from', 'direct'),
            ]);
        }

        $payload = $this->serializer->forViewer($profile, $viewer);

        return response()
            ->view('public.profile', ['p' => $payload, 'profile' => $profile])
            // Member profiles are noindex BY DEFAULT. Indexing is an explicit,
            // informed opt-in, and never available for Connect. Spec 16.3.
            ->header('X-Robots-Tag',
                $user->public_indexing === 'INDEXED' ? 'index, follow' : 'noindex, nofollow');
    }
}
