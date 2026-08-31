@extends('layouts.app')
@section('title', __('nav.messages'))
@section('noindex', true)
@section('crumb', 'Member · Messages')

@section('content')
<div class="hd"><h1>@lang('nav.messages')</h1></div>

<div class="grid" style="grid-template-columns:230px 1fr; gap:18px; align-items:start">
    <div class="card stack">
        @forelse ($threads as $t)
            @php $other = \App\Models\Profile::find($t->otherProfileId($me->id)); @endphp
            <a href="{{ route('member.mailbox.show', $t) }}" class="row g10 center"
               style="padding:11px 12px;border-bottom:1px solid var(--line);text-decoration:none;
                      background:{{ isset($thread) && $thread?->id === $t->id ? 'var(--brand-tint)' : 'transparent' }}">
                <span style="width:34px;flex:none">
                    @include('partials.photo', ['name' => $other?->displayName() ?? '?', 'blurred' => false, 'size' => 34])
                </span>
                <span class="stack">
                    <span class="sm b">{{ $other?->displayName() }}</span>
                    <span class="mono" style="font-size:10px;color:var(--muted)">{{ $other?->user->profile_id }}</span>
                </span>
            </a>
        @empty
            <div class="pad muted sm">@lang('mailbox.none')</div>
        @endforelse
    </div>

    @if (isset($thread) && $thread)
        @php $other = \App\Models\Profile::find($thread->otherProfileId($me->id)); @endphp
        <div class="card stack" style="min-height:440px">
            <div class="pad row between center" style="border-bottom:1px solid var(--line)">
                <div>
                    <div class="b sm">{{ $other?->displayName() }}</div>
                    <div class="mono xs muted">{{ $other?->user->profile_id }}</div>
                </div>
            </div>

            <div class="pad stack g10 grow" style="justify-content:flex-end">
                @foreach ($thread->messages as $m)
                    <div class="msg {{ $m->sender_profile_id === $me->id ? 'me' : 'them' }}">{{ $m->body }}</div>
                    @if ($m->is_filtered)
                        <div class="card pad xs muted" style="text-align:center;background:var(--surface-2)">
                            @lang('mailbox.filtered')
                        </div>
                    @endif
                @endforeach
            </div>

            <form method="POST" action="{{ route('member.mailbox.send', $thread) }}" class="pad row g8"
                  style="border-top:1px solid var(--line)">@csrf
                <input class="inp grow" name="body" placeholder="@lang('mailbox.write')" required>
                <button class="btn sm">@lang('mailbox.send')</button>
            </form>

            <div class="pad" style="border-top:1px solid var(--line)">
                @if ($thread->contactExchanged())
                    <div class="note brand"><span class="t">@lang('mailbox.exchanged_title')</span>@lang('mailbox.exchanged_body')</div>
                @elseif ($thread->exchange && $thread->exchange->offered_by !== $me->id)
                    <form method="POST" action="{{ route('member.contact.accept', $thread) }}">@csrf
                        <button class="btn ghost sm full">@lang('mailbox.accept_contact')</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('member.contact.offer', $thread) }}">@csrf
                        <button class="btn ghost sm full">@lang('mailbox.offer_contact')</button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div class="card pad muted">@lang('mailbox.select')</div>
    @endif
</div>
@endsection
