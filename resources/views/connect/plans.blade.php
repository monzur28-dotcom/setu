@extends('layouts.connect')
@section('title', __('connect.plans'))
@section('crumb', 'Connect · Plans')

@section('content')
<div class="hd"><h1>@lang('connect.plans')</h1><p class="sub">@lang('connect.plans_sub')</p></div>

<div class="grid g3">
    @foreach (config('setu.connect_plans') as $code => $plan)
        <div class="plan {{ $code === 'monthly' ? 'feat' : '' }}">
            @if ($code === 'monthly')<div class="ribbon">@lang('plans.most_chosen')</div>@endif
            <div class="ptop">
                <div class="lbl">@lang('connect.plan_'.$code)</div>
                <div class="price" style="margin-top:6px">৳{{ number_format($plan['price_bdt']) }}</div>
                <div class="per">{{ $plan['days'] ? $plan['days'].' '.__('common.days') : __('plans.forever') }}</div>
            </div>
            <ul>
                <li><span class="tick">✓</span><span>{{ $plan['likes_per_day'] ? $plan['likes_per_day'].' '.__('connect.likes_a_day') : __('connect.unlimited_likes') }}</span></li>
                <li><span class="{{ $plan['see_likers'] ? 'tick' : 'no' }}">{{ $plan['see_likers'] ? '✓' : '✕' }}</span><span>@lang('connect.see_who')</span></li>
                <li><span class="tick">✓</span><span>@lang('connect.match_and_chat')</span></li>
                <li><span class="tick">✓</span><span>@lang('connect.block_report')</span></li>
            </ul>
            <div class="pfoot">
                <button class="btn {{ $code === 'monthly' ? '' : 'ghost' }} full" disabled>@lang('plans.choose')</button>
            </div>
        </div>
    @endforeach
</div>

{{-- The boundary, stated on the pricing page itself. --}}
<div class="note bad sec">
    <span class="t">@lang('connect.not_for_sale_title')</span>
    @lang('connect.not_for_sale_body')
</div>
@endsection
