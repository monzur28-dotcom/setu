@extends('layouts.app')
@section('title', __('nav.login'))
@section('noindex', true)
@section('crumb', 'Auth · Log in')

@section('content')
<div class="hd"><h1>@lang('nav.login')</h1></div>

<form method="POST" action="{{ route('login.attempt') }}" class="card pad stack g14" style="max-width:400px">@csrf
    <div class="field">
        <label>@lang('auth.identifier')</label>
        <input class="inp" name="identifier" value="{{ old('identifier') }}" autofocus required>
    </div>
    <div class="field">
        <label>@lang('auth.password_label')</label>
        <input class="inp" type="password" name="password" autocomplete="current-password" required>
    </div>
    <button class="btn lg">@lang('nav.login')</button>
</form>

{{-- Many members will not remember a password. Code login is offered at
     equal weight, not hidden behind "trouble signing in?". Spec 6.4. --}}
<form method="POST" action="{{ route('login.code.request') }}" class="card pad stack g10 sec" style="max-width:400px">@csrf
    <div class="field">
        <label>@lang('auth.identifier')</label>
        <input class="inp" name="identifier" placeholder="01XXXXXXXXX">
    </div>
    <button class="btn ghost full">@lang('auth.login_with_code')</button>
    <div class="hint">@lang('auth.code_hint')</div>
</form>

<div class="hint sec">@lang('auth.new_here') <a href="{{ route('register') }}" style="color:var(--brand)">@lang('nav.register_free')</a></div>
@endsection
