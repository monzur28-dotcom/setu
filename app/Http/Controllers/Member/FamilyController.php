<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\GuardianLink;
use App\Models\User;
use App\Services\SmsSender;
use Illuminate\Http\Request;

/**
 * The candidate's side of the family relationship. Note what this controller
 * can do that the guardian's cannot: set the level, and end the link.
 * Spec 12.2 G2/G7.
 */
class FamilyController extends Controller
{
    public function __construct(private readonly SmsSender $sms) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return view('member.family', [
            'links' => $user->guardianLinks()->with('guardian')->get(),
            'log'   => \App\Models\GuardianAccessLog::whereIn(
                'guardian_link_id', $user->guardianLinks()->pluck('id')
            )->latest('created_at')->limit(50)->get(),
        ]);
    }

    public function invite(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:60'],
            'mobile'       => ['required', 'string', 'max:20'],
            'relationship' => ['required', 'in:FATHER,MOTHER,BROTHER,SISTER,AUNT,UNCLE,COUSIN,LEGAL_GUARDIAN'],
            'level'        => ['required', 'in:L1_PROGRESS,L2_INTRODUCTIONS,L3_FULL'],
        ]);

        $digits = preg_replace('/\D/', '', $data['mobile']);
        $e164 = str_starts_with($digits, '88') ? '+'.$digits : '+88'.ltrim($digits, '0');

        $guardian = User::where('mobile_hash', User::hashMobile($e164))->first();

        if (! $guardian) {
            $guardian = new User([
                'profile_id'     => User::generateProfileId(),
                'candidate_name' => $data['name'],
                'role'           => 'GUARDIAN',
                'status'         => 'UNVERIFIED',
                'password'       => str()->random(32),
                'locale'         => app()->getLocale(),
            ]);
            $guardian->setMobile($e164);
            $guardian->save();
        }

        $link = GuardianLink::updateOrCreate([
            'guardian_user_id' => $guardian->id,
            'candidate_user_id'=> $request->user()->id,
        ], [
            'relationship'     => $data['relationship'],
            'visibility_level' => $data['level'],
            'link_status'      => 'INVITED',
            'invite_token'     => str()->random(48),
        ]);

        Consent::record($request->user()->id, 'GUARDIAN_VISIBILITY', $request, [
            'evidence' => ['level' => $data['level'], 'guardian' => $guardian->profile_id],
        ]);

        $this->sms->send($e164, __('sms.guardian_invite', [
            'name'  => $request->user()->candidate_name,
            'brand' => config('app.name'),
            'link'  => route('guardian.accept', $link->invite_token),
        ]), critical: true);

        return back()->with('status', __('family.invited'));
    }

    /** Only the candidate may change the level. Spec 12.2 G2. */
    public function setLevel(Request $request, GuardianLink $link)
    {
        abort_unless($link->candidate_user_id === $request->user()->id, 403);

        $request->validate(['level' => ['required', 'in:L1_PROGRESS,L2_INTRODUCTIONS,L3_FULL']]);

        $link->update(['visibility_level' => $request->input('level')]);
        AuditLog::write($request->user(), 'guardian_level_changed', [
            'entity_type' => 'guardian_link', 'entity_id' => $link->id,
            'after' => ['level' => $request->input('level')],
        ]);

        return back()->with('status', __('family.level_changed'));
    }

    /**
     * Immediate, two-tap, and no reason required. A revocation flow that
     * asks "why?" is one people avoid. The guardian is told only
     * "access ended". Spec 12.2 G7/G8.
     */
    public function revoke(Request $request, GuardianLink $link)
    {
        abort_unless($link->candidate_user_id === $request->user()->id, 403);

        $link->update(['link_status' => 'REVOKED', 'revoked_at' => now()]);
        Consent::revoke($request->user()->id, 'GUARDIAN_VISIBILITY');

        return back()->with('status', __('family.revoked'));
    }
}
