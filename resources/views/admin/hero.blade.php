@extends('layouts.app')
@section('title', __('admin.hero'))
@section('noindex', true)
@section('crumb', 'Staff · Hero')

@section('content')
<div class="hd">
    <h1>@lang('admin.hero')</h1>
    <span class="sub">@lang('admin.hero_sub', ['seconds' => round($interval / 1000)])</span>
</div>

<p class="sec"><a class="btn ghost" href="{{ route('admin.appearance') }}">@lang('admin.appearance')</a></p>

<form method="POST" action="{{ route('admin.hero.add') }}" enctype="multipart/form-data"
      class="card pad row g10 wrap" style="align-items:flex-end">@csrf
    <div class="field grow">
        <label>@lang('admin.hero_image')</label>
        <input class="inp" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <div class="field grow">
        <label>@lang('admin.hero_caption')</label>
        <input class="inp" name="caption" maxlength="120">
    </div>
    <div class="field">
        <label>@lang('admin.hero_section')</label>
        <select class="inp" name="product">
            <option value="BOTH">@lang('admin.hero_both')</option>
            <option value="MATRIMONIAL">@lang('nav.matrimony')</option>
            <option value="CONNECT">@lang('nav.dating')</option>
        </select>
    </div>
    <button class="btn">@lang('admin.hero_add')</button>
    @error('image')<span class="xs bad">{{ $message }}</span>@enderror
</form>

<div class="grid g3 sec">
    @forelse ($slides as $slide)
        <div class="card slide-card">
            <img src="{{ $slide->url() }}" alt="{{ $slide->caption }}" loading="lazy">
            <form method="POST" action="{{ route('admin.hero.update', $slide) }}" class="pad stack g8">
                @csrf @method('PATCH')
                <input class="inp sm" name="caption" value="{{ $slide->caption }}" placeholder="@lang('admin.hero_caption')">
                <div class="row g6">
                    <select class="inp sm" name="product">
                        @foreach (['BOTH' => __('admin.hero_both'), 'MATRIMONIAL' => __('nav.matrimony'), 'CONNECT' => __('nav.dating')] as $v => $label)
                            <option value="{{ $v }}" @selected($slide->product === $v)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input class="inp sm" name="sort_order" value="{{ $slide->sort_order }}" inputmode="numeric" style="width:70px">
                </div>
                <label class="row g6 center sm">
                    <input type="checkbox" name="is_active" value="1" @checked($slide->is_active)>
                    <span>@lang('admin.hero_active')</span>
                </label>
                <div class="row g6">
                    <button class="btn sm grow">@lang('common.save')</button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.hero.remove', $slide) }}" class="pad" style="padding-top:0">
                @csrf @method('DELETE')
                <button class="btn sm ghost full">@lang('common.remove')</button>
            </form>
        </div>
    @empty
        <div class="card pad muted">@lang('admin.hero_empty')</div>
    @endforelse
</div>
@endsection
