@extends('layouts.app')
@section('title', __('auth.verify_number'))
@section('noindex', true)
@section('crumb', 'Auth · Verify phone')

@section('content')
<div class="hd">
    <h1>@lang('auth.verify_number')</h1>
    <p class="sub">@lang('auth.otp_sent', ['mobile' => $mobile])</p>
</div>

<form method="POST" action="{{ route('register.otp.verify') }}" class="card pad stack g14" style="max-width:420px">@csrf
    <div class="field">
        <label>@lang('auth.code')</label>
        <input class="inp mono" name="code" inputmode="numeric" maxlength="6"
               style="text-align:center; font-size:24px; letter-spacing:.4em" autofocus required>
    </div>
    <button class="btn lg">@lang('auth.verify')</button>
    <div class="hint">@lang('auth.resend_hint')</div>
    @if (app()->environment('local'))
        <div class="note"><span class="t">Local</span>@lang('auth.local_otp_hint')</div>
    @endif
</form>
@endsection
