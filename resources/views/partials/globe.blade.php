{{--
    A wireframe globe, turning.

    Built from real 3D CSS transforms rather than a library: twelve meridian
    circles rotated around the Y axis and five latitude circles laid flat at
    their true heights, inside one container that rotates. It is an actual
    sphere, so the meridians foreshorten correctly as they come round —
    which is the part that makes a fake globe look fake.

    No Three.js, no globe.gl, no CDN. A decorative element on the front page
    does not justify half a megabyte of JavaScript, a third-party origin, or
    a page that stops working when you are offline.

    Positions are unitless fractions of the sphere's radius; CSS multiplies
    them by --r, so the whole thing scales by changing one number.

    The pins sit at real latitudes and longitudes, so what it illustrates is
    what it shows.
--}}
@php
    // Meridians every 15°: enough to read as a sphere, not so many it moirés.
    $meridians = range(0, 11);

    $latitudes = [-60, -30, 0, 30, 60];

    // [latitude, longitude] for a handful of the featured markets.
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
    <div class="globe">
        @foreach ($meridians as $i)
            <span class="globe-meridian" style="--turn:{{ $i * 15 }}deg"></span>
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

    <div class="globe-glow"></div>
</div>
