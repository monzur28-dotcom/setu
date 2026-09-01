@extends('layouts.app')
@section('title', __('home.title'))
@section('meta_description', __('home.sub'))
@section('crumb', 'Public · Home')

@section('content')
{{--
    The doorway. Two products share this address and they are not the same
    promise, so the first thing the page does is ask which one you came for
    rather than guessing. The wall between them starts here: choosing a door
    is a navigation, never a signal recorded against anyone.
--}}
{{-- Administrator-set, so they arrive as custom properties rather than
     being baked into the stylesheet. --}}
<style>
    .doorway{
        --door-tint: {{ \App\Models\SiteSetting::number('door_tint') }};
        --door-blur: {{ \App\Models\SiteSetting::number('door_blur') }}px;
    }
</style>

<section class="doorway">
    <div class="doorway-art" aria-hidden="true">
        @foreach ($slides as $i => $slide)
            <div class="doorway-slide {{ $i === 0 ? 'on' : '' }}"
                 style="background-image:url('{{ $slide->url() }}')"></div>
        @endforeach
        <div class="doorway-scrim"></div>
    </div>

    <div class="doorway-inner">
        <span class="hero-badge">@lang('home.hero_badge')</span>
        <h1>@lang('home.h1')</h1>
        <p class="sub">@lang('home.doorway_sub')</p>

        <div class="doors">
            <a class="door door-matrimony" href="#matrimony">
                <span class="door-tag">@lang('nav.matrimony')</span>
                <span class="door-h">@lang('home.door_matrimony_h')</span>
                <p>@lang('home.door_matrimony_body')</p>
                <span class="door-cta">@lang('home.door_matrimony_cta') <span aria-hidden="true">→</span></span>
            </a>
            <a class="door door-dating" href="#dating">
                <span class="door-tag">@lang('nav.dating')</span>
                <span class="door-h">@lang('home.door_dating_h')</span>
                <p>@lang('home.door_dating_body')</p>
                <span class="door-cta">@lang('home.door_dating_cta') <span aria-hidden="true">→</span></span>
            </a>
        </div>

        <p class="doorway-wall">@lang('home.wall_promise')</p>
    </div>
</section>

