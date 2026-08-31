@extends('layouts.app')
@section('title', __('nav.access'))
@section('noindex', true)
@section('crumb', 'Member · Access requests')

@section('content')
<div class="hd"><h1>@lang('nav.access')</h1></div>

<div class="stack g12">
    @forelse ($requests as $r)
        <article class="card pad row g16 wrap center">
            <div style="width:78px">
                @include('partials.photo', ['name' => $r->from->displayName(), 'blurred' => true, 'size' => 78])
            </div>
            <div class="grow" style="min-width:220px">
                <div class="serif" style="font-size:18px;color:var(--ink)">
                    {{ $r->from->displayName() }}
                    <span class="mono xs muted">{{ $r->from->user->profile_id }}</span>
                </div>
                <p class="sm">
                    @lang($r->type === 'PHOTOS' ? 'access.photo_request' : 'access.private_request')
                </p>
            </div>
            <div class="row g8">
                <form method="POST" action="{{ route('member.access.respond', $r) }}">@csrf @method('PATCH')
                    <input type="hidden" name="action" value="grant">
                    <button class="btn sm">@lang('access.grant')</button>
                </form>
                <form method="POST" action="{{ route('member.access.respond', $r) }}">@csrf @method('PATCH')
                    <input type="hidden" name="action" value="decline">
                    <button class="btn sm ghost">@lang('access.decline')</button>
                </form>
            </div>
        </article>
    @empty
        <div class="card pad muted">@lang('access.none')</div>
    @endforelse
</div>
@endsection
