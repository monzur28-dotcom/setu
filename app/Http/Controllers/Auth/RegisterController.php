<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BiodataDraft;
use App\Models\Consent;
use App\Models\GeoDistrict;
use App\Models\Preference;
use App\Models\Profile;
use App\Models\ProfileCareer;
use App\Models\ProfileFamily;
use App\Models\ProfileLifestyle;
use App\Models\ProfileLocation;
use App\Models\ProfileVisibility;
use App\Models\User;
use App\Services\OtpService;
use App\Services\PhotoIntake;
use App\Services\ProfileReview;
use App\Services\SmsSender;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly SmsSender $sms,
        private readonly PhotoIntake $photos,
        private readonly ProfileReview $review,
    ) {}

    public function showStep1(Request $request)
    {
        // Pre-filled from the biodata maker, if they came that way.
        $draft = session('biodata_token')
            ? BiodataDraft::where('token', session('biodata_token'))->first()
            : null;

        return view('auth.register-1', ['prefill' => $draft?->payload ?? []]);
    }

    public function storeStep1(Request $request)
    {
        $data = $request->validate([
            'registrant_relationship' => ['required', 'in:SELF,FATHER,MOTHER,BROTHER,SISTER,RELATIVE,FRIEND,GUARDIAN'],
            'registrant_name'         => ['nullable', 'required_unless:registrant_relationship,SELF', 'string', 'max:60'],
            'candidate_name'          => ['required', 'string', 'max:60'],
            'country_code'            => ['required', 'string', 'max:5'],
            'mobile'                  => ['required', 'string', 'max:15'],
            'email'                   => ['nullable', 'email', 'max:120'],
            'password'                => ['required', 'string', 'min:8'],
            'terms'                   => ['accepted'],
        ]);

        $e164 = $data['country_code'].preg_replace('/\D/', '', ltrim($data['mobile'], '0'));

        if (User::where('mobile_hash', User::hashMobile($e164))->exists()) {
            return back()->withErrors(['mobile' => __('auth.mobile_taken')])->withInput();
        }

        session([
            'reg' => $data + ['e164' => $e164],
        ]);

        $this->otp->issue($e164);

        return redirect()->route('register.otp');
    }

    public function showOtp()
    {
        abort_unless(session('reg'), 302, '');

        return view('auth.otp', ['mobile' => session('reg.e164')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $reg = session('reg');
        abort_unless($reg, 419);

        if (! $this->otp->verify($reg['e164'], $request->input('code'))) {
            return back()->withErrors(['code' => __('auth.otp_invalid')]);
        }

        $user = DB::transaction(function () use ($reg, $request) {
            $user = new User([
                'profile_id'              => User::generateProfileId(),
                'registrant_relationship' => $reg['registrant_relationship'],
                'registrant_name'         => $reg['registrant_name'] ?? null,
                'candidate_name'          => $reg['candidate_name'],
                'email'                   => $reg['email'] ?? null,
                'password'                => $reg['password'],
                'status'                  => 'ACTIVE',
                'verification_level'      => 'PHONE',
                'mobile_verified_at'      => now(),
                'locale'                  => app()->getLocale(),
            ]);
            $user->setMobile($reg['e164']);
            $user->save();

            Consent::record($user->id, 'TERMS', $request);
            Consent::record($user->id, 'PRIVACY_POLICY', $request);

            return $user;
        });

        Auth::login($user);
        session()->forget('reg');

        return redirect()->route('register.step2');
    }

    public function showStep2()
    {
        return view('auth.register-2', ['districts' => GeoDistrict::orderBy('name_en')->get()]);
    }

    public function storeStep2(Request $request)
    {
        $data = $request->validate([
            'gender'          => ['required', 'in:MALE,FEMALE'],
            'date_of_birth'   => ['required', 'date', 'before:-18 years'],
            'height_cm'       => ['nullable', 'integer', 'min:122', 'max:241'],
            'marital_status'  => ['required', 'string'],
            'religion'        => ['required', 'string'],
            'country'         => ['required', 'string', 'size:2'],
            'city'            => ['nullable', 'string', 'max:80'],
            'district_id'     => ['nullable', 'exists:geo_districts,id'],
            'home_district_id'=> ['nullable', 'exists:geo_districts,id'],
            'profession'      => ['nullable', 'string', 'max:60'],
            'education_level' => ['nullable', 'string', 'max:40'],
            // Required. A profile without a face is the single biggest
            // driver of unanswered interests, and it is the one field a
            // member will never come back to add later.
            'photo'           => array_merge(['required'], PhotoIntake::RULES),
        ], [], ['date_of_birth' => __('auth.dob'), 'photo' => __('auth.photo')]);

        $user = $request->user();

        // Currency follows the country the member just told us they live in.
        // Guessing it from a column default would show a member in Berlin a
        // price in taka on the first screen that mentions money.
        abort_unless(Countries::exists($data['country']), 422);

        $user->forceFill(['currency' => Countries::currency($data['country'])])->save();

        $district = $data['district_id'] ?? null;

        DB::transaction(function () use ($data, $user, $request, $district) {
            $profile = Profile::updateOrCreate(['user_id' => $user->id], [
                'gender'         => $data['gender'],
                'date_of_birth'  => $data['date_of_birth'],
                'height_cm'      => $data['height_cm'] ?? null,
                'marital_status' => $data['marital_status'],
                'religion'       => $data['religion'],
                'completeness'   => 45,
            ]);

            // Privacy-protective defaults, applied before anything is visible.
            ProfileVisibility::firstOrCreate(['profile_id' => $profile->id]);
            ProfileLocation::updateOrCreate(['profile_id' => $profile->id], [
                'country'          => $data['country'],
                'city'             => $data['city'] ?? null,
                // The line below read $data['district_id'] with no default.
                // validate() omits absent nullable keys, so registering
                // without picking a district was a 500 — and districts only
                // exist for the home market, so every member outside it hit
                // it on the last step of sign-up.
                'district_id'      => $district,
                'division_id'      => $district
                    ? GeoDistrict::find($district)?->division_id : null,
                'home_district_id' => $data['home_district_id'] ?? null,
            ]);
            ProfileCareer::updateOrCreate(['profile_id' => $profile->id], [
                'profession'      => $data['profession'] ?? null,
                'education_level' => $data['education_level'] ?? null,
            ]);
            ProfileFamily::firstOrCreate(['profile_id' => $profile->id]);
            ProfileLifestyle::firstOrCreate(['profile_id' => $profile->id]);

            // Defaults deliberately BROAD — an over-constrained preference
            // produces zero matches and a churned member. Spec 14.7.
            $age = \Carbon\Carbon::parse($data['date_of_birth'])->age;
            Preference::firstOrCreate(['profile_id' => $profile->id], [
                'age_min'  => max(18, $age - ($data['gender'] === 'FEMALE' ? 2 : 8)),
                'age_max'  => $age + ($data['gender'] === 'FEMALE' ? 8 : 3),
                'religion' => [$data['religion']],
                'postures' => ['religion' => 'MUST', 'age' => 'PREFER'],
            ]);

            // Religion is special-category data under UK/EU GDPR and this
            // product cannot function without it. Consent is explicit,
            // granular, and separate from terms acceptance. Spec 24.2.
            Consent::record($user->id, 'SPECIAL_CATEGORY_RELIGION', $request);

            $this->photos->store($user, $profile, $request->file('photo'), primary: true);

            // Nothing is public yet. The profile now waits for a moderator,
            // and `scopeDiscoverable` keeps it out of every listing until
            // one has read it.
            $this->review->submitForFirstReview($profile);

            $this->sendCandidateConfirmation($user);
        });

        return redirect()->route('member.privacy')->with('status', __('auth.profile_created'));
    }

    /**
     * The soft consent gate. A relative can create a working profile
     * immediately — conversion is preserved — but the EXPOSURE that needs
     * consent waits for consent: until confirmed, the profile is visible to
     * logged-in members only, never public, never indexed, and never visible
     * to an operator. Spec 9.5.
     */
    private function sendCandidateConfirmation(User $user): void
    {
        if ($user->registrant_relationship === 'SELF') {
            return;
        }

        $token = Str::random(48);
        cache()->put('candidate_confirm:'.$token, $user->id, now()->addDays(30));

        $this->sms->send(
            $user->mobile,
            __('sms.candidate_confirm', [
                'name'  => $user->registrant_name,
                'brand' => config('app.name'),
                'link'  => route('candidate.confirm', $token),
            ]),
            critical: true,
        );
    }
}
