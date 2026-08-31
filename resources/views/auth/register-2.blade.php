@extends('layouts.app')
@section('title', __('auth.about_candidate'))
@section('noindex', true)
@section('crumb', 'Auth · Register 2 of 2')

@section('content')
<div class="hd">
    <span class="lbl">@lang('auth.step_2_of_2')</span>
    <h1>@lang('auth.about_candidate')</h1>
</div>

<form method="POST" action="{{ route('register.step2.store') }}" enctype="multipart/form-data" class="card pad stack g14" style="max-width:660px">@csrf
    <div class="grid g3" style="gap:10px">
        <div class="field"><label>@lang('profile.gender')</label>
            <select class="inp" name="gender" required>
                <option value="FEMALE">@lang('enum.gender.FEMALE')</option>
                <option value="MALE">@lang('enum.gender.MALE')</option>
            </select></div>
        <div class="field"><label>@lang('biodata.dob')</label>
            <input class="inp" type="date" name="date_of_birth" required></div>
        <div class="field"><label>@lang('profile.height')</label>
            <input class="inp" name="height_cm" inputmode="numeric" placeholder="160"></div>

        <div class="field"><label>@lang('profile.marital_status')</label>
            <select class="inp" name="marital_status" required>
                @foreach (['NEVER_MARRIED','DIVORCED','LEGALLY_SEPARATED','WIDOWED'] as $m)
                    <option value="{{ $m }}">@lang('enum.marital.'.$m)</option>
                @endforeach
            </select></div>
        <div class="field"><label>@lang('profile.religion')</label>
            <select class="inp" name="religion" required>
                @foreach (['ISLAM','HINDUISM','CHRISTIANITY','BUDDHISM','OTHER'] as $r)
                    <option value="{{ $r }}">@lang('enum.religion.'.$r)</option>
                @endforeach
            </select></div>
        <div class="field"><label>@lang('search.country')</label>
            <select class="inp" name="country" required>
                <option value="BD">Bangladesh</option><option value="GB">United Kingdom</option>
                <option value="US">United States</option><option value="CA">Canada</option>
                <option value="AE">UAE</option><option value="AU">Australia</option>
            </select></div>

        <div class="field"><label>@lang('search.city')</label><input class="inp" name="city"></div>
        <div class="field"><label>@lang('search.district')</label>
            <select class="inp" name="district_id">
                <option value="">—</option>
                @foreach ($districts as $d)<option value="{{ $d->id }}">{{ $d->name() }}</option>@endforeach
            </select></div>
        {{-- The "desher bari" question, asked in every match conversation. --}}
        <div class="field"><label>@lang('profile.home_district')</label>
            <select class="inp" name="home_district_id">
                <option value="">—</option>
                @foreach ($districts as $d)<option value="{{ $d->id }}">{{ $d->name() }}</option>@endforeach
            </select></div>

        <div class="field"><label>@lang('profile.profession')</label><input class="inp" name="profession"></div>
        <div class="field"><label>@lang('profile.education')</label>
            <select class="inp" name="education_level">
                <option value="">—</option>
                @foreach (['SSC','HSC','DIPLOMA','BACHELOR','MASTER','MBBS','ENGINEERING','MPHIL','PHD','DAKHIL','ALIM','FAZIL','KAMIL'] as $e)
                    <option value="{{ $e }}">{{ $e }}</option>
                @endforeach
            </select></div>
    </div>

    {{--
        Required, not optional. A profile with no photograph is the single
        biggest cause of unanswered interests, and it is the one field a
        member never comes back to add. Uploading it is not publishing it:
        the photo goes to moderation, and the privacy screen on the next
        step decides who ever sees it.
    --}}
    <div class="field">
        <label>@lang('auth.photo') <span class="req">*</span></label>
        <input class="inp" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
        <span class="xs muted">@lang('auth.photo_help')</span>
        @error('photo')<span class="xs bad">{{ $message }}</span>@enderror
    </div>
    <button class="btn lg">@lang('auth.save_and_privacy')</button>
</form>
@endsection
