<?php

namespace App\Http\Controllers\Connect;

use App\Http\Controllers\Controller;
use App\Models\ConnectDeck;
use App\Models\ConnectLike;
use App\Models\ConnectMatch;
use App\Models\ConnectPreference;
use App\Models\ConnectProfile;
use App\Models\ConnectPrompt;
use App\Models\Consent;
use App\Services\ConnectWall;
use App\Services\DeckGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ===========================================================================
 *  CONNECT
 * ===========================================================================
 *  The second product. Everything here lives under /connect, is never public,
 *  never indexed, and is invisible to guardians and operators.
 *
 *  Three rules in this file are marked [SAFETY-CRITICAL]. They are not
 *  tunable for commercial reasons, and if engagement metrics later argue for
 *  relaxing one, that argument is wrong. Spec 4.5.
 * ===========================================================================
 */
class ConnectController extends Controller
{
    public function __construct(private readonly DeckGenerator $decks) {}

    public function start(Request $request)
    {
        return view('connect.start', ['user' => $request->user()]);
    }

    /**
     * Opting in is a deliberate, separate act — and it requires a HIGHER
     * verification bar than the marriage side: 18+, phone verified, AND a
     * selfie liveness check, because catfishing is the primary complaint on
     * every competitor. Spec 27.3 S7.
     */
    public function enable(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        abort_unless($profile, 403, __('connect.need_profile'));
        abort_if($profile->age < 18, 403, __('connect.age'));

        $selfie = $user->verifications()
            ->where('type', 'SELFIE')->where('status', 'APPROVED')->exists();

        if (! $selfie && ! app()->environment('local')) {
            return redirect()->route('member.verification')
                ->with('status', __('connect.selfie_required'));
        }

        DB::transaction(function () use ($user, $profile, $request) {
            $user->update(['dating_enabled' => true]);

            $cp = ConnectProfile::firstOrCreate(['user_id' => $user->id], [
                'connect_id'   => ConnectProfile::generateConnectId(),
                'display_name' => explode(' ', trim($user->candidate_name))[0],
                'age'          => $profile->age,
                'city'         => $profile->location?->city ?? 'Dhaka',
                'intentions'   => 'SERIOUS_RELATIONSHIP',
            ]);

            ConnectPreference::firstOrCreate(['connect_profile_id' => $cp->id]);

            Consent::record($user->id, 'CONNECT_PARTICIPATION', $request);
        });

        return redirect()->route('connect.profile.edit')->with('status', __('connect.enabled'));
    }

