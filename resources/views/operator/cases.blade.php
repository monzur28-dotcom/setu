@extends('layouts.app')
@section('title', __('operator.caseload'))
@section('noindex', true)
@section('crumb', 'Staff · Ghotok console')

@section('content')
<div class="hd">
    <h1>@lang('operator.caseload')</h1>
    <p class="sub">{{ auth()->user()->candidate_name }} · {{ trans_choice('operator.active_cases', $cases->count(), ['n' => $cases->count()]) }}</p>
</div>

<div class="card scrollx">
    <table class="tbl">
        <thead><tr>
            <th>@lang('operator.client')</th><th>@lang('operator.stage')</th>
            <th>@lang('operator.open')</th><th>@lang('operator.consent')</th><th></th>
        </tr></thead>
        <tbody>
        @forelse ($cases as $c)
            @php $hasConsent = $c->consents()->whereNull('revoked_at')->exists(); @endphp
            <tr>
                <td class="mono">{{ $c->client->profile_id }}</td>
                <td>@lang('enum.case_stage.'.$c->stage)</td>
                <td class="mono">{{ $c->daysOpen() }}d</td>
                <td>
                    @if ($hasConsent)
                        <span class="chip ok">✓</span>
                    @else
                        <span class="chip bad">⚠ @lang('operator.no_consent')</span>
                    @endif
                </td>
                <td><a class="btn sm ghost" href="{{ route('operator.case', $c) }}">@lang('common.open')</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">@lang('operator.no_cases')</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Stated in the console itself, so the boundary is visible to the person
     working inside it every day. Spec 13.1. --}}
<div class="note bad sec">
    <span class="t">@lang('operator.limits_title')</span>
    @lang('operator.limits_body')
</div>
@endsection
