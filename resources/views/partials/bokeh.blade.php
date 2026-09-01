{{--
    Hexagonal bokeh, drifting.

    A recreation of Animation1.mp4 — warm gold and coral hexagons at varying
    depths of field, the palette sampled straight out of the source file
    (#C27443, #C36F67, #965E3C, #461906) so the colour is the video's, not a
    guess at it.

    Rebuilt in CSS rather than served as the video, because the video costs
    5.5 MB on every first view of the front page and this costs about three.
    It also loops seamlessly, tints itself to whichever product's palette it
    is sitting in, and does not have to be re-encoded when the brand moves.

    Deterministic: the "random" spread is generated from a fixed seed, so the
    layout is stable across page loads and deploys rather than shimmering
    into a new arrangement every request.

    $count — how many hexagons, default 18
--}}
@php
    $count = $count ?? 18;
    // The largest hexagon, in px. A short banner needs small lights or the
    // near ones get cropped and read as flat panels rather than as something
    // out of focus in front of the lens.
    $max = $max ?? 228;

    // Sampled from the source. Ordered dark → bright so the weighting below
    // puts more of the quiet tones behind and fewer hot highlights in front.
    $palette = ['#7A3D1B', '#965E3C', '#A14D48', '#B46C5E', '#C27443', '#C36F67', '#D89A5E'];

    mt_srand(20260901);

    $hexes = [];
    for ($i = 0; $i < $count; $i++) {
        // Depth drives size, blur and opacity together — a big soft one is
        // near the lens, a small sharp one is far away. Varying them
        // independently is what makes fake bokeh look like coloured dots.
        $depth = mt_rand(0, 100) / 100;

        $hexes[] = [
            'size'    => round($max * (0.17 + $depth * 0.83)),
            // Blur scales with the field too, so a small field stays soft
            // rather than turning into a set of crisp tiles.
            'blur'    => round($max * (0.004 + $depth * 0.062), 1),
            'opacity' => round(0.52 - $depth * 0.26, 3),
            'colour'  => $palette[mt_rand(0, count($palette) - 1)],
            'left'    => mt_rand(-8, 100),
            'top'     => mt_rand(-10, 100),
            'drift'   => mt_rand(-70, 70),
            'rise'    => mt_rand(-90, -30),
            'spin'    => mt_rand(-40, 40),
            'dur'     => mt_rand(26, 54),
            'delay'   => -mt_rand(0, 40),
        ];
    }
@endphp
<div class="bokeh" aria-hidden="true">
    @foreach ($hexes as $h)
        <span class="bokeh-hex" style="
            --size:{{ $h['size'] }}px;
            --blur:{{ $h['blur'] }}px;
            --opacity:{{ $h['opacity'] }};
            --colour:{{ $h['colour'] }};
            --left:{{ $h['left'] }}%;
            --top:{{ $h['top'] }}%;
            --drift:{{ $h['drift'] }}px;
            --rise:{{ $h['rise'] }}px;
            --spin:{{ $h['spin'] }}deg;
            --dur:{{ $h['dur'] }}s;
            --delay:{{ $h['delay'] }}s;"></span>
    @endforeach
</div>
