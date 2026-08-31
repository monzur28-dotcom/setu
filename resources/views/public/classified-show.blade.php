@extends('layouts.app')
@section('title', $ad->headline)
@section('crumb', 'Tools · Classifieds')

@section('content')
<div class="hd">
    <span class="chip brand">@lang('ads.'.strtolower($ad->looking_for).'_wanted')</span>
    <h1 style="margin-top:8px">{{ $ad->headline }}</h1>
</div>

<div class="card pad stack g12" style="max-width:70ch">
    <p>{{ $ad->body }}</p>
    @include('partials.sheet', ['rows' => [
        __('profile.age')        => $ad->age,
        __('profile.education')  => $ad->education,
        __('profile.profession') => $ad->profession,
        __('profile.religion')   => $ad->religion,
        __('search.district')    => $ad->district?->name(),
    ]])
    <div class="note">
        <span class="t">@lang('ads.contact')</span>
        <span class="mono">{{ $ad->contact_phone }}</span>
    </div>
    @if ($ad->no_media_flag)
        <div class="note bad"><span class="t">নো-মিডিয়া</span>@lang('ads.no_media_notice')</div>
    @endif
    <div class="note bad"><span class="t">@lang('safety.title')</span>@lang('safety.never_money')</div>
</div>
@endsection
