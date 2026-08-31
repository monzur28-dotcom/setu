<div class="grid g2" style="gap:10px">
    <div class="field"><label>@lang('search.city')</label><input class="inp" name="city" value="{{ $profile->location?->city }}"></div>
    <div class="field"><label>@lang('profile.area')</label><input class="inp" name="area" value="{{ $profile->location?->area }}">
        <span class="hint">@lang('profile.area_hint')</span></div>
    <div class="field"><label>@lang('search.district')</label>
        <select class="inp" name="district_id">
            <option value="">—</option>
            @foreach ($districts as $d)<option value="{{ $d->id }}" @selected($profile->location?->district_id == $d->id)>{{ $d->name() }}</option>@endforeach
        </select></div>
    <div class="field"><label>@lang('profile.home_district')</label>
        <select class="inp" name="home_district_id">
            <option value="">—</option>
            @foreach ($districts as $d)<option value="{{ $d->id }}" @selected($profile->location?->home_district_id == $d->id)>{{ $d->name() }}</option>@endforeach
        </select></div>
    <div class="field"><label>@lang('search.relocation')</label>
        <select class="inp" name="relocation_intent">
            @foreach (['WILL_RELOCATE','WILL_NOT','PARTNER_RELOCATES','OPEN','UNDECIDED'] as $r)
                <option value="{{ $r }}" @selected($profile->location?->relocation_intent === $r)>@lang('enum.relocation.'.$r)</option>
            @endforeach
        </select>
        <span class="hint">@lang('profile.relocation_hint')</span></div>
</div>
