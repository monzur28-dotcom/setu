@extends('layouts.app')
@section('title', __('family.introductions'))
@section('noindex', true)
@section('content')
<div class="hd"><h1>@lang('family.introductions')</h1></div>
<div class="grid g3">
    @forelse ($connected as $p)
        @include('partials.profile-card', ['p' => $p])
    @empty
        <div class="card pad muted" style="grid-column:1/-1">@lang('family.no_connections')</div>
    @endforelse
</div>
@endsection
