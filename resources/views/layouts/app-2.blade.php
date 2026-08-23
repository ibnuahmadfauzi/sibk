<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('page-title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Inter:wght@400;500;600;700;800&family=Caveat:wght@600;700&display=swap" rel="stylesheet">

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
