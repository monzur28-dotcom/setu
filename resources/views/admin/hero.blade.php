@extends('layouts.app')
@section('title', __('admin.hero'))
@section('noindex', true)
@section('crumb', 'Staff · Hero')

@section('content')
<div class="hd">
    <h1>@lang('admin.hero')</h1>
    <span class="sub">@lang('admin.hero_sub', ['seconds' => round($interval / 1000)])</span>
</div>

{{--
    The doorway cards' glass. The preview is a real .door on a real slide, so
    what an administrator drags is what a visitor gets — a swatch that only
    approximated it would be worse than no preview.
--}}
<form method="POST" action="{{ route('admin.appearance') }}" class="card pad sec glass-admin"
      style="--door-tint: {{ $tint }}; --door-blur: {{ $blur }}px">@csrf
    <span class="lbl">@lang('admin.appearance')</span>
    <p class="xs muted" style="margin:6px 0 16px">@lang('admin.appearance_sub')</p>

    <div class="glass-admin-grid">
        <div class="stack g14">
            <div class="field">
                <label>@lang('admin.door_tint') — <span class="mono" id="tint-out">{{ $tint }}</span>%</label>
                <input type="range" name="door_tint" id="tint" min="0" max="100" value="{{ $tint }}">
                <span class="xs muted">@lang('admin.door_tint_hint')</span>
            </div>
            <div class="field">
                <label>@lang('admin.door_blur') — <span class="mono" id="blur-out">{{ $blur }}</span>px</label>
                <input type="range" name="door_blur" id="blur" min="0" max="30" value="{{ $blur }}">
                <span class="xs muted">@lang('admin.door_blur_hint')</span>
            </div>
            <button class="btn">@lang('common.save')</button>
        </div>

        <div class="glass-preview">
            @if ($slides->isNotEmpty())
                <img src="{{ $slides->first()->url() }}" alt="">
            @endif
            <div class="door door-matrimony" style="pointer-events:none">
                <span class="door-tag">@lang('nav.matrimony')</span>
                <span class="door-h">@lang('home.door_matrimony_h')</span>
                <p>@lang('home.door_matrimony_body')</p>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
// Drives the same custom properties the front page uses, on the same .door
// markup, so the preview cannot drift from the real thing.
(function () {
    var form = document.querySelector('.glass-admin');
    if (!form) return;

    [['tint', 'tint-out', '--door-tint', ''],
     ['blur', 'blur-out', '--door-blur', 'px']].forEach(function (pair) {
        var input  = document.getElementById(pair[0]);
        var output = document.getElementById(pair[1]);

        input.addEventListener('input', function () {
            output.textContent = input.value;
            form.style.setProperty(pair[2], input.value + pair[3]);
        });
    });
})();
</script>
@endpush
<form method="POST" action="{{ route('admin.hero.add') }}" enctype="multipart/form-data"
      class="card pad row g10 wrap" style="align-items:flex-end">@csrf
    <div class="field grow">
        <label>@lang('admin.hero_image')</label>
        <input class="inp" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <div class="field grow">
        <label>@lang('admin.hero_caption')</label>
        <input class="inp" name="caption" maxlength="120">
    </div>
    <div class="field">
        <label>@lang('admin.hero_section')</label>
        <select class="inp" name="product">
            <option value="BOTH">@lang('admin.hero_both')</option>
            <option value="MATRIMONIAL">@lang('nav.matrimony')</option>
            <option value="CONNECT">@lang('nav.dating')</option>
        </select>
    </div>
    <button class="btn">@lang('admin.hero_add')</button>
    @error('image')<span class="xs bad">{{ $message }}</span>@enderror
</form>

<div class="grid g3 sec">
    @forelse ($slides as $slide)
        <div class="card slide-card">
            <img src="{{ $slide->url() }}" alt="{{ $slide->caption }}" loading="lazy">
            <form method="POST" action="{{ route('admin.hero.update', $slide) }}" class="pad stack g8">
                @csrf @method('PATCH')
                <input class="inp sm" name="caption" value="{{ $slide->caption }}" placeholder="@lang('admin.hero_caption')">
                <div class="row g6">
                    <select class="inp sm" name="product">
                        @foreach (['BOTH' => __('admin.hero_both'), 'MATRIMONIAL' => __('nav.matrimony'), 'CONNECT' => __('nav.dating')] as $v => $label)
                            <option value="{{ $v }}" @selected($slide->product === $v)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input class="inp sm" name="sort_order" value="{{ $slide->sort_order }}" inputmode="numeric" style="width:70px">
                </div>
                <label class="row g6 center sm">
                    <input type="checkbox" name="is_active" value="1" @checked($slide->is_active)>
                    <span>@lang('admin.hero_active')</span>
                </label>
                <div class="row g6">
                    <button class="btn sm grow">@lang('common.save')</button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.hero.remove', $slide) }}" class="pad" style="padding-top:0">
                @csrf @method('DELETE')
                <button class="btn sm ghost full">@lang('common.remove')</button>
            </form>
        </div>
    @empty
        <div class="card pad muted">@lang('admin.hero_empty')</div>
    @endforelse
</div>
@endsection
