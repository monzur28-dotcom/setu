@extends('layouts.app')
@section('title', $page->h1)
@section('meta_description', Str::limit(strip_tags($page->intro() ?? ''), 155))
@section('crumb', 'Public · Landing')

@section('content')
<div class="hd">
    <span class="lbl">/{{ $page->slug }}</span>
    <h1>{{ $page->h1 }}</h1>
    <p class="sub">{{ trans_choice('search.count', $count, ['n' => number_format($count)]) }} · @lang('landing.updated_hourly')</p>
</div>

{{-- 150–250 words of UNIQUE prose per page. Not a template with a district
     swapped in — that is what turns a page network into a penalty. --}}
<p class="sm" style="max-width:70ch">{{ $page->intro() }}</p>

@if ($profiles->isNotEmpty())
    <div class="grid g4c sec">
        @foreach ($profiles as $p)
            @include('partials.profile-card', ['p' => $p])
        @endforeach
    </div>
@else
    <div class="note sec">@lang('landing.empty')</div>
@endif

@if ($page->faq_json)
    <div class="sec">
        <span class="lbl">@lang('common.faq')</span>
        @foreach ($page->faq_json as $item)
            <div class="card pad stack g4" style="margin-bottom:10px">
                <span class="b sm">{{ $item['q'] ?? '' }}</span>
                <span class="sm muted">{{ $item['a'] ?? '' }}</span>
            </div>
        @endforeach
    </div>
@endif

@if ($page->internal_links)
    <div class="sec">
        <span class="lbl">@lang('landing.related')</span>
        <div class="row wrap g8">
            @foreach ($page->internal_links as $link)
                <a class="chip" href="{{ url($link['slug'] ?? '#') }}">{{ $link['label'] ?? '' }}</a>
            @endforeach
        </div>
    </div>
@endif
@endsection
