<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('page-title')</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- end Google Fonts -->

    {{-- Vite: SCSS + JS --}}
    @vite(['resources/scss/app-dashboard.scss', 'resources/js/app-dashboard.js'])
    {{-- end Vite --}}

    @yield('extra-css')
</head>

<body>

    {{-- Include Sidebar Component --}}
    @include('components.sidebar')
    {{-- end Include Sidebar Component --}}

    {{-- MAIN SECTION --}}
    <main class="sibk-main">

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
