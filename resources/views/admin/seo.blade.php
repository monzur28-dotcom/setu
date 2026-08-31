@extends('layouts.app')
@section('title', __('admin.seo'))
@section('noindex', true)
@section('content')
<div class="hd"><h1>@lang('admin.seo')</h1><p class="sub">@lang('admin.seo_sub')</p></div>

<form method="POST" action="{{ route('admin.seo.refresh') }}">@csrf
    <button class="btn ghost sm">@lang('admin.refresh_counts')</button>
</form>

<div class="card scrollx sec">
    <table class="tbl">
        <thead><tr><th>@lang('admin.slug')</th><th>@lang('admin.matches')</th><th>@lang('admin.index_status')</th><th>@lang('admin.updated')</th></tr></thead>
        <tbody>
        @forelse ($pages as $p)
            <tr>
                <td class="mono">/{{ $p->slug }}</td>
                <td class="mono">{{ $p->match_count }}</td>
                <td>
                    <span class="chip {{ $p->shouldIndex() ? 'ok' : 'bad' }}">
                        {{ $p->shouldIndex() ? 'INDEX' : 'NOINDEX' }}
                    </span>
                </td>
                <td class="mono xs muted">{{ $p->count_updated_at?->diffForHumans() ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">@lang('admin.no_pages')</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="note sec"><span class="t">@lang('admin.threshold_title')</span>@lang('admin.threshold_body')</div>
@endsection
