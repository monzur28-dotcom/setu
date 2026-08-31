@extends('layouts.app')
@section('title', __('ads.place'))
@section('crumb', 'Tools · Place an ad')

@section('content')
<div class="hd"><h1>@lang('ads.place')</h1></div>

{{-- Two prominent warnings, one directly above the phone field.
     A classified ad is public "just like a newspaper" and the poster must
     understand that before, not after. Spec 9.2. --}}
<div class="note bad">
    <span class="t">@lang('ads.public_warning_title')</span>
    @lang('ads.public_warning_body')
</div>

<form method="POST" action="{{ route('classifieds.store') }}" class="card pad stack g12 sec" style="max-width:620px">@csrf
    <div class="grid g2" style="gap:10px">
        <div class="field"><label>@lang('search.looking_for')</label>
            <select class="inp" name="looking_for">
                <option value="GROOM">@lang('ads.groom_wanted')</option>
                <option value="BRIDE">@lang('ads.bride_wanted')</option>
            </select></div>
        <div class="field"><label>@lang('search.district')</label>
            <select class="inp" name="district_id">
                <option value="">—</option>
                @foreach ($districts as $d)<option value="{{ $d->id }}">{{ $d->name() }}</option>@endforeach
            </select></div>
        <div class="field"><label>@lang('profile.age')</label><input class="inp" name="age" inputmode="numeric"></div>
        <div class="field"><label>@lang('profile.education')</label><input class="inp" name="education"></div>
    </div>
    <div class="field"><label>@lang('ads.headline')</label><input class="inp" name="headline" required></div>
    <div class="field"><label>@lang('ads.body')</label><textarea class="inp" name="body" rows="4" required></textarea></div>

    <div class="note bad" style="margin:2px 0">@lang('ads.phone_warning')</div>
    <div class="field"><label>@lang('ads.contact')</label><input class="inp" name="contact_phone" required></div>

    <label class="row g8 sm"><input type="checkbox" name="no_media_flag" value="1" checked> @lang('ads.mark_no_media')</label>
    <div class="hint">@lang('ads.dowry_notice')</div>

    <button class="btn">@lang('ads.submit')</button>
</form>
@endsection
