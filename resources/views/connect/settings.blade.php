@extends('layouts.connect')
@section('title', __('nav.settings'))
@section('crumb', 'Connect · Settings')

@section('content')
<div class="hd"><h1>@lang('connect.settings')</h1></div>

<div class="card pad stack g12" style="max-width:620px">
    @include('partials.sheet', ['rows' => [
        __('connect.photo_visibility') => __('connect.'.($cp->photo_visibility === 'BLURRED_UNTIL_MATCH' ? 'blurred_default' : 'visible')),
        __('connect.location_shown')   => $cp->city.' — '.__('connect.city_only'),
        __('connect.notifications')    => __('connect.push_only'),
    ]])
    <div class="hint">@lang('connect.sms_note')</div>
    <a class="btn ghost sm" href="{{ route('connect.profile.edit') }}">@lang('common.edit')</a>
</div>

{{-- Deleting Connect leaves the marriage account completely intact.
     One action, no reason required. Spec 27.1 W10. --}}
<form method="POST" action="{{ route('connect.destroy') }}" class="card pad stack g10 sec" style="max-width:620px">
    @csrf @method('DELETE')
    <span class="lbl">@lang('connect.delete_title')</span>
    <p class="sm muted">@lang('connect.delete_body')</p>
    <button class="btn danger">@lang('connect.delete')</button>
</form>
@endsection
