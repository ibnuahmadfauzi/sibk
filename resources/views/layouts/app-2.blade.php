<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page-title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Inter:wght@400;500;600;700;800&family=Caveat:wght@600;700&display=swap" rel="stylesheet">

    {{-- Vite: SCSS + JS --}}
    @vite(['resources/scss/app-dashboard.scss', 'resources/js/app-dashboard.js'])
    {{-- end Vite --}}

    @yield('extra-css')
</head>

<body class="sibk-app-body" data-draft-user="{{ auth()->id() }}" @if(session()->has('success')) data-save-succeeded="true" @endif>
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

    <div class="modal fade sibk-confirmation-modal" id="app-confirmation-modal" tabindex="-1" aria-labelledby="app-confirmation-title" aria-describedby="app-confirmation-message" aria-hidden="true" data-app-confirmation-modal>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close sibk-confirmation-modal__close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                <div class="modal-body text-center">
                    <span class="sibk-confirmation-modal__icon" aria-hidden="true">
                        <svg data-app-confirmation-success viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>
                        <svg data-app-confirmation-danger viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg>
                    </span>
                    <h2 class="modal-title sibk-confirmation-modal__title" id="app-confirmation-title" data-app-confirmation-title>Konfirmasi</h2>
                    <p class="sibk-confirmation-modal__message" id="app-confirmation-message">
                        <span data-app-confirmation-message></span><strong data-app-confirmation-subject></strong><span data-app-confirmation-suffix></span>
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-app-confirmation-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-app-confirmation-action>Ya, lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    @yield('extra-javascript')

</body>

</html>
