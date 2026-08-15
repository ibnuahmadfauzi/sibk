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

    {{-- CDN Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    {{-- end CDN Bootstrap --}}

    {{-- CDN Fontawesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
        integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- end CDN Fontawesome --}}

    {{-- CSS File --}}
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    {{-- end CSS File --}}

    @yield('extra-css')
</head>

<body>

    {{-- Include Sidebar Component --}}
    @include('components.sidebar')
    {{-- end Include Sidebar Component --}}

    {{-- MAIN SECTION --}}
    <main class="main">

        {{-- Include Topbar Component --}}
        @include('components.topbar')
        {{-- end Include Topbar Component --}}


        {{-- CONTENT SECTION --}}
        <section class="content">
            @yield('body')
        </section>

    </main>

    <!-- CDN Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
    <!-- end CDN Bootstrap -->

    {{-- CDN Font Awesome --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/js/all.min.js"
        integrity="sha512-BivWm1+PupfLofQ5Ei/fNEC6Oq6IZiGO9WUm2ibWHZ33cj/qTX4zsBW/0SN9Myo4HEmINmr0wjuQE8eDL3rmng=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    {{-- end CDN Font Awesome --}}

    {{-- JS File --}}
    <script src="/assets/js/dashboard.js"></script>
    {{-- end JS File --}}

    @yield('extra-javascript')

</body>

</html>
