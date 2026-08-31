<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\LandingPage;
use App\Models\Plan;
use App\Models\SuccessStory;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function plans()
    {
        return view('public.plans', [
            'plans' => Plan::where('product', 'MATRIMONIAL')->where('is_active', true)
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function safety()  { return view('public.safety'); }
    public function about()   { return view('public.about'); }
    public function faq()     { return view('public.faq'); }

    public function stories()
    {
        return view('public.stories', [
            'stories' => SuccessStory::where('status', 'PUBLISHED')->latest()->paginate(12),
        ]);
    }

    public function guide(string $slug)
    {
        return view('public.guide', ['guide' => Guide::where('slug', $slug)->where('published', true)->firstOrFail()]);
    }

    /**
     * Sitemap. Connect is excluded — not filtered out downstream, simply
     * never gathered. Wall rule W3.
     */
    public function sitemap(): Response
    {
        $urls = collect([url('/'), route('plans'), route('classifieds.index'), route('biodata.create')]);

        LandingPage::all()->filter->shouldIndex()
            ->each(fn ($p) => $urls->push(url('/'.$p->slug)));

        Guide::where('published', true)->each(fn ($g) => $urls->push(route('guide', $g->slug)));

        $xml = view('public.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
