@extends('layouts.app')
@section('title', __('common.faq'))
@section('crumb', 'Public · FAQ')
@section('content')
<div class="hd"><h1>@lang('common.faq')</h1></div>
<div class="stack g10" style="max-width:70ch">
    @foreach (range(1, 6) as $i)
        <div class="card pad stack g4">
            <span class="b sm">@lang("faq.q{$i}")</span>
            <span class="sm muted">@lang("faq.a{$i}")</span>
        </div>
    @endforeach
</div>
@endsection
