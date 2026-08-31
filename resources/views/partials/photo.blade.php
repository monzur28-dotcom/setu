{{--
    A photo tile. When blurred, the blur is applied SERVER-SIDE and served
    from a separate variant — never a CSS filter over the real image, which
    a viewer can simply remove. Spec 19.4.
--}}
@php
    $size = $size ?? 120;
    $initials = collect(explode(' ', trim($name ?? '?')))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp
<div class="ph {{ ($blurred ?? true) ? 'blur' : '' }}" style="--sz:{{ $size }}px">
    @if (! empty($url))
        <img src="{{ $url }}" alt="" style="width:100%;height:100%;object-fit:cover">
    @else
        <span class="mono-init">{{ mb_strtoupper($initials) }}</span>
    @endif
    @if ($blurred ?? true)
        <span class="lockbadge">@lang('profile.hidden')</span>
    @endif
</div>
