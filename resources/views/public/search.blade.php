@extends('layouts.app')
@section('title', __('search.title'))
@section('crumb', 'Public · Search')

@section('content')
<div class="hd">
    <span class="lbl">/search</span>
    <h1>@lang('search.title')</h1>
    <p class="sub">{{ trans_choice('search.count', $results->total(), ['n' => number_format($results->total())]) }}</p>
</div>

{{--
    Filters live on the results page, not only on the front page. Someone
    who arrives here from a search engine, a shared link or a country tile
    has to be able to change their mind without going home first — and a
    global audience arrives at this URL from far more directions than a
    single-market one does.
--}}
<form method="GET" action="{{ route('public.search') }}" class="card pad filter-bar">
    <div class="field">
        <label>@lang('search.looking_for')</label>
        <select class="inp" name="gender">
            <option value="">@lang('common.all')</option>
            <option value="FEMALE" @selected(($filters['gender'] ?? '') === 'FEMALE')>@lang('search.bride')</option>
            <option value="MALE" @selected(($filters['gender'] ?? '') === 'MALE')>@lang('search.groom')</option>
        </select>
    </div>

    <div class="field">
        <label>@lang('search.country')</label>
        @include('partials.country-select', [
            'countries' => $countries,
            'selected'  => $filters['country'] ?? null,
            'any'       => __('search.any_country'),
        ])
    </div>

    <div class="field">
        <label>@lang('search.religion')</label>
        <select class="inp" name="religion">
            <option value="">@lang('common.all')</option>
            @foreach (['ISLAM', 'HINDUISM', 'CHRISTIANITY', 'BUDDHISM', 'JUDAISM', 'SIKHISM', 'JAINISM', 'OTHER'] as $r)
                <option value="{{ $r }}" @selected(($filters['religion'] ?? '') === $r)>@lang('enum.religion.'.$r)</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label>@lang('search.age')</label>
        <div class="row g6">
            <input class="inp" name="age_min" value="{{ $filters['age_min'] ?? '' }}" inputmode="numeric" placeholder="18">
            <span class="muted">–</span>
            <input class="inp" name="age_max" value="{{ $filters['age_max'] ?? '' }}" inputmode="numeric" placeholder="70">
        </div>
    </div>

    <div class="field">
        <label>@lang('search.marital_status')</label>
        <select class="inp" name="marital_status">
            <option value="">@lang('common.all')</option>
            @foreach (['NEVER_MARRIED', 'DIVORCED', 'WIDOWED', 'LEGALLY_SEPARATED'] as $m)
                <option value="{{ $m }}" @selected(($filters['marital_status'] ?? '') === $m)>@lang('enum.marital.'.$m)</option>
            @endforeach
        </select>
    </div>

    <label class="row g6 center sm has-photo">
        <input type="checkbox" name="has_photo" value="1" @checked(! empty($filters['has_photo']))>
        <span>@lang('search.with_photo')</span>
    </label>

    <div class="row g6">
        <button class="btn">@lang('search.apply')</button>
        <a class="btn ghost" href="{{ route('public.search') }}">@lang('search.clear')</a>
    </div>
</form>

<div class="grid g4c sec">
    @forelse ($cards as $p)
        @include('partials.profile-card', ['p' => $p])
    @empty
        <div class="card pad" style="grid-column:1/-1">
            <div class="b">@lang('search.no_results')</div>
            <p class="sm muted">@lang('search.relax_hint')</p>
        </div>
    @endforelse
</div>

<div class="sec">{{ $results->links() }}</div>
@endsection
