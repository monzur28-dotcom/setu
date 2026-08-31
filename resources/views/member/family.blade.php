@extends('layouts.app')
@section('title', __('nav.family'))
@section('noindex', true)
@section('crumb', 'Member · Family')

@section('content')
<div class="hd">
    <h1>@lang('family.title')</h1>
    <p class="sub">@lang('family.you_decide')</p>
</div>

@if ($links->isNotEmpty())
    <div class="stack g12">
        @foreach ($links as $link)
            <div class="card pad row between center wrap g12">
                <div>
                    <div class="b sm">{{ $link->guardian->candidate_name }}
                        <span class="muted">· @lang('enum.relationship.'.$link->relationship)</span></div>
                    <div class="xs muted">@lang('enum.level.'.$link->visibility_level)</div>
                </div>
                <div class="row g8">
                    <form method="POST" action="{{ route('member.family.level', $link) }}" class="row g6">
                        @csrf @method('PATCH')
                        <select class="inp sm" name="level" onchange="this.form.submit()">
                            @foreach (['L1_PROGRESS','L2_INTRODUCTIONS','L3_FULL'] as $l)
                                <option value="{{ $l }}" @selected($link->visibility_level === $l)>@lang('enum.level.'.$l)</option>
                            @endforeach
                        </select>
                    </form>
                    {{-- Two taps, no reason required, silent to the guardian. --}}
                    <form method="POST" action="{{ route('member.family.revoke', $link) }}">@csrf @method('DELETE')
                        <button class="btn danger sm">@lang('family.end_access')</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('member.family.invite') }}" class="card pad stack g12 sec" style="max-width:640px">@csrf
    <span class="lbl">@lang('family.invite_someone')</span>
    <div class="grid g2" style="gap:10px">
        <div class="field"><label>@lang('family.their_name')</label><input class="inp" name="name" required></div>
        <div class="field"><label>@lang('auth.mobile')</label><input class="inp" name="mobile" required></div>
    </div>
    <div class="field"><label>@lang('family.relationship')</label>
        <select class="inp" name="relationship">
            @foreach (['MOTHER','FATHER','BROTHER','SISTER','AUNT','UNCLE','COUSIN','LEGAL_GUARDIAN'] as $r)
                <option value="{{ $r }}">@lang('enum.relationship.'.$r)</option>
            @endforeach
        </select></div>

    <div class="field"><label>@lang('family.what_they_see')</label>
        <div class="stack g8">
            @foreach (['L1_PROGRESS','L2_INTRODUCTIONS','L3_FULL'] as $i => $l)
                <label class="card pad row g10" style="cursor:pointer">
                    <input type="radio" name="level" value="{{ $l }}" @checked($i === 0) style="margin-top:3px">
                    <span class="stack g2">
                        <span class="b sm">@lang('enum.level.'.$l)</span>
                        <span class="xs muted">@lang('family.level_'.strtolower(explode('_', $l)[0]).'_hint')</span>
                    </span>
                </label>
            @endforeach
        </div></div>

    <button class="btn">@lang('family.send_invite')</button>
</form>

{{-- The limits stated to the candidate, plainly, so they know what they are
     and are not granting. Spec 12.2. --}}
<div class="note bad sec" style="max-width:640px">
    <span class="t">@lang('family.never_title')</span>
    @lang('family.never_body')
</div>

@if ($log->isNotEmpty())
    <div class="sec">
        <span class="lbl">@lang('family.access_log')</span>
        <div class="card scrollx">
            <table class="tbl">
                <thead><tr><th>@lang('common.when')</th><th>@lang('common.action')</th></tr></thead>
                <tbody>
                    @foreach ($log as $entry)
                        <tr><td class="mono">{{ $entry->created_at?->diffForHumans() }}</td><td>{{ $entry->action }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="hint" style="margin-top:8px">@lang('family.log_hint')</div>
    </div>
@endif
@endsection
