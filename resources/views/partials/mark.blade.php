{{--
    The SheTu mark: the ornate S and T inside a heart, with the ring above.
    "Setu" is Bengali for bridge and SheTu reads as she + tumi — she and you.

    Raster rather than the drawn bridge that used to be here, because this is
    the real logo and a hand-drawn approximation of it would be a different
    mark wearing the same name.

    Served at 128px and displayed at 20–28, so it stays sharp on a retina
    screen without shipping the 512 for a sidebar tile.

    $size — px, defaults 28
--}}
@php $size = $size ?? 28; @endphp
<img class="mark-glyph"
     src="{{ asset('img/brand/mark-128.png') }}"
     width="{{ $size }}" height="{{ $size }}"
     alt="{{ config('app.name') }}"
     decoding="async">