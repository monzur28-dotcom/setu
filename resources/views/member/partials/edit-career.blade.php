<div class="grid g2" style="gap:10px">
    <div class="field"><label>@lang('profile.education')</label><input class="inp" name="education_level" value="{{ $profile->career?->education_level }}"></div>
    <div class="field"><label>@lang('profile.education_detail')</label><input class="inp" name="education_detail" value="{{ $profile->career?->education_detail }}"></div>
    <div class="field"><label>@lang('profile.institution')</label><input class="inp" name="institution" value="{{ $profile->career?->institution }}">
        <span class="hint">@lang('profile.never_public')</span></div>
    <div class="field"><label>@lang('profile.profession')</label><input class="inp" name="profession" value="{{ $profile->career?->profession }}"></div>
    <div class="field"><label>@lang('profile.employer')</label><input class="inp" name="employer" value="{{ $profile->career?->employer }}">
        <span class="hint">@lang('profile.never_public')</span></div>
    <div class="field"><label>@lang('profile.income')</label><input class="inp" name="income_band" value="{{ $profile->career?->income_band }}"></div>
</div>
