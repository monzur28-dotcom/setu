@extends('layouts.app')
@section('title', __('operator.case'))
@section('noindex', true)
@section('content')
<div class="hd">
    <h1>@lang('operator.case') · <span class="mono">{{ $case->client->profile_id }}</span></h1>
    <p class="sub">@lang('enum.case_stage.'.$case->stage) · {{ $case->daysOpen() }} @lang('common.days')</p>
</div>

<div class="grid g2" style="align-items:start">
    <div class="card pad stack g10">
        <span class="lbl">@lang('operator.brief')</span>
        <p class="sm">{{ $case->brief ?: __('operator.no_brief') }}</p>
        <a class="btn sm ghost" href="{{ route('operator.search', $case) }}">@lang('operator.source_candidates')</a>
    </div>

    <div class="card pad stack g10">
        <span class="lbl">@lang('operator.consent')</span>
        @forelse ($consents as $c)
            <div class="row between center sm">
                <span>@lang('enum.consent_scope.'.$c->scope)</span>
                <span class="chip {{ $c->isLive() ? 'ok' : 'bad' }}">
                    {{ $c->isLive() ? ($c->expires_at?->toDateString() ?? __('common.active')) : __('operator.expired') }}
                </span>
            </div>
        @empty
            <div class="note bad">@lang('operator.no_consent_body')</div>
        @endforelse
    </div>
</div>

<form method="POST" action="{{ route('operator.contact', $case) }}" class="card pad stack g10 sec" style="max-width:620px">@csrf
    <span class="lbl">@lang('operator.log_contact')</span>
    <div class="grid g2" style="gap:10px">
        <div class="field"><label>@lang('operator.party')</label><input class="inp" name="party" required></div>
        <div class="field"><label>@lang('operator.channel')</label>
            <select class="inp" name="channel">
                @foreach (['CALL','SMS','WHATSAPP','MEETING','EMAIL'] as $ch)<option value="{{ $ch }}">{{ $ch }}</option>@endforeach
            </select></div>
    </div>
    <div class="field"><label>@lang('operator.summary')</label><textarea class="inp" name="summary" rows="3" required></textarea></div>
    <button class="btn sm">@lang('operator.log')</button>
    <div class="hint">@lang('operator.log_hint')</div>
</form>

<form method="POST" action="{{ route('operator.outcome', $case) }}" class="card pad stack g10 sec" style="max-width:620px">@csrf
    <span class="lbl">@lang('operator.record_outcome')</span>
    <select class="inp" name="outcome">
        @foreach (['NOT_PROCEEDING','TALKING','ENGAGED','MARRIED'] as $o)
            <option value="{{ $o }}">@lang('enum.outcome.'.$o)</option>
        @endforeach
    </select>
    <button class="btn sm ghost">@lang('common.save')</button>
    <div class="hint">@lang('operator.two_person_hint')</div>
</form>

@if ($case->contacts->isNotEmpty())
    <div class="sec">
        <span class="lbl">@lang('operator.activity')</span>
        <div class="stack g8">
            @foreach ($case->contacts as $c)
                <div class="card pad sm">
                    <span class="chip">{{ $c->channel }}</span> {{ $c->party }}
                    <div class="muted" style="margin-top:4px">{{ $c->summary }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
