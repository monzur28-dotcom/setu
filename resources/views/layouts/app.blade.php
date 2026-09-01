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

    {{--
        The SheTu mark. PNG rather than .ico: every browser in use reads PNG
        favicons, and .ico exists for versions of Internet Explorer that
        cannot run this application anyway.
    --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/brand/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/brand/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/brand/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="{{ \App\Support\Theme::brand() }}">

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
<script>
// A reveal toggle on every password field. Added here rather than in each
// form so a field added later gets one without anybody remembering to.
//
// Progressive enhancement: with JavaScript off the input is exactly what it
// was, which is the right failure for a login form.
(function () {
    var SHOW = @json(__('auth.show_password'));
    var HIDE = @json(__('auth.hide_password'));

    var EYE = '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5.5 12 5.5 22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3.2"/></svg>';
    var OFF = '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 6.1A9.9 9.9 0 0 1 12 6c7 0 10.5 6 10.5 6a17 17 0 0 1-3.6 4.2M6.3 7.8A17 17 0 0 0 1.5 12S5 18 12 18c1.3 0 2.5-.2 3.5-.6"/><path d="M9.8 9.9a3.2 3.2 0 0 0 4.4 4.4"/></svg>';

    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        if (input.dataset.pwReady) return;
        input.dataset.pwReady = '1';

        var wrap = document.createElement('div');
        wrap.className = 'pw-field';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var btn = document.createElement('button');
        // Never a submit button: a reveal toggle that posts the form is a
        // login screen that logs you in when you try to check your typing.
        btn.type = 'button';
        btn.className = 'pw-toggle';
        btn.innerHTML = EYE;
        btn.setAttribute('aria-label', SHOW);
        btn.setAttribute('aria-pressed', 'false');
        wrap.appendChild(btn);

        var setShown = function (shown) {
            input.type = shown ? 'text' : 'password';
            btn.innerHTML = shown ? OFF : EYE;
            btn.setAttribute('aria-label', shown ? HIDE : SHOW);
            btn.setAttribute('aria-pressed', shown ? 'true' : 'false');
        };

        btn.addEventListener('click', function () {
            setShown(input.type === 'password');
            input.focus();
        });

        // Re-hide on submit, so a password is not left in plain text on the
        // screen for whoever walks past next, or on the back button.
        if (input.form) {
            input.form.addEventListener('submit', function () { setShown(false); });
        }
    });
})();
</script>
@stack('scripts')
</body>
</html>
