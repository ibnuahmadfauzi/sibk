@props(['label', 'type' => 'text'])
<th {{ $attributes->merge(['scope' => 'col']) }} data-sort-type="{{ $type }}" aria-sort="none">
    <button class="sibk-sort-header sibk-sort-header--button" type="button" aria-label="Urutkan {{ $label }}">
        <span>{{ $label }}</span><span class="sibk-sort-header__icon" aria-hidden="true">↕</span>
    </button>
</th>
