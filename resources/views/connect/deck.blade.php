@extends('layouts.connect')
@section('title', __('connect.suggestions'))
@section('crumb', 'Connect · Suggestions')

@section('content')
<div class="hd">
    <h1>@lang('connect.todays_suggestions')</h1>
    <p class="sub">@lang('connect.deck_sub')</p>
</div>

@if ($candidate)
    <div class="deck">
        <article class="deckcard">
            <div class="hero">
                @include('partials.photo', [
                    'name' => $candidate->display_name,
                    'blurred' => ! $candidate->photosVisibleTo($cp->id),
                    'url' => $candidate->photos->first()?->url(! $candidate->photosVisibleTo($cp->id)),
                    'size' => 340,
                ])
            </div>
            <div class="pad stack g10">
                <div class="row between center">
                    <div>
                        <span class="serif" style="font-size:22px;color:var(--ink)">{{ $candidate->display_name }}, {{ $candidate->age }}</span>
                        <div class="sm muted">{{ $candidate->city }}</div>
                    </div>
                    <span class="chip ok">◆ @lang('profile.verified')</span>
                </div>
                <div class="chip brand" style="align-self:flex-start">@lang('enum.intentions.'.$candidate->intentions)</div>
                <hr class="rule">
                @foreach ($candidate->prompts as $prompt)
                    <div class="stack g2">
                        <span class="lbl">@lang('connect.prompt_'.$prompt->question_key)</span>
                        <span class="serif" style="font-size:16px;color:var(--ink);line-height:1.4">{{ $prompt->answer }}</span>
                    </div>
                @endforeach
                @if ($candidate->bio)<p class="sm">{{ $candidate->bio }}</p>@endif
                <div class="xs muted">@lang('connect.blur_note')</div>
            </div>

            {{-- Two actions of equal weight. A "pass" that feels harder to
                 press than "interested" corrupts the data. --}}
            <div class="row g8 pad" style="border-top:1px solid var(--line)">
                <form method="POST" action="{{ route('connect.act', $candidate) }}" class="grow">@csrf
                    <input type="hidden" name="action" value="pass">
                    <button class="btn ghost full">@lang('connect.pass')</button>
                </form>
                <form method="POST" action="{{ route('connect.act', $candidate) }}" class="grow">@csrf
                    <input type="hidden" name="action" value="like">
                    <button class="btn full">@lang('connect.like')</button>
                </form>
            </div>
        </article>

        <div class="row between center sm muted" style="margin-top:12px">
            <span>@lang('connect.likes_left'): <b class="mono">{{ $remaining ?? '∞' }}</b></span>
            <a class="btn quiet sm" href="{{ route('connect.plans') }}">@lang('connect.get_unlimited')</a>
        </div>
    </div>
@else
    <div class="card pad" style="max-width:400px;margin:0 auto;text-align:center">
        <div class="b">@lang('connect.deck_empty_title')</div>
        <p class="sm muted" style="margin-top:6px">@lang('connect.deck_empty_body')</p>
    </div>
@endif
@endsection
