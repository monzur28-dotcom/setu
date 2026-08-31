@extends('layouts.app')
@section('title', __('admin.success_fees'))
@section('noindex', true)
@section('content')
<div class="hd"><h1>@lang('admin.success_fees')</h1><p class="sub">@lang('admin.two_person')</p></div>
<div class="card scrollx">
    <table class="tbl">
        <thead><tr><th>@lang('operator.case')</th><th>@lang('billing.total')</th><th>@lang('admin.structure')</th><th>@lang('common.status')</th><th></th></tr></thead>
        <tbody>
        @forelse ($fees as $f)
            <tr>
                <td class="mono">#{{ $f->case_id }}</td>
                <td class="mono">৳{{ number_format($f->amount) }}</td>
                <td>{{ $f->structure }}</td>
                <td><span class="chip">{{ $f->status }}</span></td>
                <td>
                    @if (! $f->confirmed_by && $f->recorded_by !== auth()->id())
                        <form method="POST" action="{{ route('admin.fees.confirm', $f) }}">@csrf
                            <button class="btn sm">@lang('admin.confirm')</button>
                        </form>
                    @elseif ($f->recorded_by === auth()->id() && ! $f->confirmed_by)
                        <span class="xs muted">@lang('admin.needs_other_person')</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">@lang('admin.no_fees')</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
