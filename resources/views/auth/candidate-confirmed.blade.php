@extends('layouts.app')
@section('noindex', true)
@section('content')
<div class="hd"><h1>@lang('auth.confirmed_title')</h1><p class="sub">@lang('auth.confirmed_body')</p></div>
<a class="btn" href="{{ route('login') }}">@lang('nav.login')</a>
@endsection
