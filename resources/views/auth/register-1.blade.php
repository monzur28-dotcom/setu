@extends('layouts.app')
@section('title', __('auth.register'))
@section('noindex', true)
@section('crumb', 'Auth · Register 1 of 2')

@section('content')
<div class="hd">
    <span class="lbl">@lang('auth.step_1_of_2')</span>
    <h1>@lang('auth.create_profile')</h1>
    <p class="sub">@lang('auth.register_sub')</p>
</div>

<form method="POST" action="{{ route('register.store') }}" class="card pad stack g14" style="max-width:560px">@csrf
    {{-- The registrant relationship is the FIRST question, because a parent
         or sibling is often the one filling this in. Spec 2.4. --}}
    <div class="field">
        <label>@lang('auth.profile_for')</label>
        <select class="inp" name="registrant_relationship" id="rel" onchange="toggleRegistrant()">
            <option value="SELF">@lang('auth.rel_self')</option>
            <option value="MOTHER" @selected(old('registrant_relationship') === 'MOTHER')>@lang('auth.rel_daughter_son')</option>
            <option value="FATHER" @selected(old('registrant_relationship') === 'FATHER')>@lang('auth.rel_father')</option>
            <option value="BROTHER">@lang('auth.rel_brother')</option>
            <option value="SISTER">@lang('auth.rel_sister')</option>
            <option value="RELATIVE">@lang('auth.rel_relative')</option>
        </select>
    </div>

    <div class="field">
        <label>@lang('auth.candidate_name')</label>
        <input class="inp" name="candidate_name" value="{{ old('candidate_name', $prefill['full_name'] ?? '') }}" required>
    </div>

    <div id="registrant" style="display:none">
        <div class="field">
            <label>@lang('auth.your_name')</label>
            <input class="inp" name="registrant_name" value="{{ old('registrant_name') }}">
        </div>
        {{-- The consent gate, explained before they commit, not after. --}}
        <div class="note brand" style="margin-top:12px">
            <span class="t">@lang('auth.consent_gate_title')</span>
            @lang('auth.consent_gate_body')
        </div>
    </div>

    <div class="grid g2" style="gap:10px">
        <div class="field">
            <label>@lang('auth.mobile')</label>
            <div class="row g6">
                {{-- Every dial code, deduplicated: +1 appears once for the US
                     and Canada rather than twice. A member whose country is
                     missing from a sign-up form does not try again. --}}
                <select class="inp" name="country_code" style="width:150px">
                    @foreach (\App\Support\Countries::dialCodes() as $dial => $where)
                        <option value="{{ $dial }}" @selected($dial === old('country_code', config('setu.default_country_code')))>
                            {{ $dial }} · {{ $where }}
                        </option>
                    @endforeach
                </select>
                <input class="inp" name="mobile" value="{{ old('mobile') }}" inputmode="tel" required>
            </div>
        </div>
        <div class="field">
            <label>@lang('auth.email')</label>
            <input class="inp" type="email" name="email" value="{{ old('email') }}">
        </div>
    </div>

    <div class="field">
        <label>@lang('auth.password')</label>
        <input class="inp" type="password" name="password" required minlength="8">
    </div>

    <label class="row g8 sm center">
        <input type="checkbox" name="terms" value="1" required> @lang('auth.accept_terms')
    </label>

    <button class="btn lg">@lang('common.continue')</button>
    <div class="hint">@lang('auth.have_account') <a href="{{ route('login') }}" style="color:var(--brand)">@lang('nav.login')</a></div>
</form>

@push('scripts')
<script>
function toggleRegistrant() {
    document.getElementById('registrant').style.display =
        document.getElementById('rel').value === 'SELF' ? 'none' : 'block';
}
toggleRegistrant();
</script>
@endpush
@endsection
