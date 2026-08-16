<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('page-title')</title>

    {{-- Vite: SCSS + JS --}}
    @vite(['resources/scss/app-dashboard.scss', 'resources/js/app-dashboard.js'])
    {{-- end Vite --}}

    @yield('extra-css')
</head>

<body class="sibk-app-body">
    <a class="sibk-skip-link" href="#main-content">Lewati ke konten utama</a>

    {{-- Include Sidebar Component --}}
    @include('components.sidebar')
    {{-- end Include Sidebar Component --}}

    {{-- MAIN SECTION --}}
    <main class="sibk-main" id="main-content">

        {{-- Include Topbar Component --}}
        @include('components.topbar')
        {{-- end Include Topbar Component --}}


        {{-- CONTENT SECTION --}}
        <section class="sibk-content">
            @yield('body')
        </section>

    </main>

    @yield('extra-javascript')

</body>

</html>
