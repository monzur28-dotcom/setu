@extends('layouts.app')
@section('title', __('search.title'))
@section('crumb', 'Public · Search')

@section('content')
<div class="hd">
    <span class="lbl">/search</span>
    <h1>@lang('search.title')</h1>
    <p class="sub">{{ trans_choice('search.count', $results->total(), ['n' => number_format($results->total())]) }}</p>
</div>

<div class="grid g4c">
    @forelse ($cards as $p)
        @include('partials.profile-card', ['p' => $p])
    @empty
        <div class="card pad" style="grid-column:1/-1">
            <div class="b">@lang('search.no_results')</div>
            <p class="sm muted">@lang('search.relax_hint')</p>
        </div>
    @endforelse
</div>

<div class="sec">{{ $results->links() }}</div>
@endsection
