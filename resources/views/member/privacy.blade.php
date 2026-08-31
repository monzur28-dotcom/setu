@extends('layouts.app')
@section('title', __('privacy.title'))
@section('noindex', true)
@section('crumb', 'Member · Privacy')

@section('content')
<div class="hd">
    <span class="lbl">/me/privacy</span>
    <h1>@lang('privacy.title')</h1>
    <p class="sub">@lang('privacy.sub')</p>
</div>

{{-- The live side-by-side preview. This is not a list of checkboxes: it is
     the screen where the platform's central promise becomes real to the
     member, or does not. Spec 10.2. --}}
<div class="grid g2" style="align-items:start">
    <div class="card" style="border-color:var(--line-strong)">
        <div class="pad" style="border-bottom:1px solid var(--line);background:var(--surface-2)">
            <div class="lbl">@lang('privacy.public_profile')</div>
            <div class="xs muted" style="margin-top:3px">@lang('privacy.public_who')</div>
        </div>
        <div class="pad">
            <div style="width:90px;margin-bottom:12px">
                @include('partials.photo', ['name' => $publicView['display_name'], 'blurred' => ! $visibility->show_photos, 'size' => 90])
            </div>
            @include('partials.sheet', ['rows' => [
                __('profile.profile_id')     => $publicView['profile_id'],
                __('profile.age')            => $publicView['age'] ?? null,
                __('profile.marital_status') => __('enum.marital.'.($publicView['marital_status'] ?? 'NEVER_MARRIED')),
                __('profile.district')       => $publicView['district'] ?? null,
                __('profile.home_district')  => $publicView['home_district'] ?? null,
                __('profile.religion')       => __('enum.religion.'.($publicView['religion'] ?? 'ISLAM')),
                __('profile.name')           => $publicView['display_name'] ?? null,
                __('profile.profession')     => $publicView['profession'] ?? __('profile.hidden'),
            ]])
        </div>
    </div>

    <div class="card" style="border-color:var(--brand)">
        <div class="pad" style="border-bottom:1px solid var(--line);background:var(--brand-tint)">
            <div class="lbl" style="color:var(--brand-deep)">@lang('privacy.private_profile')</div>
            <div class="xs" style="color:var(--brand-deep);opacity:.8;margin-top:3px">@lang('privacy.private_who')</div>
        </div>
        <div class="pad">
            <div style="width:90px;margin-bottom:12px">
                @include('partials.photo', ['name' => $privateView['display_name'], 'blurred' => false, 'size' => 90])
            </div>
            @include('partials.sheet', ['rows' => [
                __('profile.full_name')   => $privateView['full_name'] ?? null,
                __('profile.about')       => $privateView['about_me'] ?? null,
                __('profile.employer')    => $privateView['employer'] ?? null,
                __('profile.institution') => $privateView['institution'] ?? null,
                __('profile.area')        => $privateView['area'] ?? null,
            ]])
            <div class="note" style="margin-top:12px">@lang('profile.contact_never')</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('member.privacy.update') }}" class="card pad stack g12 sec">
    @csrf @method('PATCH')
    <span class="lbl">@lang('privacy.what_is_public')</span>
    @foreach (['show_photos','show_name','show_gender','show_height','show_city','show_profession','show_hobbies'] as $field)
        <label class="row between center" style="cursor:pointer">
            <span class="sm">@lang('privacy.'.$field)</span>
            <input type="checkbox" name="{{ $field }}" value="1" @checked($visibility->$field)>
        </label>
    @endforeach
    <hr class="rule">
    <label class="row between center" style="cursor:pointer">
        <span class="stack">
            <span class="sm b">@lang('privacy.operator_access')</span>
            <span class="xs muted">@lang('privacy.operator_access_hint')</span>
        </span>
        <input type="checkbox" name="allow_operator_access" value="1" @checked($visibility->allow_operator_access)>
    </label>
    <button class="btn">@lang('common.save')</button>
</form>

{{-- Indexing: OFF by default, explicit informed opt-in, plain wording. --}}
<form method="POST" action="{{ route('member.privacy.indexing') }}" class="card pad stack g10 sec">
    @csrf @method('PATCH')
    <label class="row between center" style="cursor:pointer">
        <span class="stack">
            <span class="sm b">@lang('privacy.indexing_title')</span>
            <span class="xs muted">@lang('privacy.indexing_body')</span>
        </span>
        <input type="checkbox" name="public_indexing" value="1"
               @checked(auth()->user()->public_indexing === 'INDEXED') onchange="this.form.submit()">
    </label>
</form>

<form method="POST" action="{{ route('member.privacy.hide') }}" class="card pad stack g10 sec">@csrf
    <span class="lbl">@lang('privacy.hide_from')</span>
    <div class="row g8">
        <input class="inp grow" name="mobile" placeholder="01XXXXXXXXX">
        <button class="btn sm">@lang('common.add')</button>
    </div>
    <div class="hint">@lang('privacy.hide_from_hint')</div>
    @if ($hidden->isNotEmpty())
        <div class="row wrap g6">
            @foreach ($hidden as $h)<span class="chip mono">•••• {{ substr($h->mobile_hash, 0, 6) }}</span>@endforeach
        </div>
    @endif
</form>
@endsection
