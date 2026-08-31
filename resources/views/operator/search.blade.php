@extends('layouts.app')
@section('title', __('operator.sourcing'))
@section('noindex', true)
@section('content')
<div class="hd">
    <h1>@lang('operator.sourcing')</h1>
    <p class="sub">@lang('operator.public_only')</p>
</div>
<div class="grid g4c">
    @foreach ($cards as $p)
        @include('partials.profile-card', ['p' => $p])
    @endforeach
</div>
@endsection
