<div class="grid g2" style="gap:10px">
    <div class="field"><label>@lang('profile.height')</label><input class="inp" name="height_cm" value="{{ $profile->height_cm }}"></div>
    <div class="field"><label>@lang('profile.marital_status')</label>
        <select class="inp" name="marital_status">
            @foreach (['NEVER_MARRIED','DIVORCED','LEGALLY_SEPARATED','WIDOWED'] as $m)
                <option value="{{ $m }}" @selected($profile->marital_status === $m)>@lang('enum.marital.'.$m)</option>
            @endforeach
        </select></div>
    <div class="field"><label>@lang('profile.prayer_habit')</label>
        <select class="inp" name="prayer_habit">
            @foreach (['FIVE_TIMES','REGULARLY','OCCASIONALLY','NOT_PRACTISING','PREFER_NOT_TO_SAY'] as $p)
                <option value="{{ $p }}" @selected($profile->prayer_habit === $p)>@lang('enum.prayer.'.$p)</option>
            @endforeach
        </select></div>
    <div class="field"><label>@lang('profile.timeline')</label>
        <select class="inp" name="marriage_timeline">
            @foreach (['WITHIN_6_MONTHS','WITHIN_A_YEAR','WITHIN_2_YEARS','NO_FIXED_TIMELINE'] as $t)
                <option value="{{ $t }}" @selected($profile->marriage_timeline === $t)>@lang('enum.timeline.'.$t)</option>
            @endforeach
        </select></div>
</div>
<div class="field"><label>@lang('profile.headline')</label><input class="inp" name="headline" value="{{ $profile->headline }}" maxlength="100"></div>
<div class="field"><label>@lang('profile.about')</label><textarea class="inp" name="about_me" rows="4">{{ $profile->about_me }}</textarea>
    <span class="hint">@lang('profile.about_hint')</span></div>