{{-- ============================ 1 · MATRIMONY ============================ --}}
<section id="matrimony" class="half half-matrimony">
    <div class="half-head">
        <span class="lbl">@lang('nav.matrimony')</span>
        <h2>@lang('home.matrimony_h')</h2>
        <p class="sub">@lang('home.matrimony_sub')</p>
    </div>

    <div class="home-hero {{ $featured->isEmpty() ? 'no-art' : '' }}">
        <div class="hero-copy">
            {{-- Fills the column, and illustrates the claim the stats below
                 make rather than sitting there as decoration. --}}
            @include('partials.globe')
            <div class="globe-caption">@lang('home.globe_caption')</div>

            <div class="hero-actions">
                <a class="btn" href="{{ route('register') }}">@lang('nav.register_free')</a>
                <a class="btn ghost" href="{{ route('public.search') }}">@lang('home.browse')</a>
            </div>

            <div class="hero-stats">
                <div><strong>500K+</strong><span>Members</span></div>
                <div><strong>5K+</strong><span>Success stories</span></div>
                <div><strong>150+</strong><span>Countries</span></div>
            </div>
        </div>

        @if ($featured->isNotEmpty())
            {{--
                Brides and grooms, faces first. Every profile here has
                show_photos = true — the montage is drawn from the same
                serializer as every other surface, so the front page cannot
                become the one place a hidden photograph leaks.
            --}}
            <div class="hero-art">
                <div class="hero-collage">
                    @foreach ($featured as $f)
                        @php $ph = $f['photos'][0] ?? null; @endphp
                        <a class="collage-tile" href="{{ route('public.profile', $f['profile_id']) }}">
                            <img src="{{ $ph['url'] }}" alt="" loading="lazy">
                            <span class="cap">
                                <span class="nm">{{ $f['display_name'] ?? '—' }}</span>
                                <span class="xs muted">{{ $f['age'] ?? '' }}@isset($f['district']) · {{ $f['district'] }}@endisset</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                <span class="hero-art-note">@lang('home.photo_consent')</span>
            </div>
        @endif

        <div class="hero-search">
            <div class="mini-heading">@lang('home.search_without_account')</div>
            <form method="GET" action="{{ route('public.search') }}">
                <div class="search-row">
                    <div class="field">
                        <label>@lang('search.looking_for')</label>
                        <select class="inp" name="gender">
                            <option value="FEMALE">@lang('search.bride')</option>
                            <option value="MALE">@lang('search.groom')</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>@lang('search.religion')</label>
                        <select class="inp" name="religion">
                            <option value="">@lang('common.all')</option>
                            @foreach (['ISLAM', 'HINDUISM', 'CHRISTIANITY', 'BUDDHISM'] as $r)
                                <option value="{{ $r }}">@lang('enum.religion.'.$r)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>@lang('search.age')</label>
                        <div class="row g6">
                            <input class="inp" name="age_min" value="22" inputmode="numeric">
                            <span class="muted">–</span>
                            <input class="inp" name="age_max" value="32" inputmode="numeric">
                        </div>
                    </div>
                    <div class="field">
                        <label>@lang('search.country')</label>
                        @include('partials.country-select', [
                            'countries' => $countries,
                            'selected'  => null,
                            'any'       => __('search.any_country'),
                        ])
                    </div>
                    <button class="btn">@lang('home.browse')</button>
                </div>
                <span class="xs muted">@lang('home.free_promise')</span>
            </form>
        </div>
    </div>

    <div class="trust-grid sec">
        @foreach ([
            ['home.trust1', 'home.trust1_sub'],
            ['home.trust2', 'home.trust2_sub'],
            ['home.trust3', 'home.trust3_sub'],
            ['home.trust4', 'home.trust4_sub'],
        ] as [$a, $b])
            <div class="trust-card">
                <div class="trust-icon">✓</div>
                <div>
                    <div class="b sm">@lang($a)</div>
                    <div class="xs muted">@lang($b)</div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($recent->isNotEmpty())
        <div class="sec">
            <div class="section-head">
                <span class="lbl">@lang('home.recent')</span>
                <a href="{{ route('public.search') }}" class="text-link">View all</a>
            </div>
            <div class="grid g4c">
                @foreach ($recent as $p)
                    @include('partials.profile-card', ['p' => $p])
                @endforeach
            </div>
        </div>
    @endif

    <div class="sec">
        <div class="section-head">
            <span class="lbl">@lang('home.how_it_works')</span>
        </div>
        <div class="grid g4c">
            @foreach ([1, 2, 3, 4] as $i)
                <div class="card pad stack g8 process-card">
                    <div class="step"><span class="n">{{ $i }}</span><span class="b sm">@lang("home.step{$i}")</span></div>
                    <p class="sm muted">@lang("home.step{$i}_body")</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="sec">
        <div class="section-head">
            <span class="lbl">@lang('home.free_tools')</span>
        </div>
        <div class="grid g3">
            <div class="tool-card">
                <div class="b">@lang('nav.biodata')</div>
                <p class="sm muted">@lang('home.biodata_body')</p>
                <a class="btn sm ghost" href="{{ route('biodata.create') }}">@lang('home.open_tool')</a>
            </div>
            <div class="tool-card">
                <div class="b">@lang('nav.classifieds')</div>
                <p class="sm muted">@lang('home.classifieds_body')</p>
                <a class="btn sm ghost" href="{{ route('classifieds.index') }}">@lang('home.browse_ads')</a>
            </div>
            <div class="tool-card">
                <div class="b">@lang('nav.safety')</div>
                <p class="sm muted">@lang('home.safety_body')</p>
                <a class="btn sm ghost" href="{{ route('safety') }}">@lang('common.read_more')</a>
            </div>
        </div>
    </div>

    <div class="sec">
        <div class="section-head">
            <span class="lbl">@lang('home.browse_by_country')</span>
        </div>
        <div class="grid g4c category-grid">
            @foreach ($countries['featured'] as $code => $label)
                <a class="cat-card" href="{{ route('public.search', ['country' => $code]) }}">
                    <span>{{ $label }}</span>
                    <span class="muted">→</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Regions of the home market, where the geography data actually goes
         deeper than the country. Other markets get country-level browsing
         until their regions are seeded, which is honest rather than showing
         empty pages. --}}
    @if ($divisions->isNotEmpty())
        <div class="sec">
            <div class="section-head">
                <span class="lbl">@lang('home.browse_by', ['country' => \App\Support\Countries::name(config('setu.home_market'))])</span>
            </div>
            <div class="grid g4c category-grid">
                @foreach ($divisions as $d)
                    <a class="cat-card" href="{{ url('matrimony/'.$d->slug) }}">
                        <span>{{ $d->name() }}</span>
                        <span class="muted">→</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</section>

