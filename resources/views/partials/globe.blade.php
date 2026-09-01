{{--
    The globe: Globe8.mp4 itself.

    Rebuilt as a seamless loop rather than dropped in as supplied. The source
    is 6.93s and its first and last frames do not match, so a plain `loop`
    would visibly jump every time round. The tail is crossfaded over the head
    with ffmpeg, giving a 5.92s clip whose ends agree — 2.7 MB down to 381 KB
    at 760px wide, no audio track, faststart so it begins before it is fully
    fetched.

    It does not download for someone who never scrolls past the doorway. The
    file is fetched and started only when the panel is close to the viewport,
    which is what `preload="none"` plus the observer below buys: a visitor who
    bounces off the front page pays nothing for it.

    The poster carries the first frame, so the panel is never an empty box
    while the video loads, and it is what remains if the video fails or
    autoplay is refused.
--}}
<div class="globe-panel">
    <video
        class="globe-video"
        poster="{{ asset('video/globe-poster.jpg') }}"
        width="760" height="428"
        muted loop playsinline preload="none"
        aria-hidden="true"
        data-src="{{ asset('video/globe.mp4') }}"></video>

    {{-- Ties the clip's cool blue into a warm palette at the edges without
         recolouring the Earth itself. --}}
    <div class="globe-panel-vignette"></div>
</div>
