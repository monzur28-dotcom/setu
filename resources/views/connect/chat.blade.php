@extends('layouts.connect')
@section('title', $other?->display_name)
@section('crumb', 'Connect · Chat')

@section('content')
<div class="hd">
    <h1>{{ $other?->display_name }}, {{ $other?->age }}</h1>
    <p class="sub">{{ $other?->city }} · @lang('enum.intentions.'.($other?->intentions ?? 'SERIOUS_RELATIONSHIP'))</p>
</div>

<div class="card stack" style="max-width:560px;min-height:420px">
    <div class="pad row between center" style="border-bottom:1px solid var(--line)">
        <div class="row g10 center">
            <span style="width:34px">
                @include('partials.photo', ['name' => $other?->display_name ?? '?', 'blurred' => false, 'size' => 34])
            </span>
            <span class="b sm">{{ $other?->display_name }}</span>
        </div>
        <div class="row g6">
            <form method="POST" action="{{ route('connect.unmatch', $match) }}">@csrf
                <button class="btn quiet sm">@lang('connect.unmatch')</button>
            </form>
            {{-- Absolute and silent. The blocked person is never told. --}}
            <form method="POST" action="{{ route('connect.block', $other) }}">@csrf
                <button class="btn quiet sm">@lang('connect.block')</button>
            </form>
        </div>
    </div>

    <div class="pad stack g10 grow" style="justify-content:flex-end">
        <div class="card pad xs muted" style="text-align:center;background:var(--surface-2)">@lang('connect.matched_note')</div>
        @foreach ($match->messages as $m)
            <div class="msg {{ $m->sender_connect_id === $cp->id ? 'me' : 'them' }}">{{ $m->body }}</div>
            @if ($m->is_filtered)
                <div class="card pad xs muted" style="text-align:center;background:var(--surface-2)">@lang('mailbox.filtered')</div>
            @endif
        @endforeach
    </div>

    <form method="POST" action="{{ route('connect.chat.send', $match) }}" class="pad row g8"
          style="border-top:1px solid var(--line)">@csrf
        <input class="inp grow" name="body" placeholder="@lang('mailbox.write')" required>
        <button class="btn sm">@lang('mailbox.send')</button>
    </form>
</div>

<div class="note sec" style="max-width:560px">
    <span class="t">@lang('connect.no_images_title')</span>
    @lang('connect.no_images_body')
</div>
@endsection
