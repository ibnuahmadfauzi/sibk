@props(['name', 'label', 'sortParam' => 'sort', 'directionParam' => 'direction', 'pageParam' => 'page'])
@php
    $active = request($sortParam) === $name && in_array(request($directionParam), ['asc', 'desc'], true);
    $direction = $active ? request($directionParam) : null;
    $next = $direction === null ? [$sortParam => $name, $directionParam => 'asc']
        : ($direction === 'asc' ? [$sortParam => $name, $directionParam => 'desc'] : []);
    $params = array_merge(request()->except($sortParam, $directionParam, $pageParam), $next);
    $url = url()->current().($params === [] ? '' : '?'.http_build_query($params));
@endphp
<th {{ $attributes->merge(['scope' => 'col']) }} aria-sort="{{ $direction === 'asc' ? 'ascending' : ($direction === 'desc' ? 'descending' : 'none') }}">
    <a class="sibk-sort-header {{ $active ? 'is-active' : '' }}" href="{{ $url }}" aria-label="Urutkan {{ $label }}{{ $direction === 'asc' ? ' menurun' : ($direction === 'desc' ? ' sesuai urutan awal' : ' menaik') }}">
        <span>{{ $label }}</span>
        <span class="sibk-sort-header__icon" aria-hidden="true">{{ $direction === 'asc' ? '↑' : ($direction === 'desc' ? '↓' : '↕') }}</span>
    </a>
</th>
