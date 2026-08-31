<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ContactExchange;
use App\Models\MailboxMessage;
use App\Models\MailboxThread;
use App\Services\ContactMasker;
use Illuminate\Http\Request;

class MailboxController extends Controller
{
    public function __construct(private readonly ContactMasker $masker) {}

    public function index(Request $request)
    {
        $me = $request->user()->profile;

        $threads = MailboxThread::where('profile_a_id', $me->id)
            ->orWhere('profile_b_id', $me->id)
            ->orderByDesc('last_message_at')->get();

        return view('member.mailbox', ['threads' => $threads, 'me' => $me, 'thread' => $threads->first()]);
    }

    public function show(Request $request, MailboxThread $thread)
    {
        $me = $request->user()->profile;
        abort_unless($thread->includes($me->id), 403);

        $thread->messages()->where('sender_profile_id', '!=', $me->id)
            ->whereNull('read_at')->update(['read_at' => now()]);

        $threads = MailboxThread::where('profile_a_id', $me->id)
            ->orWhere('profile_b_id', $me->id)->orderByDesc('last_message_at')->get();

        return view('member.mailbox', compact('thread', 'threads', 'me'));
    }

    public function send(Request $request, MailboxThread $thread)
    {
        $me = $request->user()->profile;
        abort_unless($thread->includes($me->id), 403);

        $request->validate(['body' => ['required', 'string', 'max:2000']]);

        // Contact patterns are masked in BOTH directions until an explicit
        // two-sided exchange has completed. This protects the owner, not the
        // paywall: it applies identically on every plan.
        [$body, $filtered, $reason] = $thread->contactExchanged()
            ? [$request->input('body'), false, null]
            : $this->masker->mask($request->input('body'));

        $flags = $this->masker->riskFlags($request->input('body'));

        MailboxMessage::create([
            'thread_id'         => $thread->id,
            'sender_profile_id' => $me->id,
            'body'              => $body,
            'is_filtered'       => $filtered,
            'filter_reason'     => $reason,
        ]);

        $thread->update(['last_message_at' => now()]);

        if (in_array('MONEY_REQUEST', $flags, true)) {
            \App\Models\ModerationItem::create([
                'entity_type' => 'REPORT', 'entity_id' => $thread->id,
                'mode' => 'MATRIMONIAL', 'priority' => 1,
            ]);
        }

        return back();
    }

    /**
     * Contact is exchanged, never sold. One member offers, the other must
     * accept, and only then is either number revealed. No plan, coupon,
     * admin action or support request substitutes for this. Spec 27.2 P4.
     */
    public function offerContact(Request $request, MailboxThread $thread)
    {
        $me = $request->user()->profile;
        abort_unless($thread->includes($me->id), 403);

        ContactExchange::updateOrCreate(
            ['thread_id' => $thread->id],
            ['offered_by' => $me->id],
        );

        return back()->with('status', __('mailbox.contact_offered'));
    }

    public function acceptContact(Request $request, MailboxThread $thread)
    {
        $me = $request->user()->profile;
        abort_unless($thread->includes($me->id), 403);

        $exchange = $thread->exchange;
        abort_unless($exchange && $exchange->offered_by !== $me->id, 403);

        $exchange->update(['accepted_at' => now()]);

        return back()->with('status', __('mailbox.contact_exchanged'));
    }
}
