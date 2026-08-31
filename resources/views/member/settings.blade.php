@extends('layouts.app')
@section('title', __('nav.settings'))
@section('noindex', true)
@section('content')
<div class="hd"><h1>@lang('nav.settings')</h1></div>

<form method="POST" action="{{ route('member.settings.update') }}" class="card pad stack g12" style="max-width:560px">
    @csrf @method('PATCH')
    <div class="field"><label>@lang('settings.language')</label>
        <select class="inp" name="locale">
            <option value="bn" @selected($user->locale === 'bn')>বাংলা</option>
            <option value="en" @selected($user->locale === 'en')>English</option>
        </select></div>
    <div class="field"><label>@lang('settings.currency')</label>
        <select class="inp" name="currency">
            @foreach (['BDT','USD','GBP','CAD','AED'] as $c)
                <option value="{{ $c }}" @selected($user->currency === $c)>{{ $c }}</option>
            @endforeach
        </select></div>
    <button class="btn">@lang('common.save')</button>
</form>

<div class="card pad stack g10 sec" style="max-width:560px">
    <span class="lbl">@lang('settings.pause_title')</span>
    <p class="sm muted">@lang('settings.pause_body')</p>
    @if ($user->status === 'PAUSED')
        <form method="POST" action="{{ route('member.resume') }}">@csrf<button class="btn ghost">@lang('settings.resume')</button></form>
    @else
        <form method="POST" action="{{ route('member.pause') }}">@csrf<button class="btn ghost">@lang('settings.pause')</button></form>
    @endif
</div>

<form method="POST" action="{{ route('member.destroy') }}" class="card pad stack g10 sec" style="max-width:560px">
    @csrf @method('DELETE')
    <span class="lbl">@lang('settings.delete_title')</span>
    <p class="sm muted">@lang('settings.delete_body')</p>
    <button class="btn danger">@lang('settings.delete')</button>
</form>
@endsection
