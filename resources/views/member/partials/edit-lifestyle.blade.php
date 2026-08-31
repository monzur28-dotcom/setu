<div class="grid g3" style="gap:10px">
    <div class="field"><label>@lang('profile.diet')</label>
        <select class="inp" name="diet">
            <option value="">—</option>
            @foreach (['HALAL_ONLY','VEGETARIAN','NON_VEGETARIAN','OTHER'] as $d)
                <option value="{{ $d }}" @selected($profile->lifestyle?->diet === $d)>@lang('enum.diet.'.$d)</option>
            @endforeach
        </select></div>
    @foreach (['smoking', 'drinking'] as $k)
        <div class="field"><label>@lang('profile.'.$k)</label>
            <select class="inp" name="{{ $k }}">
                @foreach (['NO','OCCASIONALLY','YES'] as $v)
                    <option value="{{ $v }}" @selected($profile->lifestyle?->$k === $v)>@lang('enum.yesno.'.$v)</option>
                @endforeach
            </select></div>
    @endforeach
</div>
