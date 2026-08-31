@extends('layouts.app')
@section('title', __('ads.title'))
@section('meta_description', __('ads.sub'))
@section('crumb', 'Tools · Classifieds')

@section('content')
<div class="hd">
    <span class="lbl">/classifieds</span>
    <h1>@lang('ads.title')</h1>
    <p class="sub">@lang('ads.sub')</p>
</div>

<div class="row g8 wrap">
    @auth
        <a class="btn" href="{{ route('classifieds.create') }}">@lang('ads.place')</a>
    @else
        <a class="btn" href="{{ route('register') }}">@lang('ads.place')</a>
    @endauth
</div>

<div class="stack g12 sec">
    @forelse ($ads as $ad)
        <article class="card pad">
            <div class="row between center wrap g8">
                <span class="chip brand">@lang('ads.'.strtolower($ad->looking_for).'_wanted')</span>
                <span class="mono xs muted">{{ $ad->expires_at?->diffForHumans() }}</span>
            </div>
            <p style="margin:10px 0 4px; font-size:15px; color:var(--ink)">{{ $ad->headline }}</p>
            <p class="sm muted">{{ Str::limit($ad->body, 220) }}</p>
            <div class="row wrap g6" style="margin-top:10px">
                @if ($ad->district)<span class="chip">{{ $ad->district->name() }}</span>@endif
                @if ($ad->education)<span class="chip">{{ $ad->education }}</span>@endif
                @if ($ad->no_media_flag)
                    <span class="chip gold">নো-মিডিয়া · @lang('ads.no_media')</span>
                @endif
                <span class="grow"></span>
                <a class="btn sm ghost" href="{{ route('classifieds.show', $ad->slug) }}">@lang('common.view')</a>
            </div>
        </article>
    @empty
        <div class="card pad muted">@lang('ads.none')</div>
    @endforelse
</div>

<div class="sec">{{ $ads->links() }}</div>

<div class="note sec">
    <span class="t">নো-মিডিয়া</span>
    @lang('ads.no_media_explained')
</div>
@endsection
