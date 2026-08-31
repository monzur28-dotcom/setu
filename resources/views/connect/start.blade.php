@extends('layouts.connect')
@section('title', __('connect.name'))
@section('crumb', 'Connect · Join')

@section('content')
<div class="hd">
    <span class="lbl">@lang('connect.name')</span>
    <h1>@lang('connect.headline')</h1>
    <p class="sub">@lang('connect.sub')</p>
</div>

{{-- The opt-in screen is a contract with the member. Every promise here is
     enforced somewhere in the codebase, not just written down. Spec 11.1. --}}
<div class="stack g12" style="max-width:64ch">
    @foreach (['separate', 'private', 'family', 'no_contact'] as $k)
        <div class="card pad stack g4">
            <span class="b sm">@lang("connect.promise_{$k}_title")</span>
            <span class="sm muted">@lang("connect.promise_{$k}_body")</span>
        </div>
    @endforeach

    <div class="note bad">
        <span class="t">@lang('connect.photo_warning_title')</span>
        @lang('connect.photo_warning_body')
    </div>

    <div class="card pad stack g10">
        <span class="lbl">@lang('connect.requirements')</span>
        <div class="row g8 center sm"><span class="chip ok">✓</span> @lang('connect.req_age')</div>
        <div class="row g8 center sm"><span class="chip ok">✓</span> @lang('connect.req_phone')</div>
        <div class="row g8 center sm"><span class="chip">○</span> @lang('connect.req_selfie')</div>
    </div>

    <form method="POST" action="{{ route('connect.enable') }}">@csrf
        <button class="btn lg">@lang('connect.join')</button>
    </form>
    <a class="btn quiet" href="{{ route('member.dashboard') }}">@lang('common.not_now')</a>
</div>
@endsection
