<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\GeoDistrict;
use App\Models\SavedSearch;
use App\Services\LandingPageService;
use App\Services\MatchScore;
use App\Services\VisibilitySerializer;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly LandingPageService $landing,
        private readonly VisibilitySerializer $serializer,
        private readonly MatchScore $score,
    ) {}

    public function index(Request $request)
    {
        $me = $request->user()->profile;

        $filters = $request->only([
            'gender', 'religion', 'sect', 'prayer_habit', 'age_min', 'age_max',
            'district_id', 'division_id', 'home_district_id', 'profession',
            'education_level', 'marital_status', 'has_children', 'employed_in',
            'relocation_intent', 'verification', 'last_active', 'has_photo', 'country',
        ]);

        // Default to the opposite gender unless explicitly overridden.
        $filters['gender'] ??= $me->gender === 'MALE' ? 'FEMALE' : 'MALE';

        $query = $this->landing->query($filters, $me->id)->notHiddenFrom($request->user());

        if (! empty($filters['verification']) && $filters['verification'] === 'NID') {
            $query->whereHas('user', fn ($u) => $u->whereIn('verification_level', ['NID', 'NID_SELFIE']));
        }

        if (! empty($filters['last_active'])) {
            $days = (int) $filters['last_active'];
            $query->whereHas('user', fn ($u) => $u->where('last_active_at', '>', now()->subDays($days)));
        }

        $results = $query->paginate(20)->withQueryString();

        $cards = $results->getCollection()->map(function ($p) use ($me, $request) {
            $payload = $this->serializer->forViewer($p, $request->user());
            $score = $this->score->between($me, $p);
            // Never display a score below 40 — low numbers read as insults.
            $payload['score'] = $score >= 40 ? $score : null;

            return $payload;
        });

        return view('member.search', [
            'results'   => $results,
            'cards'     => $cards,
            'filters'   => $filters,
            'districts' => GeoDistrict::orderBy('name_en')->get(),
            'saved'     => SavedSearch::where('profile_id', $me->id)->get(),
        ]);
    }

    /** Saved searches with alerts are FREE on every plan. Spec 8.4. */
    public function save(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:80']]);

        SavedSearch::create([
            'profile_id'      => $request->user()->profile->id,
            'name'            => $request->input('name'),
            'filters'         => $request->except(['_token', 'name']),
            'alert_frequency' => 'WEEKLY',
        ]);

        return back()->with('status', __('search.saved'));
    }
}
