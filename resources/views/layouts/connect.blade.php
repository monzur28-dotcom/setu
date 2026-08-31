{{--
    Connect runs on the same design system with a different palette, so the
    member feels they have moved rather than switched a tab.
    It is also stamped noindex at three layers: robots.txt, the middleware
    header, and here. Wall rule W3.
--}}
@extends('layouts.app', ['mode' => 'connect'])

@section('noindex', true)
