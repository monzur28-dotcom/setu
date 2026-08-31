@extends('layouts.app')
@section('title', __('nav.dashboard'))
@section('noindex', true)
@section('crumb', 'Member · Dashboard')

@section('content')
<div class="hd">
    <h1>@lang('dash.greeting', ['name' => auth()->user()->candidate_name])</h1>
    <p class="sub">@lang('dash.sub')</p>
</div>

{{--
    The member has to be told where their profile stands. A profile that is
    waiting for review is not broken and not rejected, and the difference
    matters enough to say plainly rather than leave them refreshing search
    results looking for themselves.
--}}
@if ($profile->moderation_status !== 'APPROVED')
    <div class="note {{ $profile->moderation_status === 'REJECTED' ? 'bad' : '' }} sec" style="margin-top:0">
        <span class="t">@lang('dash.review_'.strtolower($profile->moderation_status).'_title')</span>
        @lang('dash.review_'.strtolower($profile->moderation_status).'_body')
        @if ($profile->moderation_reason)
            <div class="sm" style="margin-top:6px"><span class="lbl xs">@lang('admin.reason')</span> {{ $profile->moderation_reason }}</div>
        @endif
    </div>
@elseif ($hasPendingEdit)
    <div class="note sec" style="margin-top:0">
        <span class="t">@lang('dash.review_edit_title')</span>
        @lang('dash.review_edit_body')
    </div>
@endif
<div class="grid g3">
    <a class="card pad stack g6" href="{{ route('member.interests') }}"
       style="border-color:{{ $interests->count() ? 'var(--brand)' : 'var(--line)' }}">
        <span class="lbl">@lang('dash.interests_received')</span>
        <span class="serif" style="font-size:30px;color:var(--ink)">{{ $interests->count() }}</span>
        <span class="xs muted">@lang('dash.replying_free')</span>
    </a>
    <a class="card pad stack g6" href="{{ route('member.access') }}">
        <span class="lbl">@lang('dash.access_requests')</span>
        <span class="serif" style="font-size:30px;color:var(--ink)">{{ $requests->count() }}</span>
        <span class="xs muted">@lang('dash.granting_mutual')</span>
    </a>
    <a class="card pad stack g6" href="{{ route('member.mailbox') }}">
        <span class="lbl">@lang('dash.unread')</span>
        <span class="serif" style="font-size:30px;color:var(--ink)">{{ $unread }}</span>
        <span class="xs muted">@lang('dash.contact_hidden')</span>
    </a>
</div>

<div class="grid g2 sec" style="align-items:start">
    <div class="card pad stack g12">
        <span class="lbl">@lang('dash.profile_strength')</span>
        <div class="row between center">
            <span class="serif" style="font-size:26px;color:var(--ink)">{{ $profile->completeness }}%</span>
            <span class="chip {{ in_array(auth()->user()->verification_level, ['NID','NID_SELFIE']) ? 'ok' : '' }}">
                ◆ @lang('profile.'.(in_array(auth()->user()->verification_level, ['NID','NID_SELFIE']) ? 'nid_verified' : 'phone_verified'))
            </span>
        </div>
        <div style="height:5px;background:var(--surface-2);border-radius:3px;overflow:hidden">
            <div style="width:{{ $profile->completeness }}%;height:100%;background:var(--brand)"></div>
        </div>
        <p class="sm">@lang('dash.photo_nudge')</p>
        <div class="row g8">
            <a class="btn sm ghost" href="{{ route('member.privacy') }}">@lang('nav.privacy')</a>
            <a class="btn sm ghost" href="{{ route('member.verification') }}">@lang('dash.get_verified')</a>
        </div>
    </div>

    <div class="card pad stack g12">
        <span class="lbl">@lang('dash.your_plan')</span>
        <div class="row between center">
            <span class="serif" style="font-size:22px;color:var(--ink)">
                {{ app()->getLocale() === 'bn' ? auth()->user()->plan()['label_bn'] : auth()->user()->plan()['label_en'] }}
            </span>
            @if (auth()->user()->planCode() === 'free')
                <a class="btn sm" href="{{ route('plans') }}">@lang('plans.see')</a>
            @else
                <span class="chip ok">@lang('common.active')</span>
            @endif
        </div>
        @include('partials.sheet', ['rows' => [
            __('dash.browse_search') => __('common.unlimited'),
            __('dash.receive_reply') => __('dash.free_always'),
            __('dash.send_interests') => auth()->user()->plan()['interests_per_day']
                ? auth()->user()->plan()['interests_per_day'].' / '.__('common.day')
                : __('common.not_included'),
        ]])
    </div>
</div>

@if ($case)
    <div class="card pad row between center wrap g12 sec">
        <div>
            <div class="b sm">@lang('dash.your_ghotok'): {{ $case->operator->candidate_name }}</div>
            <div class="xs muted">@lang('enum.case_stage.'.$case->stage) · {{ $case->daysOpen() }} @lang('common.days')</div>
        </div>
        <span class="chip brand">@lang('enum.case_stage.'.$case->stage)</span>
    </div>
@endif

<div class="sec">
    <span class="lbl">@lang('nav.family')</span>
    <div class="card pad row between center wrap g12">
        <div>
            <div class="b sm">
                {{ $guardians->isEmpty() ? __('family.none_linked') : trans_choice('family.linked', $guardians->count(), ['n' => $guardians->count()]) }}
            </div>
            <div class="xs muted">@lang('family.you_decide')</div>
        </div>
        <a class="btn sm ghost" href="{{ route('member.family') }}">@lang('family.manage')</a>
    </div>
</div>
@endsection
