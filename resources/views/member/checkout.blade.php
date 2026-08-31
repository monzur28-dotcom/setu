@extends('layouts.app')
@section('title', __('billing.checkout'))
@section('noindex', true)
@section('crumb', 'Member · Checkout')

@section('content')
<div class="hd"><h1>@lang('billing.checkout')</h1></div>

<form method="POST" action="{{ route('billing.initiate', $plan) }}"
      class="grid" style="grid-template-columns:1fr 300px; gap:20px; align-items:start">@csrf
    <div class="stack g16">
        <div class="card pad stack g12">
            <span class="lbl">@lang('billing.method')</span>
            @foreach ([
                ['bkash', 'bKash', __('billing.bkash_hint')],
                ['nagad', 'Nagad', ''],
                ['sslcommerz', __('billing.card'), 'Visa · MasterCard · AmEx'],
                ['stripe', __('billing.international'), 'USD · GBP · CAD'],
            ] as $i => [$value, $label, $hint])
                <label class="card pad row g10 center" style="cursor:pointer">
                    <input type="radio" name="provider" value="{{ $value }}" @checked($i === 0) required>
                    <span class="stack">
                        <span class="b sm">{{ $label }}</span>
                        @if ($hint)<span class="xs muted">{{ $hint }}</span>@endif
                    </span>
                </label>
            @endforeach
        </div>

        @if ($deposit)
            {{-- The success component is a REFUNDABLE DEPOSIT, stated plainly
                 rather than in a footnote. Spec 18.5. --}}
            <div class="card pad stack g10">
                <span class="lbl">@lang('billing.agreement')</span>
                <div class="card pad sm" style="max-height:170px;overflow:auto;background:var(--surface-2)">
                    <p><b>1. @lang('billing.agreement_1_title')</b> — @lang('billing.agreement_1')</p>
                    <p style="margin-top:8px"><b>2. @lang('billing.agreement_2_title')</b> — @lang('billing.agreement_2')</p>
                    <p style="margin-top:8px"><b>3. @lang('billing.agreement_3_title')</b> — @lang('billing.agreement_3')</p>
                </div>
                <label class="row g8 sm"><input type="checkbox" name="agreement" value="1" required> @lang('billing.accept_agreement')</label>
            </div>
        @endif
    </div>

    <div class="card pad stack g10" style="position:sticky;top:78px">
        <span class="lbl">@lang('billing.summary')</span>
        @include('partials.sheet', ['rows' => [
            __('billing.plan')     => app()->getLocale() === 'bn' ? $plan->name_bn : $plan->name_en,
            __('billing.duration') => $plan->duration_days.' '.__('common.days'),
            __('billing.price')    => '৳'.number_format($plan->price),
            __('billing.deposit')  => $deposit ? '৳'.number_format($deposit).' ('.__('billing.refundable').')' : null,
            __('billing.vat')      => '৳'.number_format($vat),
            __('billing.total')    => '৳'.number_format($plan->price + $vat + ($deposit ?? 0)),
        ]])
        <button class="btn lg full">@lang('billing.pay')</button>
        <a class="xs" href="{{ route('plans') }}" style="color:var(--brand)">@lang('billing.refund_policy')</a>
    </div>
</form>
@endsection