    public function editProfile(Request $request)
    {
        $cp = $request->user()->connectProfile;

        return view('connect.profile', [
            'cp'      => $cp,
            'prompts' => $cp?->prompts ?? collect(),
            // W9: a non-blocking warning, because reverse image search is how
            // two identities get linked and only the member can weigh that.
            'reuseWarning' => false,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $cp = $request->user()->connectProfile;

        $data = $request->validate([
            'display_name'      => ['required', 'string', 'max:30'],
            'city'              => ['required', 'string', 'max:60'],
            'bio'               => ['nullable', 'string', 'max:300'],
            'intentions'        => ['required', 'in:MARRIAGE_WITHIN_YEAR,SERIOUS_RELATIONSHIP,GETTING_TO_KNOW'],
            'faith_practice'    => ['nullable', 'string', 'max:40'],
            'education_coarse'  => ['nullable', 'string', 'max:40'],
            'profession_coarse' => ['nullable', 'string', 'max:40'],
            'photo_visibility'  => ['nullable', 'in:BLURRED_UNTIL_MATCH,VISIBLE_TO_SUGGESTIONS'],
        ]);

        $cp->update($data);

        foreach ($request->input('prompts', []) as $key => $answer) {
            if (filled($answer)) {
                ConnectPrompt::updateOrCreate(
                    ['connect_profile_id' => $cp->id, 'question_key' => $key],
                    ['answer' => $answer],
                );
            }
        }

        return back()->with('status', __('connect.saved'));
    }

    /** A deck, not a directory. There is no search endpoint here, by design. */
    public function deck(Request $request)
    {
        $cp = $request->user()->connectProfile;

        $card = ConnectDeck::where('connect_profile_id', $cp->id)
            ->where('for_date', today())->whereNull('seen_at')
            ->orderByDesc('score')->first();

        if (! $card) {
            $this->decks->buildFor($cp);

            $card = ConnectDeck::where('connect_profile_id', $cp->id)
                ->where('for_date', today())->whereNull('seen_at')
                ->orderByDesc('score')->first();
        }

        $candidate = $card ? ConnectProfile::with('prompts')->find($card->candidate_connect_id) : null;

        $plan = $request->user()->plan('CONNECT');
        $used = ConnectLike::where('from_connect_id', $cp->id)
            ->whereDate('created_at', today())->count();

        return view('connect.deck', [
            'cp'        => $cp,
            'card'      => $card,
            'candidate' => $candidate,
            'remaining' => $plan['likes_per_day'] === null ? null : max(0, $plan['likes_per_day'] - $used),
        ]);
    }

    /**
     * [SAFETY-CRITICAL] A like is recorded silently. The recipient is NOT
     * told — not by a notification, not by a count that would identify them,
     * not by any channel — unless it becomes mutual. Only a mutual choice
     * produces a signal, in either direction. Spec 27.3 S2.
     */
    public function act(Request $request, ConnectProfile $candidate)
    {
        $cp = $request->user()->connectProfile;
        $action = $request->input('action') === 'like' ? 'LIKE' : 'PASS';

        $plan = $request->user()->plan('CONNECT');

        if ($action === 'LIKE' && $plan['likes_per_day'] !== null) {
            $used = ConnectLike::where('from_connect_id', $cp->id)
                ->where('action', 'LIKE')->whereDate('created_at', today())->count();

            if ($used >= $plan['likes_per_day']) {
                return redirect()->route('connect.plans')->with('status', __('connect.likes_used'));
            }
        }

        $mutual = ConnectLike::record($cp->id, $candidate->id, $action);

        ConnectDeck::where('connect_profile_id', $cp->id)
            ->where('candidate_connect_id', $candidate->id)
            ->update(['seen_at' => now()]);

        return $mutual
            ? redirect()->route('connect.matches')->with('status', __('connect.matched'))
            : back()->with('status', $action === 'LIKE' ? __('connect.liked') : __('connect.passed'));
    }

    public function matches(Request $request)
    {
        $cp = $request->user()->connectProfile;

        $matches = ConnectMatch::where('status', 'ACTIVE')
            ->where(fn ($q) => $q->where('a_connect_id', $cp->id)->orWhere('b_connect_id', $cp->id))
            ->get()
            ->map(fn ($m) => ['match' => $m, 'other' => ConnectProfile::find($m->otherId($cp->id))]);

        // Paid members see WHO liked them; free members see only a count, and
        // never anything that would identify a person who has not matched.
        $likerCount = ConnectLike::where('to_connect_id', $cp->id)->where('action', 'LIKE')
            ->whereNotIn('from_connect_id', $matches->pluck('other.id')->filter())
            ->count();

        return view('connect.matches', [
            'cp'         => $cp,
            'matches'    => $matches,
            'likerCount' => $likerCount,
            'seeLikers'  => (bool) ($request->user()->plan('CONNECT')['see_likers'] ?? false),
        ]);
    }

    public function chat(Request $request, ConnectMatch $match)
    {
        $cp = $request->user()->connectProfile;
        abort_unless($match->includes($cp->id) && $match->status === 'ACTIVE', 404);

        return view('connect.chat', [
            'cp'    => $cp,
            'match' => $match->load('messages'),
            'other' => ConnectProfile::find($match->otherId($cp->id)),
        ]);
    }

    public function sendMessage(Request $request, ConnectMatch $match, \App\Services\ContactMasker $masker)
    {
        $cp = $request->user()->connectProfile;
        abort_unless($match->includes($cp->id) && $match->status === 'ACTIVE', 404);

        $request->validate(['body' => ['required', 'string', 'max:2000']]);

        [$body, $filtered, $reason] = $masker->mask($request->input('body'));

        $match->messages()->create([
            'sender_connect_id' => $cp->id,
            'body'              => $body,
            'is_filtered'       => $filtered,
            'filter_reason'     => $reason,
        ]);

        return back();
    }

    public function unmatch(Request $request, ConnectMatch $match)
    {
        $cp = $request->user()->connectProfile;
        abort_unless($match->includes($cp->id), 404);

        // Immediate, mutual, silent, permanent.
        $match->update(['status' => 'UNMATCHED', 'closed_by' => $cp->id]);

        return redirect()->route('connect.deck')->with('status', __('connect.unmatched'));
    }

    /**
     * [SAFETY-CRITICAL] Block is absolute and silent. It removes both parties
     * from each other's existence — deck, matches, messages, notifications,
     * any API response — and the blocked person is never told. Spec 27.3 S4.
     */
    public function block(Request $request, ConnectProfile $candidate)
    {
        $cp = $request->user()->connectProfile;

        DB::transaction(function () use ($cp, $candidate) {
            \App\Models\ConnectBlock::firstOrCreate([
                'blocker_connect_id' => $cp->id,
                'blocked_connect_id' => $candidate->id,
            ]);

            ConnectMatch::between($cp->id, $candidate->id)?->update([
                'status' => 'UNMATCHED', 'closed_by' => $cp->id,
            ]);

            ConnectDeck::where('connect_profile_id', $cp->id)
                ->where('candidate_connect_id', $candidate->id)->delete();
        });

        return redirect()->route('connect.deck')->with('status', __('connect.blocked'));
    }

    public function settings(Request $request)
    {
        return view('connect.settings', ['cp' => $request->user()->connectProfile]);
    }

    public function plans()
    {
        return view('connect.plans');
    }

    /**
     * Deleting Connect leaves the marriage account completely intact.
     * One tap, no reason required — someone who feels trapped in a product
     * they want to leave will not trust the platform with anything else.
     * Spec 27.1 W10.
     */
    public function destroy(Request $request)
    {
        ConnectWall::deleteConnectOnly($request->user());

        return redirect()->route('member.dashboard')->with('status', __('connect.deleted'));
    }
}
