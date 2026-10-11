@props(['name', 'label', 'sortParam' => 'sort', 'directionParam' => 'direction', 'pageParam' => 'page'])
@php
    $active = request($sortParam) === $name && in_array(request($directionParam), ['asc', 'desc'], true);
    $direction = $active ? request($directionParam) : null;
    [$firstOrder, $reverseOrder] = match (true) {
        in_array($name, ['tanggal', 'efektif', 'waktu', 'mapped_at'], true) => ['terlama ke terbaru', 'terbaru ke terlama'],
        in_array($name, ['record_count', 'jumlah_kasus', 'jumlah_aktif', 'permasalahan', 'poin', 'konsultasi', 'prestasi'], true) => ['terkecil ke terbesar', 'terbesar ke terkecil'],
        default => ['A ke Z', 'Z ke A'],
    };
    $sortLabel = $direction === 'desc' ? 'Kembalikan urutan awal '.$label
        : 'Urutkan '.$label.' dari '.($direction === 'asc' ? $reverseOrder : $firstOrder);
    $next = $direction === null ? [$sortParam => $name, $directionParam => 'asc']
        : ($direction === 'asc' ? [$sortParam => $name, $directionParam => 'desc'] : []);
    $params = array_merge(request()->except($sortParam, $directionParam, $pageParam), $next);
    $url = url()->current().($params === [] ? '' : '?'.http_build_query($params));
@endphp
<th {{ $attributes->merge(['scope' => 'col']) }} aria-sort="{{ $direction === 'asc' ? 'ascending' : ($direction === 'desc' ? 'descending' : 'none') }}">
    <a class="sibk-sort-header {{ $active ? 'is-active' : '' }}" href="{{ $url }}" aria-label="{{ $sortLabel }}">
        <span>{{ $label }}</span>
        <span class="sibk-sort-header__icon" aria-hidden="true">{{ $direction === 'asc' ? '↑' : ($direction === 'desc' ? '↓' : '↕') }}</span>
    </a>
</th>
