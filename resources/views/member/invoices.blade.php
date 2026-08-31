@extends('layouts.app')
@section('title', __('billing.invoices'))
@section('noindex', true)
@section('content')
<div class="hd"><h1>@lang('billing.invoices')</h1></div>
<div class="card scrollx">
    <table class="tbl">
        <thead><tr><th>@lang('common.when')</th><th>@lang('billing.plan')</th><th>@lang('billing.total')</th><th>@lang('common.status')</th></tr></thead>
        <tbody>
        @forelse ($transactions as $t)
            <tr>
                <td class="mono">{{ $t->created_at->toDateString() }}</td>
                <td>{{ $t->statement_descriptor }}</td>
                <td class="mono">৳{{ number_format($t->amount) }}</td>
                <td><span class="chip ok">{{ $t->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">@lang('billing.no_invoices')</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
