<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title')</title>

    {{-- Vite: SCSS + JS --}}
    @vite(['resources/scss/app-auth.scss', 'resources/js/app-auth.js'])
    {{-- end Vite --}}
</head>

<body>
    <a class="sibk-skip-link" href="#main-content">Lewati ke formulir masuk</a>
    @yield('body')

    @yield('extra-javascript')
</body>

</html>
