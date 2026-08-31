{{-- The biodata-sheet row set: label/value pairs with hairline rules. --}}
<dl class="sheet">
    @foreach ($rows as $label => $value)
        @continue(blank($value) && ! ($showEmpty ?? false))
        <dt>{{ $label }}</dt>
        <dd class="{{ $value === null ? 'locked' : '' }}">{{ $value ?? __('profile.locked') }}</dd>
    @endforeach
</dl>
