@extends('layouts.app')
@section('title', __('profile.see_what_others_see'))
@section('noindex', true)
@section('content')
<div class="hd">
    <h1>@lang('profile.see_what_others_see')</h1>
    <p class="sub">@lang('profile.preview_sub')</p>
</div>
<div class="grid g2" style="align-items:start">
    <div class="card pad">
        <div class="lbl" style="margin-bottom:10px">@lang('privacy.public_profile')</div>
        <pre class="mono xs" style="white-space:pre-wrap">{{ json_encode($public, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
    <div class="card pad" style="border-color:var(--brand)">
        <div class="lbl" style="margin-bottom:10px">@lang('privacy.private_profile')</div>
        <pre class="mono xs" style="white-space:pre-wrap">{{ json_encode($private, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
<div class="note sec">@lang('profile.preview_note')</div>
@endsection
