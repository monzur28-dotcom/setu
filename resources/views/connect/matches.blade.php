@extends('layouts.connect')
@section('title', __('connect.matches'))
@section('crumb', 'Connect · Matches')

@section('content')
<div class="hd"><h1>@lang('connect.matches')</h1><p class="sub">@lang('connect.matches_sub')</p></div>

<div class="grid g2" style="max-width:760px">
    <div class="card pad stack g10">
        <span class="lbl">@lang('connect.liked_you')</span>
        <div class="row center g12">
            <span class="serif" style="font-size:34px;color:var(--ink)">{{ $likerCount }}</span>
            @unless ($seeLikers)
                {{-- A count, never an identity. The people behind this number
                     have not been seen by this member, and telling them who
                     would expose a one-sided choice. Spec 27.3 S2. --}}
                <span class="sm muted">@lang('connect.likers_hidden')</span>
            @endunless
        </div>
        @unless ($seeLikers)
            <a class="btn sm ghost" href="{{ route('connect.plans') }}">@lang('connect.see_who')</a>
        @endunless
    </div>

    <div class="card pad stack g10">
        <span class="lbl">@lang('connect.your_matches')</span>
        @forelse ($matches as $row)
            <a href="{{ route('connect.chat', $row['match']) }}" class="row g10 center"
               style="padding:6px 0;text-decoration:none">
                <span style="width:44px;flex:none">
                    @include('partials.photo', ['name' => $row['other']?->display_name ?? '?', 'blurred' => false, 'size' => 44])
                </span>
                <span class="stack g2">
                    <span class="b sm">{{ $row['other']?->display_name }}, {{ $row['other']?->age }}</span>
                    <span class="xs muted">{{ $row['other']?->city }}</span>
                </span>
            </a>
        @empty
            <div class="muted sm">@lang('connect.no_matches')</div>
        @endforelse
    </div>
</div>

<div class="note brand sec" style="max-width:760px">
    <span class="t">@lang('connect.nothing_onesided_title')</span>
    @lang('connect.nothing_onesided_body')
</div>
@endsection
