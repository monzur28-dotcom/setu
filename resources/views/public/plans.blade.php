@extends('layouts.app')
@section('title', __('plans.title'))
@section('crumb', 'Public · Plans')

@section('content')
<div class="hd">
    <span class="lbl">/plans</span>
    <h1>@lang('plans.title')</h1>
    <p class="sub">@lang('home.free_promise')</p>
</div>

@if (session('paywall'))
    <div class="note brand" style="margin-bottom:18px">
        <span class="t">@lang('plans.paywall_title')</span>
        @lang('plans.paywall_body')
    </div>
@endif

<div class="grid g3">
    @foreach ($plans as $plan)
        @php $feat = $plan->code === 'standard'; @endphp
        <div class="plan {{ $feat ? 'feat' : '' }}">
            @if ($feat)<div class="ribbon">@lang('plans.most_chosen')</div>@endif
            <div class="ptop">
                <div class="lbl">{{ app()->getLocale() === 'bn' ? $plan->name_bn : $plan->name_en }}</div>
                <div class="price" style="margin-top:6px">৳{{ number_format($plan->price) }}</div>
                <div class="per">{{ $plan->duration_days ? trans_choice('plans.months', round($plan->duration_days / 30), ['n' => round($plan->duration_days / 30)]) : __('plans.forever') }}</div>
            </div>
            <ul>
                @foreach ($plan->features['included'] ?? [] as $f)
                    <li><span class="tick">✓</span><span>{{ $f }}</span></li>
                @endforeach
                @foreach ($plan->features['excluded'] ?? [] as $f)
                    <li><span class="no">✕</span><span class="muted">{{ $f }}</span></li>
                @endforeach
            </ul>
            <div class="pfoot">
                @auth
                    @if ($plan->price > 0)
                        <a class="btn {{ $feat ? '' : 'ghost' }} full" href="{{ route('billing.checkout', $plan) }}">@lang('plans.choose')</a>
                    @else
                        <button class="btn ghost full" disabled>@lang('plans.current')</button>
                    @endif
                @else
                    <a class="btn {{ $feat ? '' : 'ghost' }} full" href="{{ route('register') }}">@lang('nav.register_free')</a>
                @endauth
            </div>
        </div>
    @endforeach
</div>

<div class="grid g2 sec">
    <div class="note"><span class="t">@lang('plans.deposit_title')</span>@lang('plans.deposit_body')</div>
    <div class="note brand"><span class="t">@lang('plans.refund_title')</span>@lang('plans.refund_body')</div>
</div>

<div class="sec">
    <span class="lbl">@lang('plans.never_sell_title')</span>
    <div class="card pad sm">@lang('plans.never_sell_body')</div>
</div>
@endsection
