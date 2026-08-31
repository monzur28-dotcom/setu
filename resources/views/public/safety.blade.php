@extends('layouts.app')
@section('title', __('safety.title'))
@section('crumb', 'Public · Safety')

@section('content')
<div class="hd"><h1>@lang('safety.title')</h1></div>

<div class="stack g12" style="max-width:70ch">
    <div class="note bad"><span class="t">@lang('safety.never_money_title')</span>@lang('safety.never_money')</div>
    <div class="note"><span class="t">@lang('safety.dowry_title')</span>@lang('safety.dowry')</div>

    @foreach (['meet_public', 'never_call', 'watermark', 'blocking'] as $k)
        <div class="card pad stack g4">
            <span class="b sm">@lang("safety.{$k}_title")</span>
            <span class="sm muted">@lang("safety.{$k}_body")</span>
        </div>
    @endforeach

    <div class="card pad stack g6">
        <span class="lbl">@lang('safety.reach_us')</span>
        <div class="sm">@lang('safety.helpline'): <span class="mono">+880 17XX-XXXXXX</span></div>
    </div>
</div>
@endsection
