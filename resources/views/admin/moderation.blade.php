@extends('layouts.app')
@section('title', __('admin.moderation'))
@section('noindex', true)
@section('content')
<div class="hd">
    <h1>@lang('admin.moderation')</h1>
    <span class="sub">@lang('admin.moderation_sub')</span>
</div>

{{-- Mode-scoped: Connect content is reviewed under Connect policy, and
     matrimonial content under matrimonial policy. Spec 13.2. --}}
<div class="tabs">
    @foreach (['MATRIMONIAL', 'CONNECT'] as $m)
        <a class="tab {{ $mode === $m ? 'on' : '' }}" href="{{ route('admin.moderation', ['mode' => $m]) }}">
            @lang('enum.mode.'.$m)
        </a>
    @endforeach
</div>

<div class="card scrollx">
    <table class="tbl">
        <thead><tr><th>@lang('admin.item')</th><th>@lang('admin.priority')</th><th>@lang('common.when')</th><th></th></tr></thead>
        <tbody>
        @forelse ($items as $item)
            @php
                $profile = $item->entity_type === 'PROFILE' ? ($profiles[$item->entity_id] ?? null) : null;
                $matched = $item->matched_words ? json_decode($item->matched_words, true) : [];
            @endphp
            <tr>
                <td>
                    <div class="b sm">{{ $item->entity_type }} · <span class="mono">{{ $item->entity_id }}</span></div>

                    @if ($profile)
                        {{-- The moderator reads the pending copy where one is
                             waiting, the approved copy otherwise. No decision
                             should ever be made against an id alone. --}}
                        <div class="xs muted mono">{{ $profile->user->profile_id }}</div>

                        @foreach ($review->textUnderReview($profile) as $field => $text)
                            <div class="mod-field">
                                <span class="lbl xs">@lang('profile.'.$field)</span>
                                <p class="sm">{{ $text }}</p>
                            </div>
                        @endforeach

                        @if ($profile->moderation_status === 'APPROVED')
                            <span class="chip xs">@lang('admin.live_while_pending')</span>
                        @endif
                    @endif

                    @if ($matched)
                        {{-- Flagged, not judged: the list decides the order of
                             the queue, a person decides the outcome. --}}
                        <div class="row wrap g4" style="margin-top:6px">
                            <span class="chip bad xs">@lang('admin.word_flagged')</span>
                            @foreach ($matched as $w)
                                <span class="chip xs mono">{{ $w }}</span>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td><span class="chip {{ $item->priority <= 2 ? 'bad' : '' }}">P{{ $item->priority }}</span></td>
                <td class="mono">{{ $item->created_at->diffForHumans() }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.moderation.decide', $item) }}" class="row g6">@csrf
                        <input class="inp sm" name="reason" placeholder="@lang('admin.reason')" style="width:150px">
                        <button class="btn sm" name="decision" value="approve">@lang('admin.approve')</button>
                        <button class="btn sm ghost" name="decision" value="reject">@lang('admin.reject')</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">@lang('admin.queue_empty')</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
