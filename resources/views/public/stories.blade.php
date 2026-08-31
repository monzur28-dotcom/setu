@extends('layouts.app')
@section('title', __('stories.title'))
@section('crumb', 'Public · Stories')
@section('content')
<div class="hd"><h1>@lang('stories.title')</h1><p class="sub">@lang('stories.consent_note')</p></div>
<div class="grid g3">
    @forelse ($stories as $s)
        <div class="card pad stack g8">
            <div class="sm">{{ app()->getLocale() === 'bn' ? $s->body_bn : $s->body_en }}</div>
            <div class="xs muted">{{ $s->city }}@if ($s->weeks_to_connect) · {{ $s->weeks_to_connect }} @lang('stories.weeks')@endif</div>
        </div>
    @empty
        <div class="card pad muted" style="grid-column:1/-1">@lang('stories.none')</div>
    @endforelse
</div>
<div class="sec">{{ $stories->links() }}</div>
@endsection
