@extends('layouts.app')
@section('title', $p['display_name'] === $p['profile_id'] ? $p['profile_id'] : $p['display_name'].' · '.$p['profile_id'])
@section('crumb', 'Public · Profile')

@section('content')
<div class="hd">
    <span class="lbl">{{ $p['profile_id'] }}</span>
    <h1>{{ $p['display_name'] }}</h1>
    {{-- A free account browses; it does not learn who anyone is. Say so,
         rather than letting the opaque id read as a missing name. --}}
    @auth
        @if ($p['display_name'] === $p['profile_id'])
            <span class="chip">@lang('profile.name_hidden_free') <a class="text-link" href="{{ route('plans') }}">@lang('plans.see_plans')</a></span>
        @endif
    @endauth
    <p class="sub">@lang('profile.public_note')</p>
</div>

<div class="grid" style="grid-template-columns:200px 1fr; gap:22px; align-items:start">
    <div class="stack g10">
        @include('partials.photo', [
            'name' => $p['display_name'],
            'blurred' => $p['photos'][0]['blurred'] ?? true,
            'url' => $p['photos'][0]['url'] ?? null,
            'size' => 200,
        ])
        @auth
            @if ($p['photos'][0]['blurred'] ?? true)
                <form method="POST" action="{{ route('member.access.request', $p['profile_id']) }}">@csrf
                    <input type="hidden" name="type" value="PHOTOS">
                    <button class="btn sm ghost full">@lang('profile.request_photos')</button>
                </form>
            @endif
        @endauth
        <div class="row wrap g4">
            @if (in_array($p['verified'], ['NID', 'NID_SELFIE']))
                <span class="chip ok">◆ @lang('profile.nid_verified')</span>
            @else
                <span class="chip">◆ @lang('profile.phone_verified')</span>
            @endif
        </div>
    </div>

    <div>
        @include('partials.sheet', ['rows' => [
            __('profile.profile_id')     => $p['profile_id'],
            __('profile.age')            => $p['age'] ?? null,
            __('profile.height')         => ($p['height_cm'] ?? null) ? $p['height_cm'].' cm' : null,
            __('profile.marital_status') => __('enum.marital.'.($p['marital_status'] ?? 'NEVER_MARRIED')),
            __('profile.lives_in')       => $p['city'] ?? $p['district'] ?? null,
            __('profile.home_district')  => $p['home_district'] ?? null,
            __('profile.religion')       => __('enum.religion.'.($p['religion'] ?? 'ISLAM')),
            __('profile.education')      => $p['education_level'] ?? null,
            __('profile.profession')     => $p['profession'] ?? null,
        ]])

        @if (($p['_level'] ?? '') === 'anonymous' || ($p['_level'] ?? '') === 'member')
            <div class="sec">
                <span class="lbl">@lang('profile.in_private')</span>
                {{-- Showing WHAT exists behind the wall is what makes people
                     request access. The values themselves are absent from the
                     payload entirely — not hidden with CSS. --}}
                @include('partials.sheet', ['showEmpty' => true, 'rows' => [
                    __('profile.full_name')  => null,
                    __('profile.about')      => null,
                    __('profile.family')     => null,
                    __('profile.employer')   => null,
                    __('profile.institution')=> null,
                    __('profile.area')       => null,
                    __('profile.contact')    => null,
                ]])
            </div>
        @else
            <div class="sec">
                <span class="lbl">@lang('profile.private_profile')</span>
                @include('partials.sheet', ['rows' => [
                    __('profile.full_name')   => $p['full_name'] ?? null,
                    __('profile.about')       => $p['about_me'] ?? null,
                    __('profile.employer')    => $p['employer'] ?? null,
                    __('profile.institution') => $p['institution'] ?? null,
                    __('profile.area')        => $p['area'] ?? null,
                ]])
                <div class="note" style="margin-top:12px">@lang('profile.contact_never')</div>
            </div>
        @endif

        @auth
            <div class="row g8 wrap sec">
                <form method="POST" action="{{ route('member.interest.send', $p['profile_id']) }}">@csrf
                    <button class="btn">@lang('interest.express')</button>
                </form>
                <form method="POST" action="{{ route('member.access.request', $p['profile_id']) }}">@csrf
                    <input type="hidden" name="type" value="PRIVATE_PROFILE">
                    <button class="btn ghost">@lang('profile.request_private')</button>
                </form>
            </div>
            <div class="note brand sec">
                <span class="t">@lang('profile.mutual_title')</span>
                @lang('profile.mutual_body')
            </div>
        @else
            <div class="note sec">
                <span class="t">@lang('profile.sign_in_title')</span>
                @lang('profile.sign_in_body')
                <div style="margin-top:8px"><a class="btn sm" href="{{ route('register') }}">@lang('nav.register_free')</a></div>
            </div>
        @endauth
    </div>
</div>
@endsection
