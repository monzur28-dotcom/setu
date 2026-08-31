@extends('layouts.app')
@section('title', __('nav.interests'))
@section('noindex', true)
@section('crumb', 'Member · Interests')

@section('content')
<div class="hd"><h1>@lang('nav.interests')</h1></div>

<div class="tabs">
    @foreach (['received', 'sent', 'accepted'] as $t)
        <a class="tab {{ $tab === $t ? 'on' : '' }}" href="{{ route('member.interests', ['tab' => $t]) }}">
            @lang("interest.tab_{$t}") ({{ $counts[$t] }})
        </a>
    @endforeach
</div>

<div class="note brand">
    <span class="t">@lang('interest.free_title')</span>
    @lang('interest.free_body')
</div>

<div class="stack g12 sec">
    @forelse ($interests as $i)
        @php $other = $tab === 'sent' ? $i->to : $i->from; @endphp
        <article class="card pad row g16 wrap center">
            <div style="width:78px">
                @include('partials.photo', ['name' => $other->displayName(), 'blurred' => true, 'size' => 78])
            </div>
            <div class="grow" style="min-width:180px">
                <div class="serif" style="font-size:18px;color:var(--ink)">{{ $other->displayName() }}</div>
                <div class="sm muted">{{ $other->age }} · {{ $other->career?->profession }} · {{ $other->location?->district?->name() }}</div>
                <div class="mono xs muted" style="margin-top:4px">{{ $other->user->profile_id }}</div>
            </div>
            @if ($tab === 'received')
                <div class="row g8">
                    <form method="POST" action="{{ route('member.interest.respond', $i) }}">@csrf @method('PATCH')
                        <input type="hidden" name="action" value="accept">
                        <button class="btn sm">@lang('interest.accept')</button>
                    </form>
                    <form method="POST" action="{{ route('member.interest.respond', $i) }}">@csrf @method('PATCH')
                        <input type="hidden" name="action" value="decline">
                        <button class="btn sm ghost">@lang('interest.decline')</button>
                    </form>
                    <a class="btn quiet sm" href="{{ route('public.profile', $other->user->profile_id) }}">@lang('common.view')</a>
                </div>
            @else
                {{-- The sender sees CLOSED, never "declined". They are not told
                     whether it was a decline or an expiry. Spec 16.2. --}}
                <span class="chip">@lang('enum.interest.'.$i->statusForSender())</span>
            @endif
        </article>
    @empty
        <div class="card pad muted">@lang('interest.none')</div>
    @endforelse
</div>

<div class="sec">{{ $interests->links() }}</div>
@endsection
