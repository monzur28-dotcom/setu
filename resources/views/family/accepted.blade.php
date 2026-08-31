@extends('layouts.app')
@section('noindex', true)
@section('content')
<div class="hd">
    <h1>@lang('family.accepted_title')</h1>
    <p class="sub">@lang('family.accepted_body', ['name' => $link->candidate->candidate_name])</p>
</div>
<a class="btn" href="{{ route('family.dashboard') }}">@lang('family.open_dashboard')</a>
@endsection
