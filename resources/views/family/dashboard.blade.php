@extends('layouts.app')
@section('title', __('family.dashboard'))
@section('noindex', true)
@section('crumb', 'Family · Guardian view')

@section('content')
<div class="hd">
    <span class="lbl">@lang('family.guardian_view')</span>
    <h1>@lang('family.progress_of', ['name' => $payload['candidate_name']])</h1>
    <p class="sub">@lang('family.level_note', ['level' => __('enum.level.'.$link->visibility_level)])</p>
</div>

<div class="grid g4c">
    @foreach ([
        [__('family.stat_profile'), __('enum.verification.'.$payload['verified'])],
        [__('family.stat_interests'), $payload['counts']['interests_received']],
        [__('family.stat_connections'), $payload['counts']['connections']],
        [__('family.stat_active'), $payload['last_active'] ?? '—'],
    ] as [$a, $b])
        <div class="card pad stack g4">
            <span class="lbl">{{ $a }}</span>
            <span class="serif" style="font-size:22px;color:var(--ink)">{{ $b }}</span>
        </div>
    @endforeach
</div>

<div class="grid g2 sec" style="align-items:start">
    <div class="card pad stack g10">
        <span class="lbl">@lang('family.who_introduced')</span>
        @if ($link->may('see_connected'))
            <a class="btn sm ghost" href="{{ route('family.introductions') }}">@lang('common.view')</a>
        @else
            <div class="card pad" style="border-style:dashed;text-align:center;color:var(--muted);font-size:13px">
                @lang('family.hidden_at_level')
            </div>
        @endif

        {{-- Not a permission check with a branch — there is no code path in
             this application that puts message content on this page. G4. --}}
        <div class="card pad" style="border-style:dashed;text-align:center;color:var(--muted);font-size:13px">
            @lang('family.messages_never')
        </div>
    </div>

    <form method="POST" action="{{ route('family.note') }}" class="card pad stack g10">@csrf
        <span class="lbl">@lang('family.your_notes')</span>
        <textarea class="inp" name="body" rows="5" placeholder="@lang('family.notes_placeholder')"></textarea>
        <div class="hint">@lang('family.notes_private')</div>
        <button class="btn sm">@lang('common.save')</button>
    </form>
</div>

@if ($notes->isNotEmpty())
    <div class="sec">
        <span class="lbl">@lang('family.saved_notes')</span>
        <div class="stack g8">
            @foreach ($notes as $n)
                <div class="card pad sm">{{ $n->body }}<div class="xs muted" style="margin-top:4px">{{ $n->created_at->diffForHumans() }}</div></div>
            @endforeach
        </div>
    </div>
@endif

@if ($link->may('contact_other_family'))
    <form method="POST" action="{{ route('family.contact') }}" class="sec">@csrf
        <button class="btn ghost">@lang('family.contact_other_family')</button>
        <div class="hint" style="margin-top:6px">@lang('family.four_consents')</div>
    </form>
@endif

<div class="note sec">
    <span class="t">@lang('family.transparency_title')</span>
    @lang('family.transparency_body')
</div>
@endsection
