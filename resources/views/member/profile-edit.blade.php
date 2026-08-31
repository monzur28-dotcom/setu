@extends('layouts.app')
@section('title', __('profile.edit'))
@section('noindex', true)
@section('crumb', 'Member · Profile')

@section('content')
<div class="hd">
    <h1>@lang('profile.edit')</h1>
    <p class="sub"><a href="{{ route('member.profile.preview') }}" style="color:var(--brand)">@lang('profile.see_what_others_see')</a></p>
</div>

<div class="tabs">
    @foreach (['basic','location','career','family','lifestyle','photos'] as $t)
        <a class="tab {{ $tab === $t ? 'on' : '' }}" href="{{ route('member.profile.edit', $t) }}">@lang('profile.tab_'.$t)</a>
    @endforeach
</div>

@if ($tab === 'photos')
    <form method="POST" action="{{ route('member.photo.store') }}" enctype="multipart/form-data"
          class="card pad row g8 center">@csrf
        <input class="inp" type="file" name="photo" accept="image/*" required>
        <button class="btn sm">@lang('common.upload')</button>
    </form>
    <div class="grid g4c sec">
        @foreach ($profile->photos as $photo)
            <div class="card pad stack g8">
                @include('partials.photo', ['name' => '?', 'blurred' => $photo->status !== 'APPROVED', 'size' => 120])
                <span class="chip {{ $photo->status === 'APPROVED' ? 'ok' : '' }}">@lang('enum.photo.'.$photo->status)</span>
                <form method="POST" action="{{ route('member.photo.destroy', $photo) }}">@csrf @method('DELETE')
                    <button class="btn quiet sm">@lang('common.remove')</button>
                </form>
            </div>
        @endforeach
    </div>
    <div class="note sec"><span class="t">@lang('profile.photo_note_title')</span>@lang('profile.photo_note_body')</div>
@else
    <form method="POST" action="{{ route('member.profile.update', $tab) }}" class="card pad stack g12" style="max-width:660px">
        @csrf @method('PATCH')
        @includeIf('member.partials.edit-'.$tab, ['profile' => $profile, 'districts' => $districts])
        <button class="btn">@lang('common.save')</button>
    </form>
@endif
@endsection
