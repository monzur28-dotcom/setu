@extends('layouts.app')
@section('title', __('auth.login_with_code'))
@section('noindex', true)
@section('content')
<div class="hd"><h1>@lang('auth.enter_code')</h1></div>
<form method="POST" action="{{ route('login.code.verify') }}" class="card pad stack g14" style="max-width:400px">@csrf
    <div class="field"><label>@lang('auth.code')</label>
        <input class="inp mono" name="code" inputmode="numeric" maxlength="6"
               style="text-align:center;font-size:24px;letter-spacing:.4em" autofocus required></div>
    <button class="btn lg">@lang('nav.login')</button>
</form>
@endsection
