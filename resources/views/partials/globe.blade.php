{{--
    A turning globe, after globe1.mp4.

    The reference is a stylised "network earth": a dense dot-grid sphere, a
    hot glowing rim at the limb, light streaks orbiting on tilted planes, and
    small hexagonal markers pinned to the surface. All geometry and glow,
    which is the reason it can be rebuilt honestly in CSS — the other
    reference, Globe8, is photographic Earth with cloud and city lights, and
    that needs WebGL or the video, not a fake.

    Recoloured. The source is neon purple and cyan; this site is warm alta
    red and gold set in a serif, and a cyberpunk globe in the middle of it
    would read as pasted in from somewhere else.

    Built from real 3D transforms: eighteen meridians and nine latitudes
    inside one rotating container, so the grid foreshortens correctly at the
    limb. That foreshortening is the difference between a sphere and a
    spinning flat circle.

    Positions are unitless fractions of the radius; CSS multiplies them by
    --r, so the whole thing rescales from one number.
--}}
@php
    $meridians = range(0, 17);              // every 10°
    $latitudes = [-70, -52, -35, -17, 0, 17, 35, 52, 70];

    // Tilt, size and speed of each orbiting streak.
    $orbits = [
        ['tiltX' => 74,  'tiltZ' => -18, 'scale' => 1.22, 'dur' => 9,  'dir' => 'normal'],
        ['tiltX' => 62,  'tiltZ' => 34,  'scale' => 1.34, 'dur' => 14, 'dir' => 'reverse'],
        ['tiltX' => 100, 'tiltZ' => 8,   'scale' => 1.15, 'dur' => 19, 'dir' => 'normal'],
    ];

    // [latitude, longitude] — real coordinates for the featured markets.
    $pins = [
        [23.8, 90.4],    // Dhaka
        [51.5, -0.1],    // London
        [40.7, -74.0],   // New York
        [43.7, -79.4],   // Toronto
        [25.2, 55.3],    // Dubai
        [-33.9, 151.2],  // Sydney
        [3.1, 101.7],    // Kuala Lumpur
        [-26.2, 28.0],   // Johannesburg
    ];
@endphp
<div class="globe-scene" aria-hidden="true">
    {{-- Orbiting streaks sit outside the sphere, on their own tilted planes.
         Each ring is transparent but for one lit edge, so rotating it reads
         as a light travelling around an orbit. --}}
    @foreach ($orbits as $o)
        <span class="globe-orbit" style="
            --tiltX:{{ $o['tiltX'] }}deg;
            --tiltZ:{{ $o['tiltZ'] }}deg;
            --scale:{{ $o['scale'] }};
            --dur:{{ $o['dur'] }}s;
            --dir:{{ $o['dir'] }}"></span>
    @endforeach

    <div class="globe">
        @foreach ($meridians as $i)
            <span class="globe-meridian" style="--turn:{{ $i * 10 }}deg"></span>
        @endforeach

        @foreach ($latitudes as $lat)
            <span class="globe-latitude"
                  style="--lift:{{ round(-sin(deg2rad($lat)), 4) }}; --shrink:{{ round(cos(deg2rad($lat)), 4) }}"></span>
        @endforeach

        @foreach ($pins as $i => [$lat, $lon])
            @php
                $latRad = deg2rad($lat);
                $lonRad = deg2rad($lon);
            @endphp
            <span class="globe-pin" style="
                --x:{{ round(cos($latRad) * sin($lonRad), 4) }};
                --y:{{ round(-sin($latRad), 4) }};
                --z:{{ round(cos($latRad) * cos($lonRad), 4) }};
                --delay:{{ $i * 0.6 }}s"><i></i></span>
        @endforeach
    </div>

    {{-- Volume and the hot limb. A wireframe with no shading reads as a flat
         spirograph; the rim is what makes it a lit object. --}}
    <div class="globe-glow"></div>
    <div class="globe-rim"></div>
</div>
