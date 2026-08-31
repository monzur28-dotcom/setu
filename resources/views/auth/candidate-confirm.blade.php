@extends('layouts.app')
@section('title', __('auth.confirm_title'))
@section('noindex', true)

@section('content')
<div class="hd">
    <h1>@lang('auth.confirm_title')</h1>
    <p class="sub">@lang('auth.confirm_sub', ['name' => $user->registrant_name])</p>
</div>

<div class="card pad stack g12" style="max-width:600px">
    @include('partials.sheet', ['rows' => [
        __('auth.candidate_name') => $user->candidate_name,
        __('auth.created_by')     => $user->registrant_name.' ('.__('enum.relationship.'.$user->registrant_relationship).')',
    ]])

    <div class="note brand">
        <span class="t">@lang('auth.confirm_note_title')</span>
        @lang('auth.confirm_note_body')
    </div>

    <div class="row g8 wrap">
        <form method="POST" action="{{ route('candidate.confirm.store', $token) }}">@csrf
            <button class="btn">@lang('auth.confirm_yes')</button>
        </form>
        <form method="POST" action="{{ route('candidate.reject', $token) }}">@csrf
            <button class="btn danger">@lang('auth.confirm_no')</button>
        </form>
    </div>
    <div class="hint">@lang('auth.confirm_no_reason')</div>
</div>
@endsection
