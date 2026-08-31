@extends('layouts.app')
@section('title', __('biodata.preview'))
@section('crumb', 'Tools · Biodata')

@section('content')
@php $d = $draft->payload; @endphp

<div class="hd">
    <h1>@lang('biodata.ready')</h1>
    <p class="sub">@lang('biodata.print_hint')</p>
</div>

<div class="grid" style="grid-template-columns:1fr 300px; gap:22px; align-items:start">
    <div class="card" style="padding:26px 24px; font-family:Newsreader, 'Noto Serif Bengali', serif">
        <div style="text-align:center; border-bottom:1px solid var(--line-strong); padding-bottom:12px; margin-bottom:14px">
            <div style="font-size:23px; color:var(--ink)">{{ $d['full_name'] ?? '' }}</div>
            <div class="lbl" style="margin-top:4px">@lang('biodata.title')</div>
        </div>
        @include('partials.sheet', ['rows' => [
            __('biodata.dob')           => $d['date_of_birth'] ?? null,
            __('biodata.height')        => $d['height'] ?? null,
            __('biodata.religion')      => $d['religion'] ?? null,
            __('biodata.marital')       => $d['marital_status'] ?? null,
            __('biodata.city')          => $d['city'] ?? null,
            __('biodata.home_district') => $d['home_district'] ?? null,
            __('biodata.education')     => $d['education'] ?? null,
            __('biodata.profession')    => $d['profession'] ?? null,
            __('biodata.father')        => $d['father'] ?? null,
            __('biodata.mother')        => $d['mother'] ?? null,
            __('biodata.siblings')      => $d['siblings'] ?? null,
            __('biodata.expectations')  => $d['expectations'] ?? null,
            __('biodata.contact')       => $d['contact'] ?? null,
        ]])
        <div style="text-align:center; margin-top:16px; padding-top:10px; border-top:1px solid var(--line)"
             class="lbl">{{ strtoupper(config('app.name')) }}</div>
    </div>

    <div class="stack g14" style="position:sticky; top:78px">
        <button class="btn lg full" onclick="window.print()">@lang('biodata.download')</button>

        {{-- The invitation appears AFTER the download, never before.
             One honest, dismissible line. Spec 9.1. --}}
        <div class="note brand">
            <span class="t">@lang('biodata.cta_title')</span>
            @lang('biodata.cta_body')
            <div style="margin-top:10px">
                <a class="btn sm" href="{{ route('biodata.convert', $draft->token) }}">@lang('biodata.cta_button')</a>
            </div>
        </div>

        <a class="btn quiet" href="{{ route('biodata.create') }}">@lang('biodata.edit_again')</a>
    </div>
</div>
@endsection
