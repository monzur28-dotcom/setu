<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\GeoDistrict;
use App\Models\Photo;
use App\Services\PhotoIntake;
use App\Services\ProfileReview;
use App\Services\VisibilitySerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileEditController extends Controller
{
    public function __construct(
        private readonly VisibilitySerializer $serializer,
        private readonly ProfileReview $review,
        private readonly PhotoIntake $photos,
    ) {}

    public function edit(Request $request, string $tab = 'basic')
    {
        $profile = $request->user()->profile;

        return view('member.profile-edit', [
            'tab'       => $tab,
            'profile'   => $profile,
            'districts' => GeoDistrict::orderBy('name_en')->get(),
        ]);
    }

    public function update(Request $request, string $tab)
    {
        $profile = $request->user()->profile;

        match ($tab) {
            'basic' => $this->updateBasic($request, $profile),
            'location' => $profile->location->update($request->validate([
                'country' => ['nullable', 'string', 'max:2'], 'city' => ['nullable', 'string', 'max:80'],
                'area' => ['nullable', 'string', 'max:80'], 'district_id' => ['nullable', 'exists:geo_districts,id'],
                'home_district_id' => ['nullable', 'exists:geo_districts,id'],
                'residency_status' => ['nullable', 'string'], 'relocation_intent' => ['nullable', 'string'],
            ])),
            'career' => $profile->career->update($request->validate([
                'education_level' => ['nullable', 'string', 'max:40'],
                'education_detail' => ['nullable', 'string', 'max:120'],
                'institution' => ['nullable', 'string', 'max:120'],
                'profession' => ['nullable', 'string', 'max:60'],
                'job_title' => ['nullable', 'string', 'max:120'],
                'employer' => ['nullable', 'string', 'max:120'],
                'employed_in' => ['nullable', 'string'], 'income_band' => ['nullable', 'string', 'max:30'],
            ])),
            'family' => $this->updateFamily($request, $profile),
            'lifestyle' => $profile->lifestyle->update($request->validate([
                'diet' => ['nullable', 'string'], 'smoking' => ['nullable', 'string'],
                'drinking' => ['nullable', 'string'],
            ])),
            default => abort(404),
        };

        $this->recomputeCompleteness($profile);

        return back()->with('status', __('profile.saved'));
    }

    /**
     * The basic tab mixes enumerations with prose. The enumerations save
     * straight through — there is nothing in a height to moderate — while
     * the headline and the about-me go to ProfileReview, which holds them
     * beside the approved copy until a moderator reads them. The member's
     * live profile does not go dark because they rewrote a sentence.
     */
    private function updateBasic(Request $request, $profile): void
    {
        $data = $request->validate([
            'height_cm'         => ['nullable', 'integer', 'min:122', 'max:241'],
            'marital_status'    => ['nullable', 'string'],
            'has_children'      => ['nullable', 'string'],
            'complexion'        => ['nullable', 'string'],
            'body_type'         => ['nullable', 'string'],
            'physical_status'   => ['nullable', 'string'],
            'prayer_habit'      => ['nullable', 'string'],
            'mother_tongue'     => ['nullable', 'string'],
            'headline'          => ['nullable', 'string', 'max:100'],
            'about_me'          => ['nullable', 'string', 'max:2000'],
            'marriage_timeline' => ['nullable', 'string'],
        ]);

        $held = array_intersect_key($data, ProfileReview::HELD['profile']);

        $profile->update(array_diff_key($data, ProfileReview::HELD['profile']));

        $this->review->submitEdit($profile, 'profile', $held);
    }

    /** Same split as the basic tab: enumerations through, prose held. */
    private function updateFamily(Request $request, $profile): void
    {
        $data = $request->validate([
            'father_occupation' => ['nullable', 'string', 'max:80'],
            'mother_occupation' => ['nullable', 'string', 'max:80'],
            'family_type' => ['nullable', 'string'], 'family_status' => ['nullable', 'string'],
            'family_values' => ['nullable', 'string'], 'family_involvement' => ['nullable', 'string'],
            'about_family' => ['nullable', 'string', 'max:1000'],
        ]);

        $held = array_intersect_key($data, ProfileReview::HELD['family']);

        $profile->family->update(array_diff_key($data, ProfileReview::HELD['family']));

        $this->review->submitEdit($profile, 'family', $held);
    }

    /**
     * "See what an introduction shows" — the single best tool for getting
     * members to improve their own profile. Returns the EXACT serialized
     * payload another member would receive. Spec 22.3.
     */
    public function preview(Request $request)
    {
        $profile = $request->user()->profile;

        return view('member.profile-preview', [
            'public'  => $this->serializer->forViewer($profile, null),
            'private' => $this->serializer->forViewer($profile, $request->user()),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate(['photo' => array_merge(['required'], PhotoIntake::RULES)]);

        $this->photos->store(
            $request->user(),
            $request->user()->profile,
            $request->file('photo'),
        );

        return back()->with('status', __('profile.photo_pending'));
    }

    public function deletePhoto(Request $request, Photo $photo)
    {
        abort_unless($photo->profile_id === $request->user()->profile->id, 403);

        Storage::disk('photos')->delete([$photo->path, $photo->blur_path]);
        $photo->delete();

        return back()->with('status', __('profile.photo_deleted'));
    }

    private function recomputeCompleteness($profile): void
    {
        $checks = [
            (bool) $profile->about_me,
            (bool) $profile->height_cm,
            (bool) $profile->career?->profession,
            (bool) $profile->career?->education_level,
            (bool) $profile->location?->district_id,
            (bool) $profile->location?->home_district_id,
            (bool) $profile->family?->family_type,
            (bool) $profile->lifestyle?->diet,
            $profile->photos()->where('status', 'APPROVED')->exists(),
            (bool) $profile->preference,
        ];

        $profile->update(['completeness' => (int) round(100 * count(array_filter($checks)) / count($checks))]);
    }
}
