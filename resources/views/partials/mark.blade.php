{{--
    The SheTu mark.

    "Setu" (সেতু) is Bengali for bridge, and SheTu reads as she + tumi —
    she and you. So the mark is a bridge: an arch standing on a deck,
    joining two sides that were separate.

    Matrimony draws the arch closed, because that is what it is for.
    Connect draws it open at the apex — two sides reaching, not yet joined —
    which is the honest difference between the two products.

    Drawn in currentColor so it inherits the palette of whichever product
    it is sitting in, and readable down to 16px because the stroke weight
    is the only detail in it.

    $connect — bool, defaults false
    $size    — px, defaults 28
--}}
@php
    $connect = $connect ?? false;
    $size    = $size ?? 28;
@endphp
<svg class="mark-glyph" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 32 32"
     fill="none" role="img" aria-label="{{ config('app.name') }}">
    @if ($connect)
        {{-- Open: two sides rising towards each other, not yet met. --}}
        <path d="M6 21.5V18A10 10 0 0 1 12.2 8.75" stroke="currentColor" stroke-width="2.9" stroke-linecap="round"/>
        <path d="M26 21.5V18A10 10 0 0 0 19.8 8.75" stroke="currentColor" stroke-width="2.9" stroke-linecap="round"/>
    @else
        {{-- Closed: one span, both sides carried. --}}
        <path d="M6 21.5V18a10 10 0 0 1 20 0v3.5" stroke="currentColor" stroke-width="2.9" stroke-linecap="round"/>
    @endif

    {{-- The deck, held clear of the arch so the two read as separate strokes
         at 16px rather than merging into one blob. --}}
    <path d="M4 26h24" stroke="currentColor" stroke-width="2.9" stroke-linecap="round"/>
</svg>
