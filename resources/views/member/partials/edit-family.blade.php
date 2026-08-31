<div class="grid g2" style="gap:10px">
    <div class="field"><label>@lang('biodata.father')</label><input class="inp" name="father_occupation" value="{{ $profile->family?->father_occupation }}"></div>
    <div class="field"><label>@lang('biodata.mother')</label><input class="inp" name="mother_occupation" value="{{ $profile->family?->mother_occupation }}"></div>
    <div class="field"><label>@lang('profile.family_type')</label>
        <select class="inp" name="family_type">
            <option value="">—</option>
            @foreach (['JOINT','NUCLEAR'] as $f)<option value="{{ $f }}" @selected($profile->family?->family_type === $f)>@lang('enum.family_type.'.$f)</option>@endforeach
        </select></div>
    <div class="field"><label>@lang('profile.family_values')</label>
        <select class="inp" name="family_values">
            <option value="">—</option>
            @foreach (['TRADITIONAL','MODERATE','LIBERAL'] as $f)<option value="{{ $f }}" @selected($profile->family?->family_values === $f)>@lang('enum.family_values.'.$f)</option>@endforeach
        </select></div>
</div>
{{-- Two people can match on every demographic field and still be incompatible
     because one expects their parents to decide. Asking directly prevents a
     category of failure no amount of biodata matching catches. Spec 14.6. --}}
<div class="field"><label>@lang('profile.family_involvement')</label>
    <select class="inp" name="family_involvement">
        <option value="">—</option>
        @foreach (['FAMILY_LED','FAMILY_INVOLVED','MY_DECISION_FAMILY_INFORMED','MY_DECISION'] as $f)
            <option value="{{ $f }}" @selected($profile->family?->family_involvement === $f)>@lang('enum.involvement.'.$f)</option>
        @endforeach
    </select></div>
<div class="field"><label>@lang('profile.about_family')</label><textarea class="inp" name="about_family" rows="3">{{ $profile->family?->about_family }}</textarea></div>