{{-- ============================== 2 · DATING ============================= --}}
{{--
    The dating half runs on the Connect palette, because the two products are
    not the same product and should not look like it. Everything here is
    marketing copy: nothing on this page reads, writes or hints at whether
    any given person has a Connect profile. Wall rule W8.
--}}
<section id="dating" class="half half-dating" data-mode="connect">
    <div class="half-head">
        <span class="lbl">@lang('nav.dating')</span>
        <h2>@lang('home.dating_h')</h2>
        <p class="sub">@lang('home.dating_sub')</p>
    </div>

    <div class="dating-panel">
        <div class="stack g14">
            <div class="grid g2">
                @foreach ([1, 2, 3, 4] as $i)
                    <div class="dating-point">
                        <span class="trust-icon">✓</span>
                        <div>
                            <div class="b sm">@lang("home.dating_point{$i}")</div>
                            <div class="xs muted">@lang("home.dating_point{$i}_sub")</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g10 wrap">
                @auth
                    <a class="btn" href="{{ route('connect.start') }}">@lang('nav.open_connect')</a>
                @else
                    <a class="btn" href="{{ route('register') }}">@lang('home.dating_cta')</a>
                @endauth
                <a class="btn ghost" href="{{ route('safety') }}">@lang('home.dating_how_safe')</a>
            </div>
        </div>

        <div class="dating-wall card pad stack g10">
            <span class="lbl">@lang('home.wall_title')</span>
            <p class="sm">@lang('nav.connect_note')</p>
            <ul class="wall-list">
                @foreach ([1, 2, 3] as $i)
                    <li>@lang("home.wall_rule{$i}")</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

<div class="sec cta-banner">
    {{-- Warm hexagonal lights over the brand gradient. This is the plate the
         effect was made for: additive light needs something dark to be
         additive against. --}}
    @include('partials.bokeh', ['count' => 16, 'max' => 150])

    <div class="cta-inner">
        <span class="lbl">@lang('home.cta_kicker')</span>
        <h2>@lang('home.cta_h')</h2>
    </div>
    <div class="cta-actions">
        <a class="btn" href="{{ route('register') }}">@lang('home.cta_primary')</a>
        <a class="btn ghost" href="{{ route('plans') }}">@lang('home.cta_secondary')</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
// The slideshow. Interval comes from config so the admin's timing choice and
// the page agree; a single slide, or a viewer who has asked for reduced
// motion, simply gets a still image.
// The globe clip is fetched only when its panel comes near the viewport, so
// a visitor who never scrolls past the doorway never pays for it. Someone
// who has asked for reduced motion gets the poster frame and nothing else.
(function () {
    var v = document.querySelector('.globe-video');
    if (!v || !v.dataset.src) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var start = function () {
        v.src = v.dataset.src;
        v.play().catch(function () { /* autoplay refused: the poster stands in */ });
    };

    if (!('IntersectionObserver' in window)) { start(); return; }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { start(); io.disconnect(); }
        });
    }, { rootMargin: '300px' });

    io.observe(v);
})();

(function () {
    var slides = document.querySelectorAll('.doorway-slide');
    if (slides.length < 2) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var i = 0;
    setInterval(function () {
        slides[i].classList.remove('on');
        i = (i + 1) % slides.length;
        slides[i].classList.add('on');
    }, {{ (int) config('setu.hero.interval_ms', 3000) }});
})();
</script>
@endpush
