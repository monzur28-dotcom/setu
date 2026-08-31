<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GeoDivision;
use App\Models\HeroSlide;
use App\Models\Profile;
use App\Services\LandingPageService;
use App\Services\VisibilitySerializer;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly LandingPageService $landing,
        private readonly VisibilitySerializer $serializer,
    ) {}

    public function index(Request $request)
    {
        // Inventory proof. A visitor must be able to see real profiles before
        // giving anything — it is what converts against gated competitors.
        $recent = Profile::query()->discoverable()
            ->with(['user', 'visibility', 'location.district', 'career', 'approvedPhotos'])
            ->latest('id')->limit(8)->get()
            ->map(fn ($p) => $this->serializer->forViewer($p, $request->user()));

        return view('public.home', [
            'recent'    => $recent,
            'featured'  => $this->featured($request),
            // Marketing art, admin-managed. Falls back to nothing gracefully:
            // an empty slideshow leaves the doorway on its own background.
            'slides'    => HeroSlide::query()->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get(),
            'divisions' => GeoDivision::with('districts')->get(),
        ]);
    }

    /**
     * The hero montage: brides and grooms who chose to make their photograph
     * public, alternating gender. Only `show_photos` profiles qualify — the
     * front page must never be the one surface where a hidden photo leaks.
     */
    private function featured(Request $request)
    {
        $pick = fn (string $gender) => Profile::query()->discoverable()
            ->where('gender', $gender)
            ->whereHas('visibility', fn ($q) => $q->where('show_photos', true))
            ->whereHas('approvedPhotos', fn ($q) => $q->where('status', 'APPROVED'))
            ->with(['user', 'visibility', 'location.district', 'career', 'approvedPhotos'])
            ->latest('id')->limit(2)->get();

        $brides = $pick('FEMALE');
        $grooms = $pick('MALE');

        return collect([$brides[0] ?? null, $grooms[0] ?? null, $brides[1] ?? null, $grooms[1] ?? null])
            ->filter()
            ->map(fn ($p) => $this->serializer->forViewer($p, $request->user()))
            ->values();
    }

    /** Search runs WITHOUT a session — that is the whole model. Spec 15.1. */
    public function search(Request $request)
    {
        $filters = $request->only([
            'gender', 'religion', 'age_min', 'age_max', 'district_id',
            'division_id', 'profession', 'marital_status', 'has_photo',
        ]);

        $results = $this->landing->query($filters, $request->user()?->profile?->id)
            ->paginate(20)->withQueryString();

        $cards = $results->getCollection()
            ->map(fn ($p) => $this->serializer->forViewer($p, $request->user()));

        return view('public.search', [
            'results' => $results,
            'cards'   => $cards,
            'filters' => $filters,
        ]);
    }
}
