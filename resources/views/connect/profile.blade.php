@extends('layouts.connect')
@section('title', __('connect.my_profile'))
@section('crumb', 'Connect · Profile')

@section('content')
<div class="hd"><h1>@lang('connect.my_profile')</h1><p class="sub">@lang('connect.profile_sub')</p></div>

<form method="POST" action="{{ route('connect.profile.update') }}" class="card pad stack g12" style="max-width:620px">
    @csrf @method('PATCH')

    <div class="grid g2" style="gap:10px">
        <div class="field"><label>@lang('connect.display_name')</label>
            <input class="inp" name="display_name" value="{{ $cp->display_name }}" maxlength="30" required>
            <span class="hint">@lang('connect.first_name_only')</span></div>
        <div class="field"><label>@lang('search.city')</label>
            <input class="inp" name="city" value="{{ $cp->city }}" required>
            <span class="hint">@lang('connect.city_only')</span></div>
    </div>

    {{-- Intentions are matched on, and weighted most heavily. It is what keeps
         this product coherent and stops it drifting into a general dating app. --}}
    <div class="field"><label>@lang('connect.intentions')</label>
        <select class="inp" name="intentions">
            @foreach (['MARRIAGE_WITHIN_YEAR','SERIOUS_RELATIONSHIP','GETTING_TO_KNOW'] as $i)
                <option value="{{ $i }}" @selected($cp->intentions === $i)>@lang('enum.intentions.'.$i)</option>
            @endforeach
        </select></div>

    <div class="field"><label>@lang('connect.bio')</label>
        <textarea class="inp" name="bio" rows="3" maxlength="300">{{ $cp->bio }}</textarea></div>

    <div class="field"><label>@lang('connect.prompts')</label>
        @foreach (['partner', 'friday', 'proud'] as $key)
            <div class="stack g4" style="margin-bottom:10px">
                <span class="lbl">@lang("connect.prompt_{$key}")</span>
                <input class="inp" name="prompts[{{ $key }}]"
                       value="{{ $prompts->firstWhere('question_key', $key)?->answer }}" maxlength="200">
            </div>
        @endforeach
    </div>

    <div class="field"><label>@lang('connect.photo_visibility')</label>
        <select class="inp" name="photo_visibility">
            <option value="BLURRED_UNTIL_MATCH" @selected($cp->photo_visibility === 'BLURRED_UNTIL_MATCH')>@lang('connect.blurred_default')</option>
            <option value="VISIBLE_TO_SUGGESTIONS" @selected($cp->photo_visibility === 'VISIBLE_TO_SUGGESTIONS')>@lang('connect.visible')</option>
        </select></div>

    <button class="btn">@lang('common.save')</button>
</form>
@endsection
