@extends('layouts.app')
@section('title', __('auth.verify_number'))
@section('noindex', true)
@section('crumb', 'Auth · Verify phone')

@section('content')
<div class="hd">
    <h1>@lang('auth.verify_number')</h1>
    <p class="sub">@lang('auth.otp_sent', ['mobile' => $mobile])</p>
</div>

<div class="stack g14" style="max-width:420px">
    <form method="POST" action="{{ route('register.otp.verify') }}" class="card pad stack g14">@csrf
        <div class="field">
            <label>@lang('auth.code')</label>
            <input class="inp mono" name="code" inputmode="numeric" maxlength="6"
                   style="text-align:center; font-size:24px; letter-spacing:.4em" autofocus required>
        </div>
        <button class="btn lg">@lang('auth.verify')</button>

        @if (app()->environment('local'))
            <div class="note"><span class="t">Local</span>@lang('auth.local_otp_hint')</div>
        @endif
    </form>

    {{-- A separate form, because it must not carry the code field: asking
         for a new code while a half-typed one sits in the box should not
         spend an attempt on it. --}}
    <form method="POST" action="{{ route('register.otp.resend') }}" class="stack g6">@csrf
        <button class="btn ghost full" id="resend" disabled>@lang('auth.resend')</button>
        <span class="hint" id="resend-hint">
            @lang('auth.resend_hint', ['seconds' => $resendAfter])
        </span>
    </form>
</div>

@push('scripts')
<script>
// The screen used to promise "ask again in :seconds" with nothing behind it
// and no value substituted. The button is now real, and disabled until the
// wait is genuinely over rather than only claiming to be.
(function () {
    var btn  = document.getElementById('resend');
    var hint = document.getElementById('resend-hint');
    if (!btn) return;

    var left = {{ (int) $resendAfter }};

    var tick = function () {
        if (left <= 0) {
            btn.disabled = false;
            hint.textContent = @json(__('auth.resend_ready'));
            return;
        }
        hint.textContent = @json(__('auth.resend_hint', ['seconds' => '__N__'])).replace('__N__', left);
        left--;
        setTimeout(tick, 1000);
    };

    tick();
})();
</script>
@endpush
@endsection
