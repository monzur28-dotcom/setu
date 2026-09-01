<!doctype html>
<html lang="{{ app()->getLocale() }}"
      data-mode="{{ $mode ?? 'matrimonial' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow">
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ \App\Support\Theme::fontUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- The administrator's appearance settings. Emitted after app.css so
         it wins on cascade order, with no !important anywhere. --}}
    <style>{!! \App\Support\Theme::css() !!}</style>

    @stack('head')
</head>
<body>
<div class="scrim" id="scrim" onclick="toggleRail()"></div>
<div class="app">
    @include('partials.rail')

    <div class="main">
        <header class="topbar">
            <button class="btn ghost sm railtoggle" onclick="toggleRail()" aria-label="Menu">☰</button>
            <span class="crumb">@yield('crumb')</span>
            <span class="grow"></span>
            @auth
                <span class="chip brand">{{ auth()->user()->profile_id }}</span>
            @else
                <a class="btn sm" href="{{ route('register') }}">@lang('nav.register_free')</a>
            @endauth
        </header>

        <main class="view">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<script>
function toggleRail() {
    document.getElementById('rail').classList.toggle('open');
    document.getElementById('scrim').classList.toggle('on');
}
</script>
@stack('scripts')
</body>
</html>
