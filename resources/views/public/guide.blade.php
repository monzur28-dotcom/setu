@extends('layouts.app')
@section('title', app()->getLocale() === 'bn' ? $guide->title_bn : $guide->title_en)
@section('crumb', 'Public · Guide')
@section('content')
<div class="hd"><h1>{{ app()->getLocale() === 'bn' ? $guide->title_bn : $guide->title_en }}</h1></div>
<article class="card pad" style="max-width:72ch">
    {!! nl2br(e(app()->getLocale() === 'bn' ? $guide->body_bn : $guide->body_en)) !!}
</article>
@endsection
