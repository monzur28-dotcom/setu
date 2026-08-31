@extends('layouts.app')
@section('title', __('biodata.title'))
@section('meta_description', __('biodata.sub'))
@section('crumb', 'Tools · Biodata maker')

@section('content')
<div class="hd">
    <span class="lbl">/marriage-biodata-maker</span>
    <h1>@lang('biodata.title')</h1>
    <p class="sub">@lang('biodata.sub')</p>
</div>

<form method="POST" action="{{ route('biodata.store') }}" class="stack g20">@csrf
    <div class="card pad stack g12">
        <span class="lbl">@lang('biodata.personal')</span>
        <div class="grid g2" style="gap:10px">
            <div class="field"><label>@lang('biodata.full_name')</label><input class="inp" name="full_name" value="{{ old('full_name') }}" required></div>
            <div class="field"><label>@lang('biodata.dob')</label><input class="inp" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
            <div class="field"><label>@lang('biodata.height')</label><input class="inp" name="height" value="{{ old('height') }}"></div>
            <div class="field"><label>@lang('biodata.religion')</label>
                <select class="inp" name="religion">
                    @foreach (['ISLAM','HINDUISM','CHRISTIANITY','BUDDHISM'] as $r)
                        <option value="{{ __('enum.religion.'.$r) }}">@lang('enum.religion.'.$r)</option>
                    @endforeach
                </select></div>
            <div class="field"><label>@lang('biodata.marital')</label>
                <select class="inp" name="marital_status">
                    @foreach (['NEVER_MARRIED','DIVORCED','WIDOWED'] as $m)
                        <option value="{{ __('enum.marital.'.$m) }}">@lang('enum.marital.'.$m)</option>
                    @endforeach
                </select></div>
            <div class="field"><label>@lang('biodata.city')</label><input class="inp" name="city" value="{{ old('city') }}"></div>
        </div>
    </div>

    <div class="card pad stack g12">
        <span class="lbl">@lang('biodata.education_career')</span>
        <div class="grid g2" style="gap:10px">
            <div class="field"><label>@lang('biodata.education')</label><input class="inp" name="education" value="{{ old('education') }}"></div>
            <div class="field"><label>@lang('biodata.profession')</label><input class="inp" name="profession" value="{{ old('profession') }}"></div>
        </div>
    </div>

    <div class="card pad stack g12">
        <span class="lbl">@lang('biodata.family')</span>
        <div class="grid g2" style="gap:10px">
            <div class="field"><label>@lang('biodata.father')</label><input class="inp" name="father" value="{{ old('father') }}"></div>
            <div class="field"><label>@lang('biodata.mother')</label><input class="inp" name="mother" value="{{ old('mother') }}"></div>
            <div class="field"><label>@lang('biodata.siblings')</label><input class="inp" name="siblings" value="{{ old('siblings') }}"></div>
            <div class="field"><label>@lang('biodata.home_district')</label><input class="inp" name="home_district" value="{{ old('home_district') }}"></div>
        </div>
    </div>

    <div class="card pad stack g12">
        <span class="lbl">@lang('biodata.expectations')</span>
        <div class="field"><textarea class="inp" name="expectations" rows="3">{{ old('expectations') }}</textarea></div>
        <div class="field"><label>@lang('biodata.contact')</label><input class="inp" name="contact" value="{{ old('contact') }}"></div>
    </div>

    <div class="row g8 wrap">
        <button class="btn lg">@lang('biodata.generate')</button>
        <select class="inp" name="template" style="width:auto">
            @foreach (['traditional','modern','formal','compact'] as $t)
                <option value="{{ $t }}">@lang('biodata.template_'.$t)</option>
            @endforeach
        </select>
    </div>

    <div class="note">
        <span class="t">@lang('biodata.no_signup_title')</span>
        @lang('biodata.no_signup_body')
    </div>
</form>
@endsection
