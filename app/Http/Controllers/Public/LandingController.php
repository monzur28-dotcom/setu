<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Services\LandingPageService;
use App\Services\VisibilitySerializer;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function __construct(
        private readonly LandingPageService $landing,
        private readonly VisibilitySerializer $serializer,
    ) {}

    public function show(Request $request, string $slug)
    {
        $page = LandingPage::where('slug', $slug)
            ->where('locale', app()->getLocale())
            ->firstOr(fn () => LandingPage::where('slug', $slug)->firstOrFail());

        $count = $this->landing->count($page);

        $profiles = $this->landing->query($page->filter_json, $request->user()?->profile?->id)
            ->limit(24)->get()
            ->map(fn ($p) => $this->serializer->forViewer($p, $request->user()));

        return response()
            ->view('public.landing', compact('page', 'count', 'profiles'))
            // Fewer than 8 matching profiles and the page noindexes itself.
            // This is what keeps a page network from becoming doorway spam.
            ->header('X-Robots-Tag', $page->shouldIndex() ? 'index, follow' : 'noindex, follow');
    }
}
