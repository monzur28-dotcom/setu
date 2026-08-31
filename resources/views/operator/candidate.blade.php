@extends('layouts.app')
@section('noindex', true)
@section('content')
<div class="hd"><h1 class="mono">{{ $payload['profile_id'] }}</h1></div>

@if (($payload['_level'] ?? '') === 'anonymous')
    <div class="note bad">
        <span class="t">@lang('operator.public_fields_only')</span>
        @lang('operator.needs_consent')
    </div>
@endif

@include('partials.sheet', ['rows' => [
    __('profile.age')        => $payload['age'] ?? null,
    __('profile.district')   => $payload['district'] ?? null,
    __('profile.profession') => $payload['profession'] ?? null,
    __('profile.education')  => $payload['education_level'] ?? null,
]])

<form method="POST" action="{{ route('operator.shortlist', [$case, $profile]) }}" class="sec">@csrf
    <button class="btn sm">@lang('operator.shortlist')</button>
</form>
@endsection
