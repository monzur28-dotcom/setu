@extends('layouts.app')
@section('title', __('admin.operations'))
@section('noindex', true)
@section('crumb', 'Staff · Operations')

@section('content')
<div class="hd"><h1>@lang('admin.operations')</h1></div>

<div class="grid g3">
    @foreach ([
        [__('admin.photo_queue'), $photoQueue, __('admin.sla_4h')],
        // Nothing in this number is public yet. It is the backlog of people
        // waiting to exist on the site at all.
        [__('admin.profile_queue'), $profileQueue, trans_choice('admin.awaiting_first', $awaitingFirstReview, ['n' => $awaitingFirstReview])],
        [__('admin.ad_queue'), $adQueue, ''],
        [__('admin.open_reports'), $reports, $critical ? trans_choice('admin.critical', $critical, ['n' => $critical]) : ''],
        [__('admin.consent_violations'), $consentViolations, __('admin.must_be_zero')],
    ] as [$a, $b, $c])
        <div class="card pad stack g4" style="{{ $a === __('admin.consent_violations') && $b > 0 ? 'border-color:var(--bad)' : '' }}">
            <span class="lbl">{{ $a }}</span>
            <span class="serif" style="font-size:26px;color:var(--ink)">{{ $b }}</span>
            <span class="xs muted">{{ $c }}</span>
        </div>
    @endforeach
</div>

<div class="row g8 wrap sec">
    <a class="btn ghost" href="{{ route('admin.moderation') }}">@lang('admin.moderation')</a>
    <a class="btn ghost" href="{{ route('admin.fees') }}">@lang('admin.success_fees')</a>
    <a class="btn ghost" href="{{ route('admin.words') }}">@lang('admin.words')</a>
    <a class="btn ghost" href="{{ route('admin.hero') }}">@lang('admin.hero')</a>
    <a class="btn ghost" href="{{ route('admin.appearance') }}">@lang('admin.appearance')</a>
    <a class="btn ghost" href="{{ route('admin.seo') }}">@lang('admin.seo')</a>
</div>

@if ($consentViolations > 0)
    <div class="note bad sec">
        <span class="t">@lang('admin.violation_title')</span>
        @lang('admin.violation_body')
    </div>
@endif
@endsection
