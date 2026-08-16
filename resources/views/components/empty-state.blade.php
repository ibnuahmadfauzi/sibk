@props(['title', 'description'])

<div {{ $attributes->class(['sibk-empty-state']) }}>
    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5"/></svg>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>
</div>
