@extends('layouts.app')
@section('title', __('about.title'))
@section('crumb', 'Public · About')
@section('content')
<div class="hd"><h1>@lang('about.title')</h1></div>
<div class="card pad" style="max-width:70ch"><p>@lang('about.body')</p></div>
@endsection
