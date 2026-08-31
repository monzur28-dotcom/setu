<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\CaseContact;
use App\Models\CaseShortlist;
use App\Models\MatchmakerCase;
use App\Models\Profile;
use App\Models\SuccessFee;
use App\Services\ConsentGate;
use App\Services\LandingPageService;
use Illuminate\Http\Request;

/**
 * ---------------------------------------------------------------------------
 *  THE GHOTOK CONSOLE
 * ---------------------------------------------------------------------------
 *  What this console deliberately does NOT have:
 *
 *    · a search across the whole database outside an assigned case
 *    · a way to open a private profile without a recorded consent
 *    · access to any member's mailbox
 *    · bulk export
 *    · any route into Connect  (the permission does not exist to be granted)
 *
 *  Every one of those is convenient, and every one has a plausible
 *  operational justification. Their absence is the specification. Spec 13.1.
 * ---------------------------------------------------------------------------
 */
class CaseController extends Controller
{
    public function __construct(
        private readonly ConsentGate $gate,
        private readonly LandingPageService $landing,
    ) {}

    public function index(Request $request)
    {
        return view('operator.cases', [
            // Own cases only. Never another operator's.
            'cases' => MatchmakerCase::where('operator_id', $request->user()->id)
                ->with('client')->whereNull('closed_at')
                ->orderBy('updated_at')->get(),
        ]);
    }

    public function show(Request $request, MatchmakerCase $case)
    {
        $this->gate->assertOwnCase($request->user(), $case);

        return view('operator.case', [
            'case'      => $case->load('client.profile', 'contacts', 'shortlist'),
            'consents'  => $case->consents,
        ]);
    }

    /** Returns the PUBLIC field set only. Always. */
    public function search(Request $request, MatchmakerCase $case)
    {
        $this->gate->assertOwnCase($request->user(), $case);

        $results = $this->landing->query($request->only([
            'gender', 'religion', 'age_min', 'age_max', 'district_id', 'profession',
        ]))->limit(30)->get();

        $cards = $results->map(fn (Profile $p) => $this->gate->viewAsOperator(
            $request->user(), $p, $case, 'sourcing',
        ));

        return view('operator.search', compact('case', 'cards'));
    }

    public function candidate(Request $request, MatchmakerCase $case, Profile $profile)
    {
        $this->gate->assertOwnCase($request->user(), $case);

        return view('operator.candidate', [
            'case'    => $case,
            'payload' => $this->gate->viewAsOperator($request->user(), $profile, $case, 'considering'),
            'canShare'=> $this->gate->mayShareWithClient($case, $profile),
            'profile' => $profile,
        ]);
    }

    public function shortlist(Request $request, MatchmakerCase $case, Profile $profile)
    {
        $this->gate->assertOwnCase($request->user(), $case);

        CaseShortlist::firstOrCreate([
            'case_id' => $case->id, 'candidate_profile_id' => $profile->id,
        ], ['note' => $request->input('note')]);

        return back()->with('status', __('operator.shortlisted'));
    }

    /** An unlogged contact is treated as not having happened. Spec 17.5. */
    public function logContact(Request $request, MatchmakerCase $case)
    {
        $this->gate->assertOwnCase($request->user(), $case);

        $data = $request->validate([
            'party'   => ['required', 'string', 'max:40'],
            'channel' => ['required', 'in:CALL,SMS,WHATSAPP,MEETING,EMAIL'],
            'summary' => ['required', 'string', 'max:2000'],
        ]);

        CaseContact::create($data + ['case_id' => $case->id, 'operator_id' => $request->user()->id]);

        return back()->with('status', __('operator.contact_logged'));
    }

    /**
     * Recording an outcome opens a success-fee record, but the fee is not
     * raised until a SECOND person confirms it. Spec 18.5.
     */
    public function recordOutcome(Request $request, MatchmakerCase $case)
    {
        $this->gate->assertOwnCase($request->user(), $case);

        $request->validate(['outcome' => ['required', 'in:NOT_PROCEEDING,TALKING,ENGAGED,MARRIED']]);

        $case->update(['outcome' => $request->input('outcome'), 'stage' => 'OUTCOME']);

        if (in_array($request->input('outcome'), ['ENGAGED', 'MARRIED'], true)) {
            SuccessFee::firstOrCreate(['case_id' => $case->id], [
                'client_user_id' => $case->client_user_id,
                'amount'         => config('setu.plans.ghotok.success_deposit_bdt'),
                'currency'       => 'BDT',
                'structure'      => 'DEPOSIT',
                'trigger_event'  => $request->input('outcome'),
                'recorded_by'    => $request->user()->id,
                'status'         => 'DUE',
            ]);
        }

        return back()->with('status', __('operator.outcome_recorded'));
    }
}
