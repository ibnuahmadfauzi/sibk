@props([
    'href' => route('cases.index'),
    'label' => 'Kembali',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-icon btn-light flex-shrink-0']) }} aria-label="{{ $label }}">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
</a>
