{{--
    One country field, used by the hero, the search bar, registration and the
    profile editor. Featured markets sit in their own group at the top and
    everywhere else follows alphabetically — being unfeatured costs a member
    nothing, they are still one scroll away.

    $countries — from Countries::grouped()
    $selected  — ISO code, or null
    $name      — form field name, defaults to "country"
    $any       — label for the "no preference" option, or null to require one
--}}
@php
    $name     = $name ?? 'country';
    $selected = $selected ?? null;
@endphp
<select class="inp" name="{{ $name }}">
    @isset($any)
        <option value="">{{ $any }}</option>
    @endisset

    @if (! empty($countries['featured']))
        <optgroup label="{{ __('search.popular_countries') }}">
            @foreach ($countries['featured'] as $code => $label)
                <option value="{{ $code }}" @selected($selected === $code)>{{ $label }}</option>
            @endforeach
        </optgroup>
    @endif

    <optgroup label="{{ __('search.all_countries') }}">
        @foreach ($countries['rest'] as $code => $label)
            <option value="{{ $code }}" @selected($selected === $code)>{{ $label }}</option>
        @endforeach
    </optgroup>
</select>
