<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ClassifiedAd;
use App\Models\GeoDistrict;
use App\Models\ModerationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassifiedController extends Controller
{
    public function index(Request $request)
    {
        $ads = ClassifiedAd::live()
            ->when($request->query('looking_for'), fn ($q, $v) => $q->where('looking_for', $v))
            ->when($request->query('district_id'), fn ($q, $v) => $q->where('district_id', $v))
            ->with('district')
            ->latest()
            ->paginate(20)->withQueryString();

        return view('public.classifieds', compact('ads'));
    }

    public function show(string $slug)
    {
        $ad = ClassifiedAd::live()->where('slug', $slug)->with('district')->firstOrFail();

        return view('public.classified-show', compact('ad'));
    }

    public function create()
    {
        return view('public.classified-create', ['districts' => GeoDistrict::orderBy('name_en')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'looking_for'    => ['required', 'in:BRIDE,GROOM'],
            'headline'       => ['required', 'string', 'max:120'],
            'body'           => ['required', 'string', 'max:1200'],
            'age'            => ['nullable', 'integer', 'min:18', 'max:80'],
            'education'      => ['nullable', 'string', 'max:40'],
            'profession'     => ['nullable', 'string', 'max:60'],
            'religion'       => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'district_id'    => ['nullable', 'exists:geo_districts,id'],
            'contact_phone'  => ['required', 'string', 'max:20'],
            'no_media_flag'  => ['nullable', 'boolean'],
        ]);

        // One live ad per verified phone number.
        $existing = ClassifiedAd::live()
            ->where('poster_user_id', $request->user()->id)->count();

        if ($existing >= config('setu.classifieds.ads_per_number')) {
            return back()->withErrors(['headline' => __('ads.one_live_ad')]);
        }

        $ad = ClassifiedAd::create($data + [
            'slug'           => Str::slug(Str::limit($data['headline'], 40, '')).'-'.Str::random(6),
            'poster_user_id' => $request->user()->id,
            'status'         => 'PENDING',
            'expires_at'     => now()->addDays(config('setu.classifieds.live_days')),
        ]);

        // Ads are public and indexed, so a bad one is a public liability:
        // moderated BEFORE publication, and screened for dowry language,
        // which is illegal. Spec 17.2.
        ModerationItem::create([
            'entity_type' => 'AD',
            'entity_id'   => $ad->id,
            'mode'        => 'MATRIMONIAL',
            'priority'    => 4,
        ]);

        return redirect()->route('classifieds.index')->with('status', __('ads.submitted'));
    }
}
