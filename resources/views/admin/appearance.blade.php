@extends('layouts.app')
@section('title', __('admin.appearance'))
@section('noindex', true)
@section('crumb', 'Staff · Appearance')

@push('head')
    {{-- Every pairing, so the preview can render in a face that has not been
         chosen yet. This screen only. --}}
    <link rel="stylesheet" href="{{ \App\Support\Theme::allFontsUrl() }}">
@endpush

@section('content')
<div class="hd">
    <h1>@lang('admin.appearance')</h1>
    <span class="sub">@lang('admin.appearance_sub')</span>
</div>

<form method="POST" action="{{ route('admin.appearance.save') }}" id="theme-form">@csrf
<div class="theme-grid">

    <div class="stack g20">
        {{-- ---------------------------------------------------- typeface --}}
        <div class="card pad stack g12">
            <span class="lbl">@lang('admin.typeface')</span>
            <p class="xs muted" style="margin:0">@lang('admin.typeface_hint')</p>

            <div class="stack g8">
                @foreach ($pairs as $key => $p)
                    <label class="pair-option {{ $pair === $key ? 'on' : '' }}">
                        <input type="radio" name="font_pair" value="{{ $key }}" @checked($pair === $key)>
                        <span class="grow">
                            <span class="pair-name" style="font-family:{{ $p['head'] }}">{{ $p['label'] }}</span>
                            <span class="xs muted">{{ $p['note'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ------------------------------------------------------- weight --}}
        <div class="card pad stack g12">
            <span class="lbl">@lang('admin.weight')</span>
            <div class="grid g2">
                <div class="field">
                    <label>@lang('admin.heading_weight')</label>
                    <select class="inp" name="heading_weight" id="headW">
                        @foreach ($weights['head'] as $v => $label)
                            <option value="{{ $v }}" @selected($headW == $v)>{{ $label }} ({{ $v }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>@lang('admin.body_weight')</label>
                    <select class="inp" name="body_weight" id="bodyW">
                        @foreach ($weights['body'] as $v => $label)
                            <option value="{{ $v }}" @selected($bodyW == $v)>{{ $label }} ({{ $v }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="field">
                <label>@lang('admin.base_size') — <span class="mono" id="size-out">{{ $size }}</span>px</label>
                <input type="range" name="base_font_px" id="size" min="13" max="19" value="{{ $size }}">
                <span class="xs muted">@lang('admin.base_size_hint')</span>
            </div>
        </div>

        {{-- ------------------------------------------------------ colours --}}
        <div class="card pad stack g12">
            <span class="lbl">@lang('admin.colour')</span>
            <p class="xs muted" style="margin:0">@lang('admin.colour_hint')</p>
            <div class="grid g2">
                <div class="field">
                    <label>@lang('admin.brand_colour')</label>
                    <div class="row g8 center">
                        <input type="color" name="brand_color" id="brand" value="{{ $brand }}">
                        <span class="mono sm" id="brand-out">{{ $brand }}</span>
                    </div>
                </div>
                <div class="field">
                    <label>@lang('admin.gold_colour')</label>
                    <div class="row g8 center">
                        <input type="color" name="gold_color" id="gold" value="{{ $gold }}">
                        <span class="mono sm" id="gold-out">{{ $gold }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- --------------------------------------------------- the globe --}}
        <div class="card pad stack g12">
            <span class="lbl">@lang('admin.globe')</span>
            <p class="xs muted" style="margin:0">@lang('admin.globe_hint')</p>
            <div class="field">
                <label>@lang('admin.globe_width') — <span class="mono" id="globeW-out">{{ $globeW }}</span>px</label>
                <input type="range" name="globe_width" id="globeW" min="220" max="680" step="10" value="{{ $globeW }}">
            </div>
        </div>

        {{-- --------------------------------------------- doorway text --}}
        <div class="card pad stack g12">
            <span class="lbl">@lang('admin.doorway_text')</span>
            <p class="xs muted" style="margin:0">@lang('admin.doorway_text_hint')</p>

            <div class="field">
                <label>@lang('admin.alignment')</label>
                <div class="row g6">
                    @foreach (['left', 'center', 'right'] as $a)
                        <label class="align-option {{ $align === $a ? 'on' : '' }}">
                            <input type="radio" name="door_align" value="{{ $a }}" @checked($align === $a)>
                            <span>@lang('admin.align_'.$a)</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="field">
                <label>@lang('admin.door_tag_size') — <span class="mono" id="tagSize-out">{{ $tagSize }}</span>px</label>
                <input type="range" name="door_tag_size" id="tagSize" min="9" max="18" value="{{ $tagSize }}">
                <span class="xs muted">@lang('admin.door_tag_size_hint')</span>
            </div>

            <div class="field">
                <label>@lang('admin.door_head_size') — <span class="mono" id="headSize-out">{{ $headSize }}</span>px</label>
                <input type="range" name="door_head_size" id="headSize" min="16" max="44" value="{{ $headSize }}">
            </div>

            <div class="field">
                <label>@lang('admin.door_body_size') — <span class="mono" id="bodySize-out">{{ $bodySize }}</span>px</label>
                <input type="range" name="door_body_size" id="bodySize" min="11" max="20" value="{{ $bodySize }}">
            </div>

            <div class="grid g3">
                <div class="field">
                    <label>@lang('admin.door_tag_colour')</label>
                    <div class="row g8 center">
                        <input type="color" name="door_tag_color" id="tagColor" value="{{ $tagColor }}">
                        <span class="mono xs" id="tagColor-out">{{ $tagColor }}</span>
                    </div>
                </div>
                <div class="field">
                    <label>@lang('admin.door_cta_colour')</label>
                    <div class="row g8 center">
                        <input type="color" name="door_cta_color" id="ctaColor" value="{{ $ctaColor }}">
                        <span class="mono xs" id="ctaColor-out">{{ $ctaColor }}</span>
                    </div>
                </div>
                <div class="field">
                    <label>@lang('admin.door_cta_dating_colour')</label>
                    <div class="row g8 center">
                        <input type="color" name="door_cta_dating_color" id="ctaDating" value="{{ $ctaDating }}">
                        <span class="mono xs" id="ctaDating-out">{{ $ctaDating }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- -------------------------------------------------------- glass --}}
        <div class="card pad stack g12">
            <span class="lbl">@lang('admin.doorway_glass')</span>
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
        </div>

        <button class="btn lg">@lang('common.save')</button>
    </div>

    {{-- ------------------------------------------------------- preview --}}
    <div class="theme-preview-wrap">
        <div class="theme-preview" id="preview">
            <span class="lbl">@lang('admin.preview')</span>

            <div class="globe-panel" id="globe-preview" style="max-width:{{ $globeW }}px">
                <img src="{{ asset('video/globe-poster.jpg') }}" alt=""
                     style="width:100%;height:100%;object-fit:cover;display:block">
                <div class="globe-panel-vignette"></div>
            </div>
            <h2>@lang('home.h1')</h2>
            <p class="sub">@lang('home.doorway_sub')</p>

            <div class="preview-doors">
                @if ($slide)
                    <img src="{{ $slide->url() }}" alt="">
                @endif
                <div class="door door-matrimony">
                    <span class="door-tag">@lang('nav.matrimony')</span>
                    <span class="door-h">@lang('home.door_matrimony_h')</span>
                    <p>@lang('home.door_matrimony_body')</p>
                    <span class="door-cta">@lang('home.door_matrimony_cta') →</span>
                </div>
                <div class="door door-dating" style="margin-top:10px">
                    <span class="door-tag">@lang('nav.dating')</span>
                    <span class="door-h">@lang('home.door_dating_h')</span>
                    <span class="door-cta">@lang('home.door_dating_cta') →</span>
                </div>
            </div>

            <div class="row g8 wrap" style="margin-top:14px">
                <span class="btn sm">@lang('nav.register_free')</span>
                <span class="btn sm ghost">@lang('home.browse')</span>
                <span class="chip ok">◆ @lang('profile.nid_verified')</span>
            </div>

            <p class="sm" style="margin-top:12px">@lang('home.matrimony_sub')</p>
            <p class="sm bn" lang="bn">@lang('home.wall_promise', [], 'bn')</p>
        </div>
        <span class="xs muted">@lang('admin.preview_hint')</span>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
// The preview is real markup — .door, .btn, .chip — under a scoped set of
// the same custom properties the site uses. Nothing here approximates the
// result; it applies it to a smaller area.
(function () {
    var p = document.getElementById('preview');
    if (!p) return;

    var pairs = @json(collect($pairs)->map(fn ($x) => ['head' => $x['head'], 'body' => $x['body']]));

    var set = function (name, value) { p.style.setProperty(name, value); };

    var mixBrand = function (hex) {
        set('--brand', hex);
        set('--brand-deep', 'color-mix(in srgb, ' + hex + ' 76%, black)');
        set('--brand-tint', 'color-mix(in srgb, ' + hex + ' 13%, white)');
    };

    document.querySelectorAll('[name="font_pair"]').forEach(function (r) {
        r.addEventListener('change', function () {
            var f = pairs[r.value];
            set('--font-head', f.head);
            set('--font-body', f.body);
            document.querySelectorAll('.pair-option').forEach(function (o) { o.classList.remove('on'); });
            r.closest('.pair-option').classList.add('on');
        });
    });

    var bind = function (id, fn) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('input', function () { fn(el.value); }); }
    };

    bind('size',  function (v) { set('--font-size', v + 'px'); document.getElementById('size-out').textContent = v; });
    bind('headW', function (v) { set('--w-head', v); });
    bind('bodyW', function (v) { set('--w-body', v); });
    bind('tint',  function (v) { set('--door-tint', v); document.getElementById('tint-out').textContent = v; });
    bind('blur',  function (v) { set('--door-blur', v + 'px'); document.getElementById('blur-out').textContent = v; });
    bind('brand', function (v) { mixBrand(v); document.getElementById('brand-out').textContent = v; });
    bind('gold',  function (v) { set('--gold', v); document.getElementById('gold-out').textContent = v; });

    bind('globeW', function (v) {
        document.getElementById('globeW-out').textContent = v;
        var g = document.getElementById('globe-preview');
        if (g) { g.style.maxWidth = v + 'px'; }
    });

    // The doorway parts are styled by rule rather than by custom property,
    // so the preview writes a scoped stylesheet instead of setting variables
    // on an element. Scoped to #preview so it cannot touch the admin chrome.
    var doorStyle = document.createElement('style');
    document.head.appendChild(doorStyle);

    var door = {
        align:     @json($align),
        tagSize:   {{ $tagSize }},
        headSize:  {{ $headSize }},
        bodySize:  {{ $bodySize }},
        tagColor:  @json($tagColor),
        ctaColor:  @json($ctaColor),
        ctaDating: @json($ctaDating)
    };

    var paintDoors = function () {
        doorStyle.textContent =
            '#preview .door{text-align:' + door.align + '}' +
            '#preview .door-tag{font-size:' + door.tagSize + 'px;color:' + door.tagColor + '}' +
            '#preview .door-h{font-size:' + door.headSize + 'px}' +
            '#preview .door p{font-size:' + door.bodySize + 'px}' +
            '#preview .door-matrimony .door-cta{color:' + door.ctaColor + '}' +
            '#preview .door-dating .door-cta{color:' + door.ctaDating + '}';
    };
    paintDoors();

    document.querySelectorAll('[name="door_align"]').forEach(function (r) {
        r.addEventListener('change', function () {
            door.align = r.value;
            document.querySelectorAll('.align-option').forEach(function (o) { o.classList.remove('on'); });
            r.closest('.align-option').classList.add('on');
            paintDoors();
        });
    });

    [['tagSize', 'tagSize-out'], ['headSize', 'headSize-out'], ['bodySize', 'bodySize-out']]
        .forEach(function (t) {
            bind(t[0], function (v) {
                door[t[0]] = v;
                document.getElementById(t[1]).textContent = v;
                paintDoors();
            });
        });

    ['tagColor', 'ctaColor', 'ctaDating'].forEach(function (id) {
        bind(id, function (v) {
            door[id] = v;
            document.getElementById(id + '-out').textContent = v;
            paintDoors();
        });
    });

    document.getElementById('headW').addEventListener('change', function (e) { set('--w-head', e.target.value); });
    document.getElementById('bodyW').addEventListener('change', function (e) { set('--w-body', e.target.value); });
})();
</script>
@endpush
