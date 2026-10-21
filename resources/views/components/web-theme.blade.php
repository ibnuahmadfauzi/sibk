@php($themeVariables = app(\App\Services\WebAppearanceService::class)->themeVariables())
@if($themeVariables)
    <style data-web-theme-colors>
        html[data-web-theme] {
            @foreach($themeVariables as $property => $value)
                {{ $property }}: {{ $value }};
            @endforeach
        }
    </style>
@endif
