@extends('layouts.app')
@section('title', __('about.title'))
@section('crumb', 'Public · About')
@section('content')
<div class="hd"><h1>@lang('about.title')</h1></div>
<div class="card pad stack g12" style="max-width:70ch">
    <p>@lang('about.body')</p>
    {{-- Where the name comes from. Worth saying plainly: it is the product
         in one sentence. --}}
    <p class="sm muted">@lang('about.name_meaning')</p>
    <p class="xs muted" style="border-top:1px solid var(--line); padding-top:12px">
        {{ __('nav.a_product_of') }} <strong>{{ config('setu.company.name') }}</strong>,
        {{ \App\Support\Countries::name(config('setu.company.incorporated')) }}.
    </p>
</div>
@endsection
