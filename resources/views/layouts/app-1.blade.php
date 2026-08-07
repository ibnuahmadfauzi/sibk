<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title')</title>

    <!-- CDN Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <!-- end CDN Bootstrap -->

    {{-- CSS File --}}
    <link rel="stylesheet" href="/assets/css/bootstrap-reset.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    {{-- end CSS File --}}

    @yield('extra-css')
</head>

<body>
    @yield('body')

    <!-- CDN Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
    <!-- end CDN Bootstrap -->

    {{-- JS File --}}
    <script src="/assets/js/script.js"></script>
    {{-- end JS File --}}

    @yield('extra-javascript')
</body>

</html>
