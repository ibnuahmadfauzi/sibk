@props(['title', 'description'])

<div {{ $attributes->class(['sibk-empty-state']) }}>
    <div class="sibk-empty-state__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="5" width="16" height="14" rx="2" />
            <line x1="8" y1="9" x2="16" y2="9" />
            <line x1="8" y1="13" x2="13" y2="13" />
        </svg>
    </div>
    <h3 class="sibk-empty-state__title">{{ $title }}</h3>
    <p class="sibk-empty-state__desc">{{ $description }}</p>
</div>
