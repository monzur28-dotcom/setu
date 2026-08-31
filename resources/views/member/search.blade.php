@extends('layouts.app')
@section('title', __('search.title'))
@section('noindex', true)
@section('crumb', 'Member · Search')

@section('content')
<div class="hd"><h1>@lang('search.title')</h1></div>

<div class="grid" style="grid-template-columns:250px 1fr; gap:20px; align-items:start">
    <form method="GET" class="card pad stack g12" style="position:sticky;top:78px">
        <span class="lbl">@lang('search.filters')</span>

        <div class="field"><label>@lang('search.marital_status')</label>
            <select class="inp" name="marital_status">
                <option value="">@lang('common.any')</option>
                @foreach (['NEVER_MARRIED','DIVORCED','LEGALLY_SEPARATED','WIDOWED'] as $m)
                    <option value="{{ $m }}" @selected(($filters['marital_status'] ?? '') === $m)>@lang('enum.marital.'.$m)</option>
                @endforeach
            </select></div>

        <div class="field"><label>@lang('search.religion')</label>
            <select class="inp" name="religion">
                <option value="">@lang('common.any')</option>
                @foreach (['ISLAM','HINDUISM','CHRISTIANITY','BUDDHISM'] as $r)
                    <option value="{{ $r }}" @selected(($filters['religion'] ?? '') === $r)>@lang('enum.religion.'.$r)</option>
                @endforeach
            </select></div>

        <div class="row g6">
            <div class="field grow"><label>@lang('search.age_min')</label><input class="inp" name="age_min" value="{{ $filters['age_min'] ?? '' }}"></div>
            <div class="field grow"><label>@lang('search.age_max')</label><input class="inp" name="age_max" value="{{ $filters['age_max'] ?? '' }}"></div>
        </div>

        <div class="field"><label>@lang('search.district')</label>
            <select class="inp" name="district_id">
                <option value="">@lang('common.any')</option>
                @foreach ($districts as $d)
                    <option value="{{ $d->id }}" @selected(($filters['district_id'] ?? '') == $d->id)>{{ $d->name() }}</option>
                @endforeach
            </select></div>

        <div class="field"><label>@lang('profile.home_district')</label>
            <select class="inp" name="home_district_id">
                <option value="">@lang('common.any')</option>
                @foreach ($districts as $d)
                    <option value="{{ $d->id }}" @selected(($filters['home_district_id'] ?? '') == $d->id)>{{ $d->name() }}</option>
                @endforeach
            </select></div>

        <div class="field"><label>@lang('search.relocation')</label>
            <select class="inp" name="relocation_intent">
                <option value="">@lang('common.any')</option>
                @foreach (['WILL_RELOCATE','WILL_NOT','PARTNER_RELOCATES','OPEN'] as $r)
                    <option value="{{ $r }}" @selected(($filters['relocation_intent'] ?? '') === $r)>@lang('enum.relocation.'.$r)</option>
                @endforeach
            </select></div>

        <div class="field"><label>@lang('search.verification')</label>
            <select class="inp" name="verification">
                <option value="">@lang('common.any')</option>
                <option value="NID" @selected(($filters['verification'] ?? '') === 'NID')>@lang('profile.nid_verified')</option>
            </select></div>

        <div class="field"><label>@lang('search.last_active')</label>
            <select class="inp" name="last_active">
                <option value="">@lang('common.any')</option>
                <option value="1">@lang('search.24h')</option>
                <option value="7">@lang('search.7d')</option>
                <option value="30">@lang('search.30d')</option>
            </select></div>

        <label class="row g8 sm"><input type="checkbox" name="has_photo" value="1" @checked($filters['has_photo'] ?? false)> @lang('search.with_photos')</label>

        <button class="btn sm">@lang('search.apply')</button>
    </form>

    <div>
        <div class="row between center wrap g8" style="margin-bottom:14px">
            <span class="sm muted">{{ trans_choice('search.count', $results->total(), ['n' => number_format($results->total())]) }}</span>
            <form method="POST" action="{{ route('member.search.save') }}" class="row g6">@csrf
                @foreach ($filters as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <input class="inp sm" name="name" placeholder="@lang('search.name_this_search')" style="width:160px">
                <button class="btn quiet sm">@lang('search.save')</button>
            </form>
        </div>

        <div class="grid g3">
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
    </div>
</div>
@endsection
